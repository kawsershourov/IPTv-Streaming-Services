<?php
declare(strict_types=1);

/**
 * Helper functions shared across the app.
 */

/** Dot-path access into the loaded config array. */
function config(string $key, $default = null)
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (is_array($value) && array_key_exists($part, $value)) {
            $value = $value[$part];
        } else {
            return $default;
        }
    }
    return $value;
}

/** HTML-escape a string for safe output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a full URL/path under the app base_url. */
function url(string $path = ''): string
{
    $base = rtrim((string) config('site.base_url', ''), '/');
    if ($path === '') {
        return $base . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * Resolve a stored upload URL (logo / media / favicon) for the CURRENT base_url.
 * DB rows saved on one environment (e.g. localhost "/SunPlex.live/uploads/..") then
 * keep working on another (e.g. a domain root "/uploads/.."). External http(s)
 * URLs and non-upload paths pass through unchanged.
 */
function asset_url(?string $stored): string
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $stored)) {
        return $stored;
    }
    if (preg_match('#(uploads/[^\s"\']+)$#', $stored, $m)) {
        return url($m[1]);
    }
    return $stored;
}

/** Build an asset URL under /assets, with a cache-busting ?v=<mtime> so updates load immediately. */
function asset(string $path): string
{
    $rel  = 'assets/' . ltrim($path, '/');
    $url  = url($rel);
    $file = BASE_DIR . '/' . $rel;
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

/** Redirect to an app path and stop. */
function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/** Convert a string to a URL-safe slug. */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Format a datetime string for display. */
function fmt_date(?string $dt, string $format = 'M j, Y'): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts ? date($format, $ts) : '—';
}

/* --------------------------------------------------------------------- */
/* CSRF                                                                   */
/* --------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Verify the CSRF token from a POST request; aborts on mismatch. */
function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['_csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back and try again.');
    }
}

/**
 * Handle an uploaded image from $_FILES[$field]; returns a web path or null.
 * Saves into /uploads/logos. Supports raster formats and SVG/ICO (which getimagesize
 * can't read, so those are validated by extension + a light content sniff).
 */
function upload_image(string $field, string $prefix = 'img'): ?string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Upload failed.');
        return null;
    }

    $tmp = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION));

    $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'bmp', 'avif'];
    if (!in_array($ext, $allowed, true)) {
        flash('error', 'Unsupported image type. Use PNG, JPG, JPEG, GIF, WEBP, SVG, ICO, BMP, or AVIF.');
        return null;
    }

    if ($ext === 'svg') {
        $head = (string) @file_get_contents($tmp, false, null, 0, 2048);
        if (stripos($head, '<svg') === false) {
            flash('error', 'That SVG file looks invalid.');
            return null;
        }
    } elseif ($ext !== 'ico') {
        // Raster formats: confirm it's a real image.
        if (@getimagesize($tmp) === false) {
            flash('error', 'That file does not look like a valid image.');
            return null;
        }
    }

    $store = $ext === 'jpeg' ? 'jpg' : $ext;
    $name  = $prefix . '_' . bin2hex(random_bytes(5)) . '.' . $store;
    if (!move_uploaded_file($tmp, BASE_DIR . '/uploads/logos/' . $name)) {
        flash('error', 'Could not store the upload.');
        return null;
    }
    return url('uploads/logos/' . $name);
}

/** <link rel="icon"> tag for the configured site icon, or empty string. */
function favicon_tag(): string
{
    $icon = asset_url(Setting::get('site_icon', ''));
    return $icon ? '<link rel="icon" href="' . e($icon) . '">' : '';
}

/**
 * Render a pagination control. Links carry data-page (for AJAX) and an href
 * (no-JS fallback) built from $params + page. Returns '' when there's one page.
 */
function pager_html(int $page, int $pages, array $params = []): string
{
    if ($pages <= 1) {
        return '';
    }
    $link = static function (int $n, string $label, bool $active = false, bool $disabled = false) use ($params): string {
        $cls = 'page-link' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '');
        $href = '?' . http_build_query(array_merge($params, ['page' => $n]));
        return '<a class="' . $cls . '" data-page="' . $n . '" href="' . e($href) . '">' . $label . '</a>';
    };

    $html  = '<nav class="pager">';
    $html .= $link(max(1, $page - 1), '‹', false, $page <= 1);

    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) {
        $html .= $link(1, '1');
        if ($start > 2) {
            $html .= '<span class="page-gap">…</span>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $link($i, (string) $i, $i === $page);
    }
    if ($end < $pages) {
        if ($end < $pages - 1) {
            $html .= '<span class="page-gap">…</span>';
        }
        $html .= $link($pages, (string) $pages);
    }

    $html .= $link(min($pages, $page + 1), '›', false, $page >= $pages);
    $html .= '</nav>';
    return $html;
}

/* --------------------------------------------------------------------- */
/* Flash messages                                                         */
/* --------------------------------------------------------------------- */

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Return and clear all pending flash messages. */
function flash_take(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/** Render pending flash messages as auto-dismissing toast notifications (top-right). */
function flash_render(): string
{
    $messages = flash_take();
    if (!$messages) {
        return '';
    }
    $html = '<div class="toast-container" id="toastContainer">';
    foreach ($messages as $f) {
        $html .= '<div class="toast toast-' . e($f['type']) . '">'
               . '<span>' . e($f['message']) . '</span>'
               . '<button type="button" class="toast-close" aria-label="Close">&times;</button></div>';
    }
    $html .= '</div>'
           . '<script>(function(){var c=document.getElementById("toastContainer");if(!c)return;'
           . 'function rm(t){t.classList.add("toast-out");setTimeout(function(){t.remove();},300);}'
           . 'c.querySelectorAll(".toast").forEach(function(t){'
           . 't.querySelector(".toast-close").addEventListener("click",function(){rm(t);});'
           . 'setTimeout(function(){rm(t);},4500);});})();</script>';
    return $html;
}

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

/** Build an asset URL under /assets. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
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
 * Saves into /uploads/logos with a random name. Flashes an error on failure.
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
    $tmp     = $_FILES[$field]['tmp_name'];
    $info    = @getimagesize($tmp);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
    $mime    = $info['mime'] ?? @mime_content_type($tmp);
    if (!isset($allowed[$mime])) {
        flash('error', 'Image must be PNG, JPG, GIF, WEBP, or SVG.');
        return null;
    }
    $name = $prefix . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmp, BASE_DIR . '/uploads/logos/' . $name)) {
        flash('error', 'Could not store the upload.');
        return null;
    }
    return url('uploads/logos/' . $name);
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

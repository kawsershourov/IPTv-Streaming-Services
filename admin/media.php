<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();

$me = current_user();

/** Allowed media extensions => grid kind. */
function media_allowed(): array
{
    return [
        'png' => 'img', 'jpg' => 'img', 'jpeg' => 'img', 'gif' => 'img', 'webp' => 'img',
        'svg' => 'img', 'bmp' => 'img', 'avif' => 'img', 'ico' => 'img',
        'mp4' => 'video', 'webm' => 'video', 'ogg' => 'video',
        'mp3' => 'audio', 'pdf' => 'file',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    if ($op === 'delete') {
        Media::delete((int) ($_POST['id'] ?? 0));
        flash('success', 'Media deleted.');
        redirect('admin/media.php');
    }

    if ($op === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $n = 0;
        foreach ($ids as $id) {
            if ($id > 0) { Media::delete($id); $n++; }
        }
        flash($n ? 'success' : 'error', $n ? "Deleted {$n} item(s)." : 'Select at least one item.');
        redirect('admin/media.php');
    }

    if ($op === 'upload') {
        $allowed = media_allowed();
        $dir = BASE_DIR . '/uploads/media/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $added = 0;
        $errors = [];
        $files = $_FILES['files'] ?? null;
        if ($files && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $orig = (string) $files['name'][$i];
                $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!isset($allowed[$ext])) {
                    $errors[] = $orig . ' (unsupported type)';
                    continue;
                }
                $tmp = $files['tmp_name'][$i];
                // Light validation for raster images.
                if ($allowed[$ext] === 'img' && !in_array($ext, ['svg', 'ico'], true) && @getimagesize($tmp) === false) {
                    $errors[] = $orig . ' (not a valid image)';
                    continue;
                }
                $name = 'm_' . bin2hex(random_bytes(6)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                if (move_uploaded_file($tmp, $dir . $name)) {
                    Media::create([
                        'filename'    => $name,
                        'url'         => url('uploads/media/' . $name),
                        'mime'        => (string) ($files['type'][$i] ?? ''),
                        'size'        => (int) ($files['size'][$i] ?? 0),
                        'uploaded_by' => (int) $me['id'],
                    ]);
                    $added++;
                }
            }
        }
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['added' => $added, 'errors' => $errors]);
            exit;
        }
        flash($added ? 'success' : 'error', $added ? "Uploaded {$added} file(s)." : 'No files uploaded.');
        foreach (array_slice($errors, 0, 5) as $err) {
            flash('error', 'Skipped: ' . $err);
        }
        redirect('admin/media.php');
    }
}

/** Grid kind (img/video/audio/file) from a URL's extension. */
function media_kind(string $url): string
{
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
    return media_allowed()[$ext] ?? 'file';
}

// ---- Picker mode: grid for the media-picker modal ----
if (isset($_GET['picker'])) {
    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 18;
    $total   = Media::count();
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = min($page, $pages);
    $items   = Media::paged($perPage, ($page - 1) * $perPage);
    require __DIR__ . '/_media_picker.php';
    exit;
}

// ---- list ----
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 24;
$total   = Media::count();
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($page, $pages);
$items   = Media::paged($perPage, ($page - 1) * $perPage);

$adminTitle = 'Media';
$activeNav  = 'media';
require __DIR__ . '/includes/header.php';

$kindOf = static function (string $url): string {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
    return media_allowed()[$ext] ?? 'file';
};
?>
<div class="toolbar">
    <h1 style="margin:0;">Media library</h1>
    <span class="muted"><?= (int) $total ?> item(s)</span>
</div>

<div class="admin-form" style="max-width:none;margin-bottom:18px;">
    <form method="post" action="<?= e(url('admin/media.php')) ?>" enctype="multipart/form-data" class="form" id="mediaUploadForm" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="upload">
        <input type="file" name="files[]" multiple accept="image/*,video/mp4,video/webm,audio/mpeg,.pdf" required style="flex:1;min-width:240px;">
        <button class="btn btn-primary" id="mediaUploadBtn">Upload</button>
    </form>
    <p class="muted" style="font-size:12px;margin:8px 0 0;">Images (PNG/JPG/GIF/WEBP/SVG/ICO/BMP/AVIF), MP4/WEBM video, MP3, PDF. Select as many as you like — large batches upload automatically in groups.</p>
</div>

<form method="post" action="<?= e(url('admin/media.php')) ?>" id="bulkMediaForm" class="bulk-bar">
    <?= csrf_field() ?>
    <input type="hidden" name="op" value="bulk_delete">
    <label class="check" style="margin:0;"><input type="checkbox" id="selectAllMedia"> Select all</label>
    <button type="submit" class="btn btn-danger btn-sm">Delete selected</button>
</form>

<?php if ($items): ?>
    <div class="media-grid">
        <?php foreach ($items as $m): $kind = $kindOf($m['url']); $u = asset_url($m['url']); ?>
            <div class="media-card">
                <label class="media-check"><input type="checkbox" class="media-cb" name="ids[]" value="<?= (int) $m['id'] ?>" form="bulkMediaForm"></label>
                <div class="media-thumb">
                    <?php if ($kind === 'img'): ?>
                        <img src="<?= e($u) ?>" alt="" loading="lazy">
                    <?php elseif ($kind === 'video'): ?>
                        <span class="media-icon">🎬</span>
                    <?php elseif ($kind === 'audio'): ?>
                        <span class="media-icon">🎵</span>
                    <?php else: ?>
                        <span class="media-icon">📄</span>
                    <?php endif; ?>
                </div>
                <div class="media-meta">
                    <input type="text" class="media-url" value="<?= e($u) ?>" readonly title="<?= e($m['filename']) ?>">
                    <div class="media-actions">
                        <button type="button" class="btn btn-outline btn-sm media-copy">Copy URL</button>
                        <form method="post" action="<?= e(url('admin/media.php')) ?>" data-confirm="Delete this media file? This cannot be undone." style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="op" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="pager-wrap"><?= pager_html($page, $pages) ?></div>
<?php else: ?>
    <p class="empty">No media yet — upload images, video or PDFs above.</p>
<?php endif; ?>

<script>
(function () {
    var all = document.getElementById('selectAllMedia');
    if (all) { all.addEventListener('change', function () { document.querySelectorAll('.media-cb').forEach(function (b) { b.checked = all.checked; }); }); }

    // Batched upload: send files in groups (PHP allows only ~20 files per request),
    // so any number of files works regardless of host limits.
    var upForm = document.getElementById('mediaUploadForm');
    var upBtn = document.getElementById('mediaUploadBtn');
    if (upForm && upBtn) {
        var fileInput = upForm.querySelector('input[type=file]');
        var endpoint = upForm.getAttribute('action');
        var csrf = (upForm.querySelector('input[name=_csrf]') || {}).value;
        var BATCH = 12;
        upForm.addEventListener('submit', function (e) {
            var files = fileInput.files ? Array.prototype.slice.call(fileInput.files) : [];
            if (files.length <= BATCH) { return; } // small batch: normal submit is fine
            e.preventDefault();
            var i = 0, added = 0, failed = 0;
            upBtn.disabled = true;
            function next() {
                if (i >= files.length) {
                    upBtn.textContent = 'Done (' + added + ')';
                    window.location.href = endpoint; // reload to show everything
                    return;
                }
                var batch = files.slice(i, i + BATCH);
                var fd = new FormData();
                fd.append('op', 'upload');
                fd.append('ajax', '1');
                if (csrf) { fd.append('_csrf', csrf); }
                batch.forEach(function (f) { fd.append('files[]', f); });
                upBtn.textContent = 'Uploading ' + Math.min(i + batch.length, files.length) + ' / ' + files.length + '…';
                fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { added += (d.added || 0); failed += (d.errors ? d.errors.length : 0); i += BATCH; next(); })
                    .catch(function () { failed += batch.length; i += BATCH; next(); });
            }
            next();
        });
    }

    // Copy URL to clipboard with brief feedback.
    document.querySelectorAll('.media-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.closest('.media-meta').querySelector('.media-url');
            var done = function () { var o = btn.textContent; btn.textContent = 'Copied!'; setTimeout(function () { btn.textContent = o; }, 1200); };
            if (navigator.clipboard) { navigator.clipboard.writeText(input.value).then(done, function () { input.select(); document.execCommand('copy'); done(); }); }
            else { input.select(); document.execCommand('copy'); done(); }
        });
    });

    // Bulk delete confirm via the shared modal.
    var bulk = document.getElementById('bulkMediaForm');
    if (bulk) {
        bulk.addEventListener('submit', function (e) {
            if (bulk.dataset.confirmed === '1') { return; }
            e.preventDefault();
            var n = document.querySelectorAll('.media-cb:checked').length;
            if (!n) { alert('Select at least one item.'); return; }
            window.spConfirm('Delete ' + n + ' selected item(s)? This cannot be undone.', function () {
                bulk.dataset.confirmed = '1'; bulk.submit();
            });
        });
    }
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

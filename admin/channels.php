<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();

$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);

/** Handle an optional channel logo upload; returns a web path or null. */
function handle_logo_upload(): ?string
{
    return upload_image('logo_file', 'ch');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    if ($op === 'reorder') {
        // Drag-and-drop reorder. sort_order is assigned PER CATEGORY by position,
        // because the front-end orders channels within each category — so the new
        // order matches what the site shows.
        $ids      = array_map('intval', (array) ($_POST['ids'] ?? []));
        $counters = [];
        foreach ($ids as $cid) {
            if ($cid <= 0) {
                continue;
            }
            $row = db_one('SELECT category_id FROM channels WHERE id = ?', [$cid]);
            if (!$row) {
                continue;
            }
            $cat = (int) $row['category_id'];
            $n   = $counters[$cat] ?? 0;
            db_run('UPDATE channels SET sort_order = ? WHERE id = ?', [$n, $cid]);
            $counters[$cat] = $n + 1;
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'count' => count($ids)]);
        exit;
    }

    if ($op === 'delete') {
        Channel::delete((int) $_POST['id']);
        flash('success', 'Channel deleted.');
        redirect('admin/channels.php');
    }

    if ($op === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $n = 0;
        foreach ($ids as $id) {
            if ($id > 0) { Channel::delete($id); $n++; }
        }
        flash($n ? 'success' : 'error', $n ? "Deleted {$n} channel(s)." : 'Select at least one channel.');
        redirect('admin/channels.php');
    }

    if ($op === 'bulk_status') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $status = ($_POST['bulk_status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        $n = 0;
        foreach ($ids as $id) {
            if ($ch = Channel::find($id)) {
                $ch['status'] = $status;
                Channel::update($id, $ch);
                $n++;
            }
        }
        flash($n ? 'success' : 'error', $n ? "Set {$n} channel(s) to {$status}." : 'Select at least one channel.');
        redirect('admin/channels.php');
    }

    if ($op === 'save') {
        $editId = (int) ($_POST['id'] ?? 0);
        $existing = $editId ? Channel::find($editId) : null;
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: slugify($name);

        $uploaded = handle_logo_upload();
        $logo = $uploaded ?? (trim($_POST['logo'] ?? '') ?: ($existing['logo'] ?? null));

        $data = [
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'name'        => $name,
            'slug'        => $slug,
            'logo'        => $logo,
            'stream_url'  => trim($_POST['stream_url'] ?? ''),
            'stream_type' => in_array($_POST['stream_type'] ?? 'hls', ['hls', 'dash', 'mp4', 'youtube'], true) ? $_POST['stream_type'] : 'hls',
            'is_live'     => isset($_POST['is_live']) ? 1 : 0,
            'is_premium'  => isset($_POST['is_premium']) ? 1 : 0,
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'status'      => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        if ($name === '' || $data['category_id'] === 0 || $data['stream_url'] === '') {
            flash('error', 'Name, category and stream URL are required.');
            redirect('admin/channels.php?action=' . ($editId ? 'edit&id=' . $editId : 'new'));
        }
        try {
            if ($editId) {
                Channel::update($editId, $data);
                flash('success', 'Channel updated.');
            } else {
                Channel::create($data);
                flash('success', 'Channel created.');
            }
            redirect('admin/channels.php');
        } catch (PDOException $ex) {
            flash('error', 'Could not save (is the slug unique?).');
            redirect('admin/channels.php?action=' . ($editId ? 'edit&id=' . $editId : 'new'));
        }
    }
}

$adminTitle = 'Channels';
$activeNav  = 'channels';

if ($action === 'new' || $action === 'edit') {
    $ch = $action === 'edit' ? Channel::find($id) : null;
    if ($action === 'edit' && !$ch) {
        flash('error', 'Channel not found.');
        redirect('admin/channels.php');
    }
    $categories = Category::all();
    $types = ['hls' => 'HLS (.m3u8)', 'dash' => 'DASH (.mpd)', 'mp4' => 'MP4', 'youtube' => 'YouTube'];
    require __DIR__ . '/includes/header.php';
    ?>
    <h1><?= $action === 'edit' ? 'Edit' : 'New' ?> channel</h1>
    <div class="admin-form">
        <form method="post" action="<?= e(url('admin/channels.php')) ?>" class="form" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="id" value="<?= (int) ($ch['id'] ?? 0) ?>">

            <div class="row2">
                <label>Name <input type="text" name="name" value="<?= e($ch['name'] ?? '') ?>" required autofocus></label>
                <label>Slug <input type="text" name="slug" value="<?= e($ch['slug'] ?? '') ?>" placeholder="auto from name"></label>
            </div>
            <label>Category
                <select name="category_id" required>
                    <option value="">— choose —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($ch['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Stream URL <input type="text" name="stream_url" value="<?= e($ch['stream_url'] ?? '') ?>" placeholder="https://…/stream.m3u8" required></label>
            <div class="row2">
                <label>Stream type
                    <select name="stream_type">
                        <?php foreach ($types as $val => $lbl): ?>
                            <option value="<?= e($val) ?>" <?= ($ch['stream_type'] ?? 'hls') === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Sort order <input type="number" name="sort_order" value="<?= (int) ($ch['sort_order'] ?? 0) ?>"></label>
            </div>
            <label>Logo
                <span class="media-field">
                    <input type="text" id="chLogoInput" name="logo" value="<?= e($ch['logo'] ?? '') ?>" placeholder="Pick from Media, paste a URL, or upload below">
                    <button type="button" class="btn btn-outline btn-sm" data-media-target="#chLogoInput" data-media-url="<?= e(url('admin/media.php')) ?>">📁 Media</button>
                </span>
            </label>
            <label>…or upload a new logo <input type="file" name="logo_file" accept="image/*"></label>
            <?php if (!empty($ch['logo'])): ?><p class="muted">Current: <?= e($ch['logo']) ?></p><?php endif; ?>

            <label class="check"><input type="checkbox" name="is_live" <?= (int) ($ch['is_live'] ?? 1) ? 'checked' : '' ?>> Live channel</label>
            <label class="check"><input type="checkbox" name="is_premium" <?= (int) ($ch['is_premium'] ?? 0) ? 'checked' : '' ?>> Premium (requires subscription)</label>
            <label>Status
                <select name="status">
                    <option value="active" <?= ($ch['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($ch['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary">Save</button>
                <a href="<?= e(url('admin/channels.php')) ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---- list ----
// "Show all" mode loads every channel on one page so drag-reorder can move a
// channel anywhere within its category. Normal mode stays paginated (20/page).
$q        = trim((string) ($_GET['q'] ?? ''));
$showAll  = isset($_GET['all']);
$perPage  = $showAll ? 100000 : 20;
$page     = $showAll ? 1 : max(1, (int) ($_GET['page'] ?? 1));
$total    = Channel::searchCount($q);
$pages    = $showAll ? 1 : max(1, (int) ceil($total / $perPage));
$page     = min($page, $pages);
$channels = Channel::searchPaged($q, $perPage, ($page - 1) * $perPage);

// AJAX live-search/pagination: return rows + pager as JSON.
if (isset($_GET['ajax'])) {
    ob_start();
    require __DIR__ . '/_channels_rows.php';
    $rows = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['rows' => $rows, 'pager' => $showAll ? '' : pager_html($page, $pages, ['q' => $q])]);
    exit;
}

require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Channels</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="get" action="<?= e(url('admin/channels.php')) ?>" class="search-box">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search channels…">
            <button class="btn btn-outline btn-sm">Search</button>
            <?php if ($q !== ''): ?><a href="<?= e(url('admin/channels.php')) ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
        </form>
        <?php if ($showAll): ?>
            <a href="<?= e(url('admin/channels.php')) ?>" class="btn btn-outline btn-sm">Show paginated</a>
        <?php else: ?>
            <a href="<?= e(url('admin/channels.php?all=1')) ?>" class="btn btn-outline btn-sm">↕ Show all (reorder)</a>
        <?php endif; ?>
        <a href="<?= e(url('admin/channels_import.php')) ?>" class="btn btn-outline btn-sm">Import CSV/Excel</a>
        <a href="<?= e(url('admin/channels.php?action=new')) ?>" class="btn btn-primary btn-sm">+ New channel</a>
    </div>
</div>

<form method="post" action="<?= e(url('admin/channels.php')) ?>" id="bulkChForm" class="bulk-bar">
    <?= csrf_field() ?>
    <span class="muted">With selected:</span>
    <select name="bulk_status" class="mini-select">
        <option value="active">activate</option>
        <option value="inactive">deactivate</option>
    </select>
    <button type="submit" name="op" value="bulk_status" class="btn btn-outline btn-sm">Apply status</button>
    <button type="submit" name="op" value="bulk_delete" class="btn btn-danger btn-sm">Delete selected</button>
</form>

<?php if ($showAll): ?>
    <p class="muted" style="margin:0 0 8px;font-size:13px;"><strong>Drag the <span style="letter-spacing:-2px;">⠿</span> handle</strong> to reorder a channel within its category — the new order shows on the site. (To move a channel above another category, reorder the categories, or change the channel’s category.)</p>
<?php else: ?>
    <p class="muted" style="margin:0 0 8px;font-size:13px;">Showing 20 per page. Click <strong>“↕ Show all (reorder)”</strong> above to drag-and-drop channels into the order you want.</p>
<?php endif; ?>
<div class="table-wrap">
    <table class="data">
        <thead><tr>
            <th style="width:28px;"></th>
            <th style="width:34px;"><input type="checkbox" id="selectAllCh" title="Select all"></th>
            <th>Name</th><th>Category</th><th>Type</th><th>Live</th><th>Premium</th><th>Status</th><th></th>
        </tr></thead>
        <tbody id="chBody" data-reorder="<?= ($showAll && $q === '') ? '1' : '0' ?>">
        <?php require __DIR__ . '/_channels_rows.php'; ?>
        </tbody>
    </table>
</div>
<div id="chPager" class="pager-wrap"><?= pager_html($page, $pages, ['q' => $q]) ?></div>
<script>
(function () {
    var showAll = <?= $showAll ? 'true' : 'false' ?>;
    var all = document.getElementById('selectAllCh');
    if (all) { all.addEventListener('change', function () { document.querySelectorAll('.ch-check').forEach(function (b) { b.checked = all.checked; }); }); }

    // AJAX live search + pagination.
    var sform = document.querySelector('.search-box');
    var sinput = sform ? sform.querySelector('input[name=q]') : null;
    var body = document.getElementById('chBody');
    var pager = document.getElementById('chPager');
    if (sform && sinput && body) {
        var t, base = sform.getAttribute('action');
        function load(page) {
            body.style.opacity = '.5';
            fetch(base + '?q=' + encodeURIComponent(sinput.value) + '&page=' + (page || 1) + (showAll ? '&all=1' : '') + '&ajax=1', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    body.innerHTML = d.rows;
                    if (pager) pager.innerHTML = d.pager;
                    body.dataset.reorder = (showAll && sinput.value.trim() === '') ? '1' : '0';
                    body.style.opacity = '1';
                    if (all) all.checked = false;
                });
        }
        sinput.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { load(1); }, 250); });
        sform.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(t); load(1); });
        if (pager) {
            pager.addEventListener('click', function (e) {
                var a = e.target.closest('.page-link');
                if (!a || a.classList.contains('disabled') || a.classList.contains('active')) { return; }
                e.preventDefault();
                load(parseInt(a.getAttribute('data-page'), 10) || 1);
            });
        }
    }

    // ---- Drag-and-drop reorder ----
    var chBody = document.getElementById('chBody');
    if (chBody) {
        var dragEl = null;
        var reorderUrl = <?= json_encode(url('admin/channels.php')) ?>;

        chBody.addEventListener('dragstart', function (e) {
            var tr = e.target.closest('tr.ch-row');
            if (!tr || chBody.dataset.reorder !== '1') { e.preventDefault(); return; }
            dragEl = tr;
            tr.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', tr.dataset.id); } catch (_) {}
        });

        chBody.addEventListener('dragover', function (e) {
            if (!dragEl) { return; }
            e.preventDefault();
            var tr = e.target.closest('tr.ch-row');
            if (!tr || tr === dragEl) { return; }
            if (tr.dataset.cat !== dragEl.dataset.cat) { return; } // keep it within its own category
            var rect = tr.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            chBody.insertBefore(dragEl, after ? tr.nextSibling : tr);
        });

        chBody.addEventListener('drop', function (e) { e.preventDefault(); });

        chBody.addEventListener('dragend', function () {
            if (!dragEl) { return; }
            dragEl.classList.remove('dragging');
            dragEl = null;
            var tokenEl = document.querySelector('#bulkChForm input[name=_csrf]');
            var fd = new FormData();
            fd.append('op', 'reorder');
            fd.append('_csrf', tokenEl ? tokenEl.value : '');
            chBody.querySelectorAll('tr.ch-row').forEach(function (tr) { fd.append('ids[]', tr.dataset.id); });
            chBody.style.opacity = '.6';
            fetch(reorderUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function () { chBody.style.opacity = '1'; })
                .catch(function () { chBody.style.opacity = '1'; });
        });
    }

    var bulk = document.getElementById('bulkChForm');
    if (bulk) {
        bulk.addEventListener('submit', function (e) {
            if (bulk.dataset.confirmed === '1') { return; }
            e.preventDefault();
            var op = e.submitter ? e.submitter.value : 'bulk_status';
            var n = document.querySelectorAll('.ch-check:checked').length;
            if (!n) { alert('Tick at least one channel first.'); return; }
            var go = function () {
                var h = document.createElement('input'); h.type = 'hidden'; h.name = 'op'; h.value = op; bulk.appendChild(h);
                bulk.dataset.confirmed = '1'; bulk.submit();
            };
            if (op === 'bulk_delete') { window.spConfirm('Delete ' + n + ' selected channel(s)? This cannot be undone.', go); }
            else { go(); }
        });
    }
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

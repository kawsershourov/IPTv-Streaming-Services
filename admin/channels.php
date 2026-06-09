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
            <label>Logo URL <input type="text" name="logo" value="<?= e($ch['logo'] ?? '') ?>" placeholder="https://… or upload below"></label>
            <label>Upload logo <input type="file" name="logo_file" accept="image/*"></label>
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
$q = trim((string) ($_GET['q'] ?? ''));
$channels = Channel::allWithCategory();
if ($q !== '') {
    $needle = mb_strtolower($q);
    $channels = array_values(array_filter($channels, static fn ($c) =>
        mb_strpos(mb_strtolower($c['name'] . ' ' . $c['category_name']), $needle) !== false));
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

<div class="table-wrap">
    <table class="data">
        <thead><tr>
            <th style="width:34px;"><input type="checkbox" id="selectAllCh" title="Select all"></th>
            <th>Name</th><th>Category</th><th>Type</th><th>Live</th><th>Premium</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($channels as $c): ?>
            <tr>
                <td><input type="checkbox" class="ch-check" name="ids[]" value="<?= (int) $c['id'] ?>" form="bulkChForm"></td>
                <td><?= e($c['name']) ?></td>
                <td class="muted"><?= e($c['category_name']) ?></td>
                <td><?= e(strtoupper($c['stream_type'])) ?></td>
                <td><?= (int) $c['is_live'] ? 'Yes' : 'No' ?></td>
                <td><?= (int) $c['is_premium'] ? '<span class="tag tag-prem">Premium</span>' : '—' ?></td>
                <td><?= $c['status'] === 'active' ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">inactive</span>' ?></td>
                <td><div class="row-actions">
                    <a href="<?= e(url('admin/channels.php?action=edit&id=' . $c['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="post" action="<?= e(url('admin/channels.php')) ?>" data-confirm="Delete &quot;<?= e($c['name']) ?>&quot;? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$channels): ?><tr><td colspan="8" class="muted"><?= $q !== '' ? 'No channels match “' . e($q) . '”.' : 'No channels yet.' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<script>
(function () {
    var all = document.getElementById('selectAllCh');
    var boxes = document.querySelectorAll('.ch-check');
    if (all) { all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); }); }
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

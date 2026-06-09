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
$channels = Channel::allWithCategory();
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Channels</h1>
    <div style="display:flex;gap:8px;">
        <a href="<?= e(url('admin/channels_import.php')) ?>" class="btn btn-outline btn-sm">Import CSV/Excel</a>
        <a href="<?= e(url('admin/channels.php?action=new')) ?>" class="btn btn-primary btn-sm">+ New channel</a>
    </div>
</div>
<div class="table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Category</th><th>Type</th><th>Live</th><th>Premium</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($channels as $c): ?>
            <tr>
                <td><?= e($c['name']) ?></td>
                <td class="muted"><?= e($c['category_name']) ?></td>
                <td><?= e(strtoupper($c['stream_type'])) ?></td>
                <td><?= (int) $c['is_live'] ? 'Yes' : 'No' ?></td>
                <td><?= (int) $c['is_premium'] ? '<span class="tag tag-prem">Premium</span>' : '—' ?></td>
                <td><?= $c['status'] === 'active' ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">inactive</span>' ?></td>
                <td><div class="row-actions">
                    <a href="<?= e(url('admin/channels.php?action=edit&id=' . $c['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="post" action="<?= e(url('admin/channels.php')) ?>" onsubmit="return confirm('Delete this channel?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$channels): ?><tr><td colspan="7" class="muted">No channels yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

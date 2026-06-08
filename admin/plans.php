<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    if ($op === 'delete') {
        Plan::delete((int) $_POST['id']);
        flash('success', 'Plan deleted.');
        redirect('admin/plans.php');
    }

    if ($op === 'save') {
        $editId = (int) ($_POST['id'] ?? 0);
        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'price'         => (float) ($_POST['price'] ?? 0),
            'duration_days' => max(1, (int) ($_POST['duration_days'] ?? 30)),
            'description'   => trim($_POST['description'] ?? '') ?: null,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'    => (int) ($_POST['sort_order'] ?? 0),
        ];
        if ($data['name'] === '') {
            flash('error', 'Plan name is required.');
            redirect('admin/plans.php?action=' . ($editId ? 'edit&id=' . $editId : 'new'));
        }
        if ($editId) {
            Plan::update($editId, $data);
            flash('success', 'Plan updated.');
        } else {
            Plan::create($data);
            flash('success', 'Plan created.');
        }
        redirect('admin/plans.php');
    }
}

$adminTitle = 'Plans';
$activeNav  = 'plans';

if ($action === 'new' || $action === 'edit') {
    $plan = $action === 'edit' ? Plan::find($id) : null;
    if ($action === 'edit' && !$plan) {
        flash('error', 'Plan not found.');
        redirect('admin/plans.php');
    }
    require __DIR__ . '/includes/header.php';
    ?>
    <h1><?= $action === 'edit' ? 'Edit' : 'New' ?> plan</h1>
    <div class="admin-form">
        <form method="post" action="<?= e(url('admin/plans.php')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="id" value="<?= (int) ($plan['id'] ?? 0) ?>">
            <label>Name <input type="text" name="name" value="<?= e($plan['name'] ?? '') ?>" required autofocus></label>
            <div class="row2">
                <label>Price (USD) <input type="number" step="0.01" min="0" name="price" value="<?= e($plan['price'] ?? '0.00') ?>"></label>
                <label>Duration (days) <input type="number" min="1" name="duration_days" value="<?= (int) ($plan['duration_days'] ?? 30) ?>"></label>
            </div>
            <label>Description <textarea name="description" rows="3"><?= e($plan['description'] ?? '') ?></textarea></label>
            <div class="row2">
                <label>Sort order <input type="number" name="sort_order" value="<?= (int) ($plan['sort_order'] ?? 0) ?>"></label>
                <label class="check" style="margin-top:28px;"><input type="checkbox" name="is_active" <?= (int) ($plan['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary">Save</button>
                <a href="<?= e(url('admin/plans.php')) ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---- list ----
$plans = Plan::all();
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Plans</h1>
    <a href="<?= e(url('admin/plans.php?action=new')) ?>" class="btn btn-primary btn-sm">+ New plan</a>
</div>
<div class="table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Price</th><th>Duration</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
            <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= (float) $p['price'] > 0 ? '$' . e(number_format((float) $p['price'], 2)) : 'Free' ?></td>
                <td><?= (int) $p['duration_days'] ?> days</td>
                <td><?= (int) $p['is_active'] ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">inactive</span>' ?></td>
                <td><div class="row-actions">
                    <a href="<?= e(url('admin/plans.php?action=edit&id=' . $p['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="post" action="<?= e(url('admin/plans.php')) ?>" onsubmit="return confirm('Delete this plan?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$plans): ?><tr><td colspan="5" class="muted">No plans yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

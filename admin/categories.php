<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();

$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    if ($op === 'reorder') {
        // Drag-and-drop reorder: sort_order follows the dropped position.
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        foreach ($ids as $i => $cid) {
            if ($cid > 0) {
                db_run('UPDATE categories SET sort_order = ? WHERE id = ?', [$i, $cid]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'count' => count($ids)]);
        exit;
    }

    if ($op === 'delete') {
        Category::delete((int) $_POST['id']);
        flash('success', 'Category deleted.');
        redirect('admin/categories.php');
    }

    if ($op === 'save') {
        $editId = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '') {
            $slug = slugify($name);
        }
        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'icon'       => trim($_POST['icon'] ?? '') ?: null,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($name === '' || $slug === '') {
            flash('error', 'Name is required.');
            redirect('admin/categories.php?action=' . ($editId ? 'edit&id=' . $editId : 'new'));
        }
        try {
            if ($editId) {
                Category::update($editId, $data);
                flash('success', 'Category updated.');
            } else {
                Category::create($data);
                flash('success', 'Category created.');
            }
            redirect('admin/categories.php');
        } catch (PDOException $ex) {
            flash('error', 'Could not save (is the slug unique?).');
            redirect('admin/categories.php?action=' . ($editId ? 'edit&id=' . $editId : 'new'));
        }
    }
}

$adminTitle = 'Categories';
$activeNav  = 'categories';

if ($action === 'new' || $action === 'edit') {
    $cat = $action === 'edit' ? Category::find($id) : null;
    if ($action === 'edit' && !$cat) {
        flash('error', 'Category not found.');
        redirect('admin/categories.php');
    }
    require __DIR__ . '/includes/header.php';
    ?>
    <h1><?= $action === 'edit' ? 'Edit' : 'New' ?> category</h1>
    <div class="admin-form">
        <form method="post" action="<?= e(url('admin/categories.php')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="id" value="<?= (int) ($cat['id'] ?? 0) ?>">
            <label>Name <input type="text" name="name" value="<?= e($cat['name'] ?? '') ?>" required autofocus></label>
            <label>Slug <input type="text" name="slug" value="<?= e($cat['slug'] ?? '') ?>" placeholder="auto from name if blank"></label>
            <div class="row2">
                <label>Icon (optional URL) <input type="text" name="icon" value="<?= e($cat['icon'] ?? '') ?>"></label>
                <label>Sort order <input type="number" name="sort_order" value="<?= (int) ($cat['sort_order'] ?? 0) ?>"></label>
            </div>
            <label class="check"><input type="checkbox" name="is_active" <?= (int) ($cat['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
            <div class="form-actions">
                <button class="btn btn-primary">Save</button>
                <a href="<?= e(url('admin/categories.php')) ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---- list ----
$categories = Category::all();
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Categories</h1>
    <a href="<?= e(url('admin/categories.php?action=new')) ?>" class="btn btn-primary btn-sm">+ New category</a>
</div>
<p class="muted" style="margin:0 0 8px;font-size:13px;"><strong>Tip:</strong> drag the <span style="letter-spacing:-2px;">⠿</span> handle to reorder — this sets the order categories appear on the home page.</p>
<div class="table-wrap">
    <table class="data">
        <thead><tr><th style="width:28px;"></th><th>Name</th><th>Slug</th><th>Order</th><th>Status</th><th></th></tr></thead>
        <tbody id="catBody">
        <?php foreach ($categories as $c): ?>
            <tr class="cat-row" draggable="true" data-id="<?= (int) $c['id'] ?>">
                <td class="drag-handle" title="Drag to reorder">⠿</td>
                <td><?= e($c['name']) ?></td>
                <td class="muted"><?= e($c['slug']) ?></td>
                <td><?= (int) $c['sort_order'] ?></td>
                <td><?= (int) $c['is_active'] ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">hidden</span>' ?></td>
                <td><div class="row-actions">
                    <a href="<?= e(url('admin/categories.php?action=edit&id=' . $c['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="post" action="<?= e(url('admin/categories.php')) ?>" data-confirm="Delete this category and all its channels? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$categories): ?><tr><td colspan="6" class="muted">No categories yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<script>
(function () {
    var body = document.getElementById('catBody');
    if (!body) { return; }
    var dragEl = null;
    var url = <?= json_encode(url('admin/categories.php')) ?>;

    body.addEventListener('dragstart', function (e) {
        var tr = e.target.closest('tr.cat-row');
        if (!tr) { e.preventDefault(); return; }
        dragEl = tr;
        tr.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', tr.dataset.id); } catch (_) {}
    });
    body.addEventListener('dragover', function (e) {
        if (!dragEl) { return; }
        e.preventDefault();
        var tr = e.target.closest('tr.cat-row');
        if (!tr || tr === dragEl) { return; }
        var rect = tr.getBoundingClientRect();
        var after = (e.clientY - rect.top) > rect.height / 2;
        body.insertBefore(dragEl, after ? tr.nextSibling : tr);
    });
    body.addEventListener('drop', function (e) { e.preventDefault(); });
    body.addEventListener('dragend', function () {
        if (!dragEl) { return; }
        dragEl.classList.remove('dragging');
        dragEl = null;
        var tokenEl = document.querySelector('#catBody form input[name=_csrf]') || document.querySelector('input[name=_csrf]');
        var fd = new FormData();
        fd.append('op', 'reorder');
        fd.append('_csrf', tokenEl ? tokenEl.value : '');
        body.querySelectorAll('tr.cat-row').forEach(function (tr) { fd.append('ids[]', tr.dataset.id); });
        body.style.opacity = '.6';
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function () { body.style.opacity = '1'; })
            .catch(function () { body.style.opacity = '1'; });
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

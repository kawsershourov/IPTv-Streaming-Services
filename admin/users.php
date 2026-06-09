<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    // Create a new user (no target row).
    if ($op === 'create') {
        $name     = trim($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['user', 'editor', 'admin'], true) ? $_POST['role'] : 'user';

        if ($name === '' || $email === '' || $password === '') {
            flash('error', 'Name, email and password are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
        } elseif (strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters.');
        } elseif (User::findByEmail($email)) {
            flash('error', 'An account with that email already exists.');
        } else {
            User::create($name, $email, password_hash($password, PASSWORD_BCRYPT), $role);
            flash('success', $name . ' created as ' . $role . '.');
        }
        redirect('admin/users.php');
    }

    $userId = (int) ($_POST['id'] ?? 0);
    $target = User::find($userId);

    if (!$target) {
        flash('error', 'User not found.');
        redirect('admin/users.php');
    }

    if ($op === 'role') {
        $newRole = in_array($_POST['role'] ?? '', ['user', 'editor', 'admin'], true) ? $_POST['role'] : null;
        if ($userId === (int) $me['id']) {
            flash('error', 'You cannot change your own role.');
        } elseif (!$newRole) {
            flash('error', 'Invalid role.');
        } else {
            User::setRole($userId, $newRole);
            flash('success', $target['name'] . ' is now ' . $newRole . '.');
        }
    } elseif ($op === 'status') {
        if ($userId === (int) $me['id']) {
            flash('error', 'You cannot suspend your own account.');
        } else {
            User::setStatus($userId, $target['status'] === 'active' ? 'suspended' : 'active');
            flash('success', 'Status updated for ' . $target['name'] . '.');
        }
    } elseif ($op === 'grant') {
        $planId = (int) ($_POST['plan_id'] ?? 0);
        if ($planId && Plan::find($planId)) {
            Subscription::grant($userId, $planId);
            flash('success', 'Subscription granted to ' . $target['name'] . '.');
        } else {
            flash('error', 'Choose a valid plan.');
        }
    } elseif ($op === 'cancel_sub') {
        Subscription::cancel($userId);
        flash('success', 'Subscription cancelled for ' . $target['name'] . '.');
    }
    redirect('admin/users.php');
}

$users = User::all();
$plans = Plan::all();

$adminTitle = 'Users';
$activeNav  = 'users';
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Users</h1>
    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('newUser').style.display='block';this.style.display='none';">+ New user</button>
</div>

<div class="admin-form" id="newUser" style="display:none;margin-bottom:18px;">
    <h2 style="font-size:16px;margin:0 0 12px;">Create user</h2>
    <form method="post" action="<?= e(url('admin/users.php')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="create">
        <div class="row2">
            <label>Name <input type="text" name="name" required></label>
            <label>Email <input type="email" name="email" required></label>
        </div>
        <div class="row2">
            <label>Password <input type="password" name="password" required minlength="6"></label>
            <label>Role
                <select name="role">
                    <option value="user">user</option>
                    <option value="editor">editor</option>
                    <option value="admin">admin</option>
                </select>
            </label>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary">Create user</button>
            <button type="button" class="btn btn-outline" onclick="document.getElementById('newUser').style.display='none';">Cancel</button>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Subscription</th><th>Assign plan</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <?php $sub = Subscription::activeForUser((int) $u['id']); $self = (int) $u['id'] === (int) $me['id']; ?>
            <tr>
                <td><?= e($u['name']) ?><?= $self ? ' <span class="muted">(you)</span>' : '' ?></td>
                <td class="muted"><?= e($u['email']) ?></td>
                <td>
                    <?php if ($self): ?>
                        <span class="tag tag-prem"><?= e($u['role']) ?></span>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('admin/users.php')) ?>" style="display:flex;gap:6px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="op" value="role">
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <select name="role" class="mini-select" onchange="this.form.submit()">
                                <?php foreach (['user', 'editor', 'admin'] as $r): ?>
                                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </td>
                <td><?= $u['status'] === 'active' ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">suspended</span>' ?></td>
                <td>
                    <?php if ($sub): ?>
                        <?= e($sub['plan_name']) ?> <span class="muted">· ends <?= e(fmt_date($sub['ends_at'])) ?></span>
                    <?php else: ?><span class="muted">—</span><?php endif; ?>
                </td>
                <td>
                    <form method="post" action="<?= e(url('admin/users.php')) ?>" style="display:flex;gap:6px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="op" value="grant">
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <select name="plan_id" class="mini-select" required>
                            <option value="">plan…</option>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary btn-sm">Grant</button>
                    </form>
                </td>
                <td><div class="row-actions">
                    <?php if (!$self): ?>
                        <form method="post" action="<?= e(url('admin/users.php')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="op" value="status"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button class="btn btn-danger btn-sm"><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                        </form>
                    <?php else: ?><span class="muted">—</span><?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

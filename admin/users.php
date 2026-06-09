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

    // Bulk: set the role for all checked users.
    if ($op === 'bulk_role') {
        $ids  = array_map('intval', (array) ($_POST['ids'] ?? []));
        $role = in_array($_POST['bulk_role'] ?? '', ['user', 'editor', 'admin'], true) ? $_POST['bulk_role'] : null;
        if (!$ids) {
            flash('error', 'Select at least one user (tick the checkboxes).');
        } elseif (!$role) {
            flash('error', 'Choose a role to apply.');
        } else {
            $n = 0;
            foreach ($ids as $id) {
                if ($id === (int) $me['id'] || !User::find($id)) {
                    continue; // skip yourself and missing users
                }
                User::setRole($id, $role);
                $n++;
            }
            flash('success', "Set {$n} user(s) to {$role}.");
        }
        redirect('admin/users.php');
    }

    // Bulk: delete all checked users (never yourself; never the last admin).
    if ($op === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        if (!$ids) {
            flash('error', 'Select at least one user to delete.');
            redirect('admin/users.php');
        }
        $n = 0;
        $admins = User::countAdmins();
        foreach ($ids as $id) {
            if ($id === (int) $me['id'] || !($u = User::find($id))) {
                continue;
            }
            if ($u['role'] === 'admin' && $admins <= 1) {
                continue;
            }
            if ($u['role'] === 'admin') {
                $admins--;
            }
            User::delete($id);
            $n++;
        }
        flash('success', "Deleted {$n} user(s).");
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
    } elseif ($op === 'delete') {
        if ($userId === (int) $me['id']) {
            flash('error', 'You cannot delete your own account.');
        } elseif ($target['role'] === 'admin' && User::countAdmins() <= 1) {
            flash('error', 'You cannot delete the last admin.');
        } else {
            User::delete($userId);
            flash('success', $target['name'] . ' deleted.');
        }
    }
    redirect('admin/users.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$users = User::all();
if ($q !== '') {
    $needle = mb_strtolower($q);
    $users = array_values(array_filter($users, static fn ($u) =>
        mb_strpos(mb_strtolower($u['name'] . ' ' . $u['email'] . ' ' . $u['role']), $needle) !== false));
}
$plans = Plan::all();

// AJAX live-search: return only the table rows.
if (isset($_GET['ajax'])) {
    require __DIR__ . '/_users_rows.php';
    exit;
}

$adminTitle = 'Users';
$activeNav  = 'users';
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Users</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <form method="get" action="<?= e(url('admin/users.php')) ?>" class="search-box">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name / email…">
            <button class="btn btn-outline btn-sm">Search</button>
            <?php if ($q !== ''): ?><a href="<?= e(url('admin/users.php')) ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
        </form>
        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('newUser').style.display='block';this.style.display='none';">+ New user</button>
    </div>
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

<!-- Bulk role: checkboxes in the table below are linked via form="bulkUserForm" -->
<form method="post" action="<?= e(url('admin/users.php')) ?>" id="bulkUserForm" class="bulk-bar">
    <?= csrf_field() ?>
    <span class="muted">With selected:</span>
    <select name="bulk_role" class="mini-select">
        <option value="">set role…</option>
        <option value="user">user</option>
        <option value="editor">editor</option>
        <option value="admin">admin</option>
    </select>
    <button type="submit" name="op" value="bulk_role" class="btn btn-primary btn-sm">Apply role</button>
    <button type="submit" name="op" value="bulk_delete" class="btn btn-danger btn-sm">Delete selected</button>
</form>

<div class="table-wrap">
    <table class="data">
        <thead><tr>
            <th style="width:34px;"><input type="checkbox" id="selectAllUsers" title="Select all"></th>
            <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Subscription</th><th>Assign plan</th><th></th>
        </tr></thead>
        <tbody id="usersBody">
        <?php require __DIR__ . '/_users_rows.php'; ?>
        </tbody>
    </table>
</div>
<script>
(function () {
    var all = document.getElementById('selectAllUsers');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.user-check').forEach(function (b) { b.checked = all.checked; });
        });
    }

    // AJAX live search: swap the table rows as you type.
    var sform = document.querySelector('.search-box');
    var sinput = sform ? sform.querySelector('input[name=q]') : null;
    var body = document.getElementById('usersBody');
    if (sform && sinput && body) {
        var t, base = sform.getAttribute('action');
        function run() {
            body.style.opacity = '.5';
            fetch(base + '?q=' + encodeURIComponent(sinput.value) + '&ajax=1', { credentials: 'same-origin' })
                .then(function (r) { return r.text(); })
                .then(function (html) { body.innerHTML = html; body.style.opacity = '1'; if (all) all.checked = false; });
        }
        sinput.addEventListener('input', function () { clearTimeout(t); t = setTimeout(run, 250); });
        sform.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(t); run(); });
    }

    // Confirm before applying a bulk action (uses the custom modal).
    var bulk = document.getElementById('bulkUserForm');
    if (bulk) {
        bulk.addEventListener('submit', function (e) {
            if (bulk.dataset.confirmed === '1') { return; }
            e.preventDefault();
            var op = e.submitter ? e.submitter.value : 'bulk_role';
            var n = document.querySelectorAll('.user-check:checked').length;
            if (!n) { alert('Tick at least one user first.'); return; }
            var go = function () {
                var h = document.createElement('input'); h.type = 'hidden'; h.name = 'op'; h.value = op; bulk.appendChild(h);
                bulk.dataset.confirmed = '1'; bulk.submit();
            };
            if (op === 'bulk_delete') {
                window.spConfirm('Delete ' + n + ' selected user(s)? This cannot be undone.', go);
                return;
            }
            var role = bulk.querySelector('[name=bulk_role]').value;
            if (!role) { alert('Choose a role to apply.'); return; }
            window.spConfirm('Set ' + n + ' selected user(s) to "' + role + '"?', go, { title: 'Apply role', ok: 'Apply' });
        });
    }
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

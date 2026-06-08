<?php
require __DIR__ . '/../app/bootstrap.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    [$ok, $err] = attempt_login($email, $_POST['password'] ?? '');
    if ($ok && is_admin()) {
        flash('success', 'Welcome back.');
        redirect('admin/index.php');
    }
    if ($ok && !is_admin()) {
        // Authenticated but not an admin — drop the session.
        logout_user();
        session_start();
        $errors[] = 'That account does not have admin access.';
    } else {
        $errors[] = $err ?? 'Login failed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — SunPlex</title>
    <?= favicon_tag() ?>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<div class="admin-login-wrap">
    <div class="auth-card">
        <h1><span class="brand-sun">Sun</span><span class="brand-plex">Plex</span> Admin</h1>
        <?php foreach ($errors as $err): ?>
            <p class="flash flash-error"><?= e($err) ?></p>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('admin/login.php')) ?>" class="form">
            <?= csrf_field() ?>
            <label>Email <input type="email" name="email" value="<?= e($email) ?>" required autofocus></label>
            <label>Password <input type="password" name="password" required></label>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
    </div>
</div>
</body>
</html>

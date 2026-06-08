<?php
require __DIR__ . '/app/bootstrap.php';

if (auth_check()) {
    redirect('account.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    [$ok, $err] = attempt_login($email, $password);
    if ($ok) {
        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);
        flash('success', 'Logged in successfully.');
        redirect($intended ?: url('index.php'));
    }
    $errors[] = $err;
}

$pageTitle = 'Login';
require __DIR__ . '/app/includes/header.php';
?>
<div class="auth-card">
    <h1>Log in to SunPlex</h1>

    <?php foreach ($errors as $err): ?>
        <p class="flash flash-error"><?= e($err) ?></p>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url('login.php')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" value="<?= e($email) ?>" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <p class="auth-alt">
        <a href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a>
        &nbsp;&middot;&nbsp;
        New here? <a href="<?= e(url('register.php')) ?>">Create an account</a>
    </p>
</div>
<?php require __DIR__ . '/app/includes/footer.php'; ?>

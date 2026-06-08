<?php
require __DIR__ . '/app/bootstrap.php';

if (auth_check()) {
    redirect('account.php');
}

$registrationOpen = Setting::get('registration_open', '1') === '1';
$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!$registrationOpen) {
        $errors[] = 'Registration is currently closed.';
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (!$errors) {
            [$userId, $err] = register_user($name, $email, $password);
            if ($err) {
                $errors[] = $err;
            } else {
                // Log the new user straight in.
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                flash('success', 'Welcome to SunPlex, ' . $name . '!');
                redirect('index.php');
            }
        }
    }
}

$pageTitle = 'Sign Up';
require __DIR__ . '/app/includes/header.php';
?>
<div class="auth-card">
    <h1>Create your account</h1>

    <?php if (!$registrationOpen): ?>
        <p class="flash flash-error">Registration is currently closed. Please check back later.</p>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <p class="flash flash-error"><?= e($err) ?></p>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url('register.php')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Full name
            <input type="text" name="name" value="<?= e($name) ?>" required autofocus>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>Password
            <input type="password" name="password" required minlength="6">
        </label>
        <label>Confirm password
            <input type="password" name="password_confirm" required minlength="6">
        </label>
        <button type="submit" class="btn btn-primary btn-block" <?= $registrationOpen ? '' : 'disabled' ?>>
            Sign Up
        </button>
    </form>

    <p class="auth-alt">Already have an account? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/app/includes/footer.php'; ?>

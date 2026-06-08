<?php
require __DIR__ . '/app/bootstrap.php';

if (auth_check()) {
    redirect('account.php');
}

$notice = null;
$resetLink = null; // shown only in debug mode (no SMTP locally)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            db_run('DELETE FROM password_resets WHERE email = ?', [$email]);
            db_run('INSERT INTO password_resets (email, token) VALUES (?, ?)', [$email, $token]);

            $link = url('reset-password.php?token=' . urlencode($token));
            if (config('debug')) {
                $resetLink = $link;
            }
            // In production this is where the link would be emailed to $email.
        }
    }
    $notice = 'If an account exists for that email, a password-reset link has been generated.';
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/app/includes/header.php';
?>
<div class="auth-card">
    <h1>Reset your password</h1>

    <?php if ($notice): ?>
        <p class="flash flash-success"><?= e($notice) ?></p>
    <?php endif; ?>

    <?php if ($resetLink): ?>
        <p class="flash flash-info">
            <strong>Dev mode:</strong> email is not configured locally, so use this link:<br>
            <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
        </p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('forgot-password.php')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" required autofocus>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>

    <p class="auth-alt"><a href="<?= e(url('login.php')) ?>">Back to login</a></p>
</div>
<?php require __DIR__ . '/app/includes/footer.php'; ?>

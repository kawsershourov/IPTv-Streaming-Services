<?php
require __DIR__ . '/app/bootstrap.php';

if (auth_check()) {
    redirect('account.php');
}

const RESET_TTL_SECONDS = 3600; // 1 hour

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$token = is_string($token) ? $token : '';
$errors = [];

/** Find a non-expired reset row for the token, or null. */
function find_reset(string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $row = db_one('SELECT * FROM password_resets WHERE token = ?', [$token]);
    if (!$row) {
        return null;
    }
    if (strtotime($row['created_at']) < time() - RESET_TTL_SECONDS) {
        db_run('DELETE FROM password_resets WHERE id = ?', [$row['id']]);
        return null;
    }
    return $row;
}

$reset = find_reset($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!$reset) {
        $errors[] = 'This reset link is invalid or has expired.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $user = User::findByEmail($reset['email']);
            if ($user) {
                User::updatePassword((int) $user['id'], password_hash($password, PASSWORD_BCRYPT));
            }
            db_run('DELETE FROM password_resets WHERE email = ?', [$reset['email']]);
            flash('success', 'Password updated. You can now log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Set New Password';
require __DIR__ . '/app/includes/header.php';
?>
<div class="auth-card">
    <h1>Set a new password</h1>

    <?php foreach ($errors as $err): ?>
        <p class="flash flash-error"><?= e($err) ?></p>
    <?php endforeach; ?>

    <?php if (!$reset): ?>
        <p class="flash flash-error">This reset link is invalid or has expired.</p>
        <p class="auth-alt"><a href="<?= e(url('forgot-password.php')) ?>">Request a new link</a></p>
    <?php else: ?>
        <form method="post" action="<?= e(url('reset-password.php')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>New password
                <input type="password" name="password" required minlength="6" autofocus>
            </label>
            <label>Confirm password
                <input type="password" name="password_confirm" required minlength="6">
            </label>
            <button type="submit" class="btn btn-primary btn-block">Update password</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/app/includes/footer.php'; ?>

<?php
declare(strict_types=1);

/**
 * Authentication: registration, login/logout, current-user, route guards.
 */

/** The logged-in user row, or null. Cached per request (reset via auth_reset_cache()). */
function current_user(): ?array
{
    if (array_key_exists('__current_user', $GLOBALS)) {
        return $GLOBALS['__current_user'];
    }

    $user = null;
    $id = $_SESSION['user_id'] ?? null;
    if ($id) {
        $user = User::find((int) $id);
        // Drop the session if the account vanished or was suspended.
        if (!$user || $user['status'] !== 'active') {
            $user = null;
            unset($_SESSION['user_id']);
        }
    }

    $GLOBALS['__current_user'] = $user;
    return $user;
}

/** Invalidate the cached current user (after login/logout within one request). */
function auth_reset_cache(): void
{
    unset($GLOBALS['__current_user']);
}

function auth_check(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

/** Staff = admin or editor (can reach the admin panel; editors are content-only). */
function is_staff(): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], ['admin', 'editor'], true);
}

/** Attempt login. Returns [ok(bool), error(string|null)]. */
function attempt_login(string $email, string $password): array
{
    $user = User::findByEmail($email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return [false, 'Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        return [false, 'This account is suspended.'];
    }

    // Rotate session id on privilege change to prevent fixation.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    auth_reset_cache();
    return [true, null];
}

/** Register a new user. Returns [userId(int)|null, error(string|null)]. */
function register_user(string $name, string $email, string $password): array
{
    $name  = trim($name);
    $email = strtolower(trim($email));

    if ($name === '' || $email === '' || $password === '') {
        return [null, 'All fields are required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [null, 'Please enter a valid email address.'];
    }
    if (strlen($password) < 6) {
        return [null, 'Password must be at least 6 characters.'];
    }
    if (User::findByEmail($email)) {
        return [null, 'An account with that email already exists.'];
    }

    $id = User::create($name, $email, password_hash($password, PASSWORD_BCRYPT));
    return [$id, null];
}

function logout_user(): void
{
    auth_reset_cache();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Require an authenticated user, else redirect to login (remembering target). */
function require_login(): void
{
    if (!auth_check()) {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? url('');
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

/** Require an admin user, else redirect to admin login. */
function require_admin(): void
{
    if (!is_admin()) {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('error', 'Admin access required.');
        redirect('admin/login.php');
    }
}

/** Require staff (admin or editor), else redirect to admin login. */
function require_staff(): void
{
    if (!is_staff()) {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('error', 'Staff access required.');
        redirect('admin/login.php');
    }
}

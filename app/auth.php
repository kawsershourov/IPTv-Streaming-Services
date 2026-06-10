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

/* --------------------------------------------------------------------- */
/* Brute-force login throttling                                           */
/* --------------------------------------------------------------------- */

const LOGIN_MAX_ATTEMPTS = 6;   // failures allowed per IP or email …
const LOGIN_WINDOW_MIN   = 15;  // … within this many minutes

/**
 * Real client IP. Uses REMOTE_ADDR (the actual TCP peer — cannot be spoofed) by
 * default. Forwarded headers (Cloudflare CF-Connecting-IP, X-Forwarded-For) are
 * client-controlled on direct hosting, so they are ONLY trusted when the site is
 * explicitly marked as behind a proxy/CDN via the 'trust_proxy' setting.
 * Without this, anyone could send X-Forwarded-For to spoof an allowed IP.
 */
function client_ip(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (class_exists('Setting') && Setting::get('trust_proxy', '0') === '1') {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $first = trim(explode(',', $xff)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
    }

    return $remote;
}

/** Are logins currently throttled for this IP or email? */
function login_is_throttled(string $email): bool
{
    $win = LOGIN_WINDOW_MIN;
    $row = db_one(
        "SELECT COUNT(*) AS c FROM login_attempts
          WHERE (ip = ? OR email = ?) AND created_at > (NOW() - INTERVAL $win MINUTE)",
        [client_ip(), strtolower($email)]
    );
    return ((int) ($row['c'] ?? 0)) >= LOGIN_MAX_ATTEMPTS;
}

function login_record_failure(string $email): void
{
    db_run('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [client_ip(), strtolower($email)]);
    // Opportunistic cleanup of rows older than a day.
    db_run('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}

function login_clear_failures(string $email): void
{
    db_run('DELETE FROM login_attempts WHERE ip = ? OR email = ?', [client_ip(), strtolower($email)]);
}

/** Attempt login. Returns [ok(bool), error(string|null)]. Throttled + fixation-safe. */
function attempt_login(string $email, string $password): array
{
    if (login_is_throttled($email)) {
        return [false, 'Too many failed attempts. Please wait about 15 minutes and try again.'];
    }

    $user = User::findByEmail($email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        login_record_failure($email);
        return [false, 'Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        login_record_failure($email);
        return [false, 'This account is suspended.'];
    }

    // Rotate session id on privilege change to prevent fixation.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    auth_reset_cache();
    login_clear_failures($email);
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

<?php
declare(strict_types=1);

/**
 * Application bootstrap — included at the top of every front-end and admin page.
 * Loads config, starts the session, and wires up db / helpers / models / auth.
 */

define('APP_DIR', __DIR__);
define('BASE_DIR', dirname(__DIR__));

// --- Config -------------------------------------------------------------
$configFile = APP_DIR . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing app/config.php — copy app/config.sample.php to app/config.php and set your DB credentials.');
}
$GLOBALS['__config'] = require $configFile;

require_once APP_DIR . '/helpers.php';

// --- Errors / timezone --------------------------------------------------
date_default_timezone_set((string) config('site.timezone', 'UTC'));
if (config('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // Production: never leak errors to visitors; log them instead.
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// --- Session (hardened cookies) ----------------------------------------
$https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
      || (($_SERVER['SERVER_PORT'] ?? '') == 443);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name((string) config('security.session_name', 'sunplex_sess'));
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,      // only sent over HTTPS in production
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Data layer + domain ------------------------------------------------
require_once APP_DIR . '/db.php';
require_once APP_DIR . '/models/User.php';
require_once APP_DIR . '/models/Category.php';
require_once APP_DIR . '/models/Channel.php';
require_once APP_DIR . '/models/Plan.php';
require_once APP_DIR . '/models/Subscription.php';
require_once APP_DIR . '/models/Setting.php';
require_once APP_DIR . '/models/Media.php';
require_once APP_DIR . '/auth.php';
require_once APP_DIR . '/access.php';
require_once APP_DIR . '/player.php';
require_once APP_DIR . '/stats.php';
require_once APP_DIR . '/mailer.php';
require_once APP_DIR . '/health.php';
require_once APP_DIR . '/geo.php';

// Email the admin about fatal errors (throttled, production only — see notify_site_error).
register_shutdown_function(static function (): void {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        notify_site_error($e);
    }
});

// --- Geo / IP access control (no-op unless enabled in Admin → Access) ----
geo_guard();

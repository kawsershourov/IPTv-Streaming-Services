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
    ini_set('display_errors', '0');
}

// --- Session ------------------------------------------------------------
session_name((string) config('security.session_name', 'sunplex_sess'));
if (session_status() !== PHP_SESSION_ACTIVE) {
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
require_once APP_DIR . '/auth.php';
require_once APP_DIR . '/access.php';

<?php
/**
 * SunPlex.live — configuration sample.
 *
 * Copy this file to app/config.php and adjust for your environment.
 * app/config.php is git-ignored so real credentials are never committed.
 */

return [
    // --- Database (XAMPP defaults: user "root", empty password) ---
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'sunplex',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // --- Site ---
    'site' => [
        // Base URL path the app is served from (no trailing slash).
        // For http://localhost/SunPlex.live/ use "/SunPlex.live".
        'base_url'     => '/SunPlex.live',
        'name'         => 'SunPlex',
        'tagline'      => 'Live TV & Streaming',
        'timezone'     => 'Asia/Dhaka',
        'default_skin' => 'minimal_skin_dark',
    ],

    // --- Security ---
    'security' => [
        // Change this to a long random string in production.
        'app_key'        => 'change-me-to-a-long-random-string',
        'session_name'   => 'sunplex_sess',
    ],

    // Development flag: shows password-reset tokens on screen (no SMTP locally).
    'debug' => true,
];

<?php
/**
 * SunPlex.live — PRODUCTION config (cPanel).
 * Fill in your cPanel database details below. This file is copied to
 * app/config.php inside the _deploy build.
 */

return [
    // --- Database (from cPanel → MySQL Databases) ---
    'db' => [
        'host'    => 'localhost',          // cPanel MySQL host is almost always localhost
        'port'    => 3306,
        'name'    => 'CPANELUSER_sunplex', // the database you created in cPanel
        'user'    => 'CPANELUSER_dbuser',  // the DB user you created
        'pass'    => 'YOUR_DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],

    // --- Site ---
    'site' => [
        'base_url'     => '',               // domain root (https://yourdomain.com/)
        'name'         => 'SunPlex',
        'tagline'      => 'Live TV & Streaming',
        'timezone'     => 'Asia/Dhaka',
        'default_skin' => 'minimal_skin_dark',
    ],

    // --- Security ---
    'security' => [
        'app_key'      => '__APP_KEY__',    // replaced with a random value by build-deploy
        'session_name' => 'sunplex_sess',
    ],

    // Production: keep errors hidden from visitors.
    'debug' => false,
];

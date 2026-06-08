<?php

//WordPress when this script is requested directly.
if (!defined('ABSPATH')) {
   $fwduvp_wp_load = dirname(__DIR__, 4) . '/wp-load.php';
   if (file_exists($fwduvp_wp_load)) {
      require_once $fwduvp_wp_load;
   }
}

if (!defined('ABSPATH')) {
   header('HTTP/1.1 403 Forbidden');
   exit;
}

// Collect and sanitize all user input early.
$fwduvp_nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
$fwduvp_name = isset($_GET['name']) ? sanitize_file_name(wp_unslash($_GET['name'])) : '';
$fwduvp_path = isset($_GET['path']) ? sanitize_text_field(wp_unslash($_GET['path'])) : '';

// If a nonce is provided, enforce it.
if (!empty($fwduvp_nonce) && !wp_verify_nonce($fwduvp_nonce, 'fwduvp_download_video')) {
   status_header(403);
   exit;
}

if (empty($fwduvp_name) || empty($fwduvp_path)) {
   status_header(400);
   exit;
}

// Allow downloads only for explicit media extensions.
$fwduvp_name_ext = strtolower((string) pathinfo($fwduvp_name, PATHINFO_EXTENSION));
if (!in_array($fwduvp_name_ext, array('mp3', 'mp4'), true)) {
   status_header(400);
   exit;
}

// Normalize incoming URL/path string to reduce encoding edge cases.
$fwduvp_path = rawurldecode($fwduvp_path);
$fwduvp_path = str_replace('\\', '/', $fwduvp_path);

// Build a same-site absolute URL when a relative path is passed.
$fwduvp_is_absolute_url = (bool) wp_http_validate_url($fwduvp_path);
if (!$fwduvp_is_absolute_url) {
   $fwduvp_path = home_url('/' . ltrim($fwduvp_path, '/'));
}

if (!wp_http_validate_url($fwduvp_path)) {
   status_header(400);
   exit;
}

// Reject external domains to prevent SSRF.
$fwduvp_request_host = wp_parse_url($fwduvp_path, PHP_URL_HOST);
$fwduvp_site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
if (empty($fwduvp_request_host) || empty($fwduvp_site_host) || strtolower($fwduvp_request_host) !== strtolower($fwduvp_site_host)) {
   status_header(403);
   exit;
}

$fwduvp_request_path = (string) wp_parse_url($fwduvp_path, PHP_URL_PATH);
$fwduvp_site_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
$fwduvp_request_path = wp_normalize_path($fwduvp_request_path);
$fwduvp_site_path = wp_normalize_path($fwduvp_site_path);

// Ensure URL path starts within the site's path prefix.
if (!empty($fwduvp_site_path) && '/' !== $fwduvp_site_path && strpos($fwduvp_request_path, rtrim($fwduvp_site_path, '/')) !== 0) {
   status_header(403);
   exit;
}

// Map URL path to a local filesystem path under ABSPATH.
$fwduvp_relative_path = ltrim((string) substr($fwduvp_request_path, strlen(rtrim($fwduvp_site_path, '/'))), '/');
$fwduvp_candidate_path = wp_normalize_path(ABSPATH . $fwduvp_relative_path);
$fwduvp_real_path = realpath($fwduvp_candidate_path);
$fwduvp_real_path = $fwduvp_real_path ? wp_normalize_path($fwduvp_real_path) : '';
$fwduvp_abs_root = wp_normalize_path(ABSPATH);

// Block traversal and non-existing files.
if (empty($fwduvp_real_path) || strpos($fwduvp_real_path, $fwduvp_abs_root) !== 0 || !is_file($fwduvp_real_path)) {
   status_header(404);
   exit;
}

$fwduvp_file_ext = strtolower((string) pathinfo($fwduvp_real_path, PATHINFO_EXTENSION));
if (!in_array($fwduvp_file_ext, array('mp3', 'mp4'), true)) {
   status_header(403);
   exit;
}

global $wp_filesystem;
if (!function_exists('WP_Filesystem')) {
   require_once ABSPATH . 'wp-admin/includes/file.php';
}

WP_Filesystem();
if (empty($wp_filesystem) || !$wp_filesystem->exists($fwduvp_real_path)) {
   status_header(404);
   exit;
}

$fwduvp_file_contents = $wp_filesystem->get_contents($fwduvp_real_path);
if (false === $fwduvp_file_contents) {
   status_header(500);
   exit;
}

// Stream the download with explicit attachment headers.
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename=' . $fwduvp_name);
header('Content-Length: ' . strlen($fwduvp_file_contents));
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw binary output is required for file download responses.
echo $fwduvp_file_contents;
exit;

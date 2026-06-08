<?php



declare(strict_types=1);

// Always return JSON and disable MIME sniffing/caching for predictable client behavior.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Hard cap used as a simple DoS guard to avoid huge directory enumerations.
const FWDUVP_PROXY_FOLDER_MAX_FILES = 500;

// This endpoint is intentionally read-only and should only be accessed via GET.
$fwduvp_request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
if ($fwduvp_request_method === null || $fwduvp_request_method === false) {
	fwduvp_proxy_folder_respond_with_error('Invalid request method.');
}

$fwduvp_request_method = function_exists('wp_unslash') ? wp_unslash((string) $fwduvp_request_method) : (string) $fwduvp_request_method;
if (strtoupper(trim($fwduvp_request_method)) !== 'GET') {
	fwduvp_proxy_folder_respond_with_error('Invalid request method.');
}

// Read user input through filter_input instead of accessing superglobals directly.
// FILTER_UNSAFE_RAW is used because we validate/path-check strictly in the next steps.
$fwduvp_raw_dir = filter_input(INPUT_GET, 'dir', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
if ($fwduvp_raw_dir === null || $fwduvp_raw_dir === false) {
	fwduvp_proxy_folder_respond_with_error('Invalid directory.');
}

$fwduvp_raw_dir = (string) $fwduvp_raw_dir;
// If WordPress is loaded, remove slashes added by wp_magic_quotes.
if (function_exists('wp_unslash')) {
	$fwduvp_raw_dir = wp_unslash($fwduvp_raw_dir);
}

// Decode URL-encoded input and normalize separators so validation behaves consistently.
$fwduvp_decoded_dir = rawurldecode($fwduvp_raw_dir);
$fwduvp_relative_dir = trim(str_replace('\\', '/', $fwduvp_decoded_dir), '/');

// Basic guardrails: no empty path, no null bytes, and a practical max length.
if ($fwduvp_relative_dir === '' || strpos($fwduvp_relative_dir, "\0") !== false || strlen($fwduvp_relative_dir) > 512) {
	fwduvp_proxy_folder_respond_with_error('Invalid directory.');
}

// Block directory traversal attempts such as ../ and path segment escapes.
if (preg_match('#(^|/)\.\.(/|$)#', $fwduvp_relative_dir)) {
	fwduvp_proxy_folder_respond_with_error('Invalid directory path.');
}

// Allow only a conservative set of characters for local relative paths.
if (!preg_match('#^[a-zA-Z0-9/_\-. ]+$#', $fwduvp_relative_dir)) {
	fwduvp_proxy_folder_respond_with_error('Directory contains invalid characters.');
}

// Disallow protocol wrappers like php://, data://, file://, etc.
if (strpos($fwduvp_relative_dir, '://') !== false) {
	fwduvp_proxy_folder_respond_with_error('Protocol wrappers are not allowed.');
}

// Resolve and normalize the plugin content base directory (this file's folder).
$fwduvp_content_base_dir = realpath(__DIR__);
if ($fwduvp_content_base_dir === false) {
	fwduvp_proxy_folder_respond_with_error('Content base path not found.');
}

$fwduvp_content_base_dir = rtrim(str_replace('\\', '/', $fwduvp_content_base_dir), '/');

// Resolve requested folder and verify it stays strictly inside content_base_dir.
// Appending '/' on both sides avoids false positives like /base2 matching /base.
$fwduvp_target_dir = realpath($fwduvp_content_base_dir . DIRECTORY_SEPARATOR . $fwduvp_relative_dir);
$fwduvp_target_dir = $fwduvp_target_dir !== false ? rtrim(str_replace('\\', '/', $fwduvp_target_dir), '/') : false;
if ($fwduvp_target_dir === false || strpos($fwduvp_target_dir . '/', $fwduvp_content_base_dir . '/') !== 0 || !is_dir($fwduvp_target_dir)) {
	fwduvp_proxy_folder_respond_with_error('Directory not allowed.');
}

// Build absolute URL base used by the player metadata response.
// Host is sanitized because HTTP_HOST is user-controlled.
$fwduvp_https_value = filter_input(INPUT_SERVER, 'HTTPS', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$fwduvp_https_value = $fwduvp_https_value === null || $fwduvp_https_value === false ? '' : (string) $fwduvp_https_value;
$fwduvp_https_value = function_exists('wp_unslash') ? wp_unslash($fwduvp_https_value) : $fwduvp_https_value;
$fwduvp_scheme = ($fwduvp_https_value !== '' && strtolower(trim($fwduvp_https_value)) !== 'off') ? 'https' : 'http';

$fwduvp_host = filter_input(INPUT_SERVER, 'HTTP_HOST', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$fwduvp_host = $fwduvp_host === null || $fwduvp_host === false ? '' : (string) $fwduvp_host;
$fwduvp_host = function_exists('wp_unslash') ? wp_unslash($fwduvp_host) : $fwduvp_host;
$fwduvp_host = function_exists('sanitize_text_field') ? sanitize_text_field($fwduvp_host) : $fwduvp_host;
$fwduvp_host = strtolower(trim($fwduvp_host));
$fwduvp_host = preg_replace('/[^a-z0-9\.\-:\[\]]/i', '', $fwduvp_host);
if ($fwduvp_host === '' || strpos($fwduvp_host, '/') !== false) {
	$fwduvp_host = 'localhost';
}
$fwduvp_script_name = filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$fwduvp_script_name = $fwduvp_script_name === null || $fwduvp_script_name === false ? '/' : (string) $fwduvp_script_name;
$fwduvp_script_name = function_exists('wp_unslash') ? wp_unslash($fwduvp_script_name) : $fwduvp_script_name;
$fwduvp_script_name = function_exists('sanitize_text_field') ? sanitize_text_field($fwduvp_script_name) : $fwduvp_script_name;
$fwduvp_script_name = str_replace('\\', '/', $fwduvp_script_name);
$fwduvp_content_url_base = rtrim($fwduvp_scheme . '://' . $fwduvp_host . rtrim(dirname($fwduvp_script_name), '/'), '/') . '/';
$fwduvp_relative_url_dir = $fwduvp_relative_dir !== '' ? $fwduvp_relative_dir . '/' : '';

// Read directory entries once; fail closed if the directory cannot be listed.
$fwduvp_files = scandir($fwduvp_target_dir);
if ($fwduvp_files === false) {
	fwduvp_proxy_folder_respond_with_error('Unable to read directory.');
}

// Collect only top-level media files expected by the player.
$fwduvp_media_files = array();
foreach ($fwduvp_files as $fwduvp_file_name) {
	if ($fwduvp_file_name === '.' || $fwduvp_file_name === '..') {
		continue;
	}

	$fwduvp_full_path = $fwduvp_target_dir . DIRECTORY_SEPARATOR . $fwduvp_file_name;
	// Skip subdirectories and non-file entries.
	if (!is_file($fwduvp_full_path)) {
		continue;
	}

	// Allowlist only supported file types.
	if (!preg_match('/\.(mp4|mp3)$/i', $fwduvp_file_name)) {
		continue;
	}

	// Preserve legacy behavior: ignore "-mobile" variants in this endpoint.
	if (stripos($fwduvp_file_name, '-mobile') !== false) {
		continue;
	}

	$fwduvp_media_files[] = $fwduvp_file_name;

	if (count($fwduvp_media_files) > FWDUVP_PROXY_FOLDER_MAX_FILES) {
		fwduvp_proxy_folder_respond_with_error('Too many media files in folder.');
	}
}

// Stable, case-insensitive sorting keeps output deterministic.
natcasesort($fwduvp_media_files);

// Build response in structured arrays (safe JSON encoding, no manual string concat).
$fwduvp_folder_entries = array();
foreach ($fwduvp_media_files as $fwduvp_file_name) {
	// Encode each filename component so spaces/special chars remain valid in URLs.
	$fwduvp_file_url = $fwduvp_content_url_base . $fwduvp_relative_url_dir . rawurlencode($fwduvp_file_name);
	// Remove extension for derived thumbnail/poster/download paths.
	$fwduvp_raw_base_url = preg_replace('/\.[^.]+$/', '', $fwduvp_file_url);
	$title = pathinfo($fwduvp_file_name, PATHINFO_FILENAME);
	$fwduvp_download_ext = strtolower((string) pathinfo($fwduvp_file_name, PATHINFO_EXTENSION));

	$fwduvp_folder_entries[] = array(
		'@attributes' => array(
			'data-video-path' => $fwduvp_file_url,
			'data-thumb-path' => $fwduvp_raw_base_url . '-thumbnail.jpg',
			'data-poster-path' => $fwduvp_raw_base_url . '-poster.jpg',
			'download-path' => $fwduvp_raw_base_url . '.' . $fwduvp_download_ext,
			'data-title' => $title,
		),
	);
}

$fwduvp_payload = array(
	'folder' => $fwduvp_folder_entries,
);

// Encode final payload and fail with explicit JSON error if encoding fails.
$fwduvp_json_payload = json_encode($fwduvp_payload);
if ($fwduvp_json_payload === false) {
	fwduvp_proxy_folder_respond_with_error('Failed to encode response.');
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON API response must be emitted as raw JSON.
printf('%s', $fwduvp_json_payload);
exit;

// Unified JSON error response helper used by all validation failures.
function fwduvp_proxy_folder_respond_with_error(string $message): void {
	http_response_code(400);
	printf('%s', json_encode(array(
		'error' => true,
		'message' => $message,
	)));
	exit;
}
<?php

declare(strict_types=1);

// Always return JSON and disable MIME sniffing/caching for predictable API behavior.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// When this file is accessed directly, WordPress functions may not be loaded yet.
// Try to locate and include wp-load.php from parent directories.
if (!function_exists('wp_parse_url') || !function_exists('wp_remote_get') || !function_exists('wp_unslash')) {
    fwduvp_proxy_maybe_bootstrap_wordpress();
}

// Allow only read-only requests for this endpoint.
$fwduvp_request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
if ($fwduvp_request_method === null || $fwduvp_request_method === false) {
    fwduvp_proxy_respond_with_error('Invalid request method.');
}

$fwduvp_request_method = function_exists('wp_unslash') ? wp_unslash((string) $fwduvp_request_method) : (string) $fwduvp_request_method;
if (strtoupper(trim($fwduvp_request_method)) !== 'GET') {
    fwduvp_proxy_respond_with_error('Invalid request method.');
}

// Read URL through filter_input (scanner-friendly) instead of raw superglobal access.
$fwduvp_raw_url = filter_input(INPUT_GET, 'url', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
if ($fwduvp_raw_url === null || $fwduvp_raw_url === false) {
    fwduvp_proxy_respond_with_error('Invalid URL.');
}

$fwduvp_raw_url = (string) $fwduvp_raw_url;
if (function_exists('wp_unslash')) {
    $fwduvp_raw_url = wp_unslash($fwduvp_raw_url);
}

$fwduvp_url = trim($fwduvp_raw_url);
if ($fwduvp_url === '' || strpos($fwduvp_url, "\0") !== false || strlen($fwduvp_url) > 2048) {
    fwduvp_proxy_respond_with_error('Invalid URL.');
}

// Validate overall URL format and explicitly allow only HTTP(S).
if (!filter_var($fwduvp_url, FILTER_VALIDATE_URL)) {
    fwduvp_proxy_respond_with_error('Invalid URL format.');
}

if (!function_exists('wp_parse_url')) {
    fwduvp_proxy_respond_with_error('WordPress URL parser is not available.');
}

$fwduvp_parts = wp_parse_url($fwduvp_url);
if (!is_array($fwduvp_parts)) {
    fwduvp_proxy_respond_with_error('Invalid URL.');
}

$fwduvp_scheme = isset($fwduvp_parts['scheme']) ? strtolower((string) $fwduvp_parts['scheme']) : '';
if ($fwduvp_scheme !== 'http' && $fwduvp_scheme !== 'https') {
    fwduvp_proxy_respond_with_error('Only HTTP and HTTPS URLs are allowed.');
}

if (isset($fwduvp_parts['user']) || isset($fwduvp_parts['pass'])) {
    fwduvp_proxy_respond_with_error('User credentials in URL are not allowed.');
}

$fwduvp_host = isset($fwduvp_parts['host']) ? strtolower((string) $fwduvp_parts['host']) : '';
if ($fwduvp_host === '') {
    fwduvp_proxy_respond_with_error('URL host is missing.');
}

// Block direct localhost-style hostnames up front.
$fwduvp_blocked_hosts = array('localhost', '127.0.0.1', '::1');
if (in_array($fwduvp_host, $fwduvp_blocked_hosts, true)) {
    fwduvp_proxy_respond_with_error('Access to local hosts is blocked.');
}

// Resolve DNS and ensure every resolved IP is public (SSRF protection).
$fwduvp_resolved_ips = fwduvp_proxy_resolve_host_ips($fwduvp_host);
if (empty($fwduvp_resolved_ips)) {
    fwduvp_proxy_respond_with_error('Unable to resolve host.');
}

foreach ($fwduvp_resolved_ips as $fwduvp_ip_address) {
    if (!fwduvp_proxy_is_public_ip($fwduvp_ip_address)) {
        fwduvp_proxy_respond_with_error('Access to internal IPs is blocked.');
    }
}

// Optional port validation: block common local/admin service ports.
$fwduvp_port = isset($fwduvp_parts['port']) ? (int) $fwduvp_parts['port'] : 0;
$fwduvp_blocked_ports = array(0, 22, 25, 110, 143, 3306, 5432, 6379, 11211, 27017);
if ($fwduvp_port > 0 && in_array($fwduvp_port, $fwduvp_blocked_ports, true)) {
    fwduvp_proxy_respond_with_error('Requested port is not allowed.');
}

if (!function_exists('wp_remote_get')) {
    fwduvp_proxy_respond_with_error('WordPress HTTP API is not available.');
}

// Fetch remote XML with strict WordPress HTTP API options.
$fwduvp_http_response = wp_remote_get(
    $fwduvp_url,
    array(
        'timeout' => 15,
        'redirection' => 0,
        'reject_unsafe_urls' => true,
        'user-agent' => 'FWDUVP-Proxy/1.0',
        'sslverify' => true,
        'limit_response_size' => 5 * 1024 * 1024,
    )
);

if (is_wp_error($fwduvp_http_response)) {
    fwduvp_proxy_respond_with_error('Failed to fetch content.');
}

$fwduvp_response_body = wp_remote_retrieve_body($fwduvp_http_response);
$fwduvp_http_code = (int) wp_remote_retrieve_response_code($fwduvp_http_response);

if ($fwduvp_http_code < 200 || $fwduvp_http_code >= 300) {
    fwduvp_proxy_respond_with_error('Remote server returned an invalid response.');
}

if (!is_string($fwduvp_response_body) || $fwduvp_response_body === '' || strlen($fwduvp_response_body) > 5 * 1024 * 1024) {
    fwduvp_proxy_respond_with_error('Invalid XML response.');
}

// Parse XML with network access disabled to prevent XXE-related fetches.
libxml_use_internal_errors(true);
$fwduvp_xml = simplexml_load_string($fwduvp_response_body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
if ($fwduvp_xml === false) {
    libxml_clear_errors();
    fwduvp_proxy_respond_with_error('Invalid XML response.');
}
libxml_clear_errors();

$fwduvp_json_payload = json_encode($fwduvp_xml);
if ($fwduvp_json_payload === false) {
    fwduvp_proxy_respond_with_error('Failed to encode response.');
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON API response must be emitted as raw JSON.
printf('%s', $fwduvp_json_payload);
exit;

// Shared JSON error formatter for all validation and runtime failures.
function fwduvp_proxy_respond_with_error(string $message = 'General error'): void {
    http_response_code(400);
    printf('%s', json_encode(array(
        'error' => true,
        'message' => $message,
    )));
    exit;
}

// Resolve all A/AAAA records so each destination can be validated.
function fwduvp_proxy_resolve_host_ips(string $host): array {
    $ips = array();

    if (function_exists('dns_get_record')) {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP)) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ips[] = $record['ipv6'];
                }
            }
        }
    }

    // Fallback for environments where DNS_* APIs are unavailable.
    if (empty($ips)) {
        $fallback_ip = gethostbyname($host);
        if (filter_var($fallback_ip, FILTER_VALIDATE_IP)) {
            $ips[] = $fallback_ip;
        }
    }

    return array_values(array_unique($ips));
}

// Accept only public routable IP addresses.
function fwduvp_proxy_is_public_ip(string $ip): bool {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

// Attempt to load WordPress core so WP helper functions are available.
function fwduvp_proxy_maybe_bootstrap_wordpress(): void {
    $search_dir = __DIR__;

    for ($depth = 0; $depth < 8; $depth++) {
        $candidate = $search_dir . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (is_readable($candidate)) {
            require_once $candidate;
            return;
        }

        $parent_dir = dirname($search_dir);
        if ($parent_dir === $search_dir) {
            break;
        }

        $search_dir = $parent_dir;
    }
}

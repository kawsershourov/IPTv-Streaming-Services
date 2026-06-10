<?php
require __DIR__ . '/app/bootstrap.php';

$ip = client_ip();
$country = detect_country($ip);

echo "<h2>Geo-Blocking Debug</h2>";
echo "<p><strong>Your IP:</strong> " . e($ip) . "</p>";
echo "<p><strong>Detected Country:</strong> " . ($country ?? "Unknown") . "</p>";

echo "<h3>Settings</h3>";
$settings = [
    'geo_enabled' => Setting::get('geo_enabled', '0'),
    'geo_allowed_countries' => Setting::get('geo_allowed_countries', ''),
    'geo_allowed_ips' => Setting::get('geo_allowed_ips', ''),
    'geo_blocked_ips' => Setting::get('geo_blocked_ips', ''),
    'geo_use_api' => Setting::get('geo_use_api', '0'),
    'geo_block_unknown' => Setting::get('geo_block_unknown', '0'),
];

echo "<pre>" . json_encode($settings, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>Access Check</h3>";
echo "<p><strong>Geo Enabled:</strong> " . ($settings['geo_enabled'] === '1' ? '✅ YES' : '❌ NO') . "</p>";

if ($settings['geo_enabled'] === '1') {
    $allowedCountries = array_filter(preg_split('/[\s,]+/', strtoupper($settings['geo_allowed_countries'])));
    $allowedIps = $settings['geo_allowed_ips'];
    
    echo "<p><strong>Allowed Countries:</strong> " . implode(", ", $allowedCountries) . "</p>";
    echo "<p><strong>Allowed IPs:</strong> " . nl2br(e($allowedIps)) . "</p>";
    
    // Test IP match
    if ($allowedIps && ip_in_list($ip, $allowedIps)) {
        echo "<p style='color:green;'><strong>✅ Your IP is in the allowed list</strong></p>";
    } else {
        echo "<p style='color:orange;'><strong>⚠️ Your IP is NOT in the allowed list</strong></p>";
    }
    
    // Test country match
    if ($allowedCountries) {
        if ($country && in_array($country, $allowedCountries, true)) {
            echo "<p style='color:green;'><strong>✅ Your country (" . e($country) . ") is in the allowed list</strong></p>";
        } else {
            echo "<p style='color:red;'><strong>❌ Your country (" . ($country ?? "Unknown") . ") is NOT in the allowed list</strong></p>";
        }
    }
}

echo '<p><a href="admin/access.php">Back to Access Control Settings</a></p>';
?>

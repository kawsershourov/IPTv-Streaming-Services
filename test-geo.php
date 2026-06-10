<?php
require __DIR__ . '/app/bootstrap.php';
require_admin(); // diagnostics only — never expose the geo config publicly

// Simulate a test IP
$testIp = $_GET['test_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$realIp = client_ip();

// Override the IP temporarily for testing
$_SERVER['REMOTE_ADDR'] = $testIp;

echo "<!DOCTYPE html>";
echo "<html><head><title>Geo-Block Test</title>";
echo "<style>";
echo "body { font-family: Arial; margin: 20px; background: #0b0e14; color: #e8ecf3; }";
echo ".section { background: #161b27; border: 1px solid #283041; border-radius: 8px; padding: 15px; margin: 15px 0; }";
echo ".pass { background: rgba(46,160,87,.2); border-color: #76e39a; color: #76e39a; }";
echo ".fail { background: rgba(255,142,160,.2); border-color: #ff8ea0; color: #ff8ea0; }";
echo ".info { background: rgba(43,139,255,.1); border-color: #2b8bff; color: #2b8bff; }";
echo "code { background: #0b0e14; padding: 2px 6px; border-radius: 4px; }";
echo "textarea { width: 100%; padding: 8px; font-family: monospace; background: #0b0e14; color: #e8ecf3; border: 1px solid #283041; border-radius: 4px; }";
echo "</style></head><body>";

echo "<h1>Geo-Blocking Test</h1>";

// Settings
$geo_enabled = Setting::get('geo_enabled', '0');
$geo_allowed_countries = Setting::get('geo_allowed_countries', '');
$geo_allowed_ips = Setting::get('geo_allowed_ips', '');
$geo_blocked_ips = Setting::get('geo_blocked_ips', '');
$geo_use_api = Setting::get('geo_use_api', '0');
$geo_block_unknown = Setting::get('geo_block_unknown', '0');
$geo_apply_admin = Setting::get('geo_apply_admin', '0');

echo "<div class='section " . ($geo_enabled === '1' ? 'info' : 'fail') . "'>";
echo "<strong>Status:</strong> " . ($geo_enabled === '1' ? "✓ ENABLED" : "✗ DISABLED") . "<br>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>Your Real IP (from browser)</h3>";
echo "<code>" . e($realIp) . "</code>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>Allowed Countries</h3>";
if ($geo_allowed_countries) {
    echo "<code>" . e($geo_allowed_countries) . "</code>";
} else {
    echo "<em>(none - allowing all countries)</em>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>Allowed IPs</h3>";
if ($geo_allowed_ips) {
    echo "<textarea rows='6' readonly>" . e($geo_allowed_ips) . "</textarea>";
} else {
    echo "<em>(none - check by country only)</em>";
}
echo "</div>";

if ($geo_enabled === '1') {
    echo "<div class='section'>";
    echo "<h2>Access Check for Your IP: " . e($realIp) . "</h2>";
    
    // Check if admin
    if (is_admin()) {
        echo "<div class='pass'><strong>✓ You are logged in as ADMIN → ALLOWED (never blocked)</strong></div>";
    } else {
        // Check if private IP
        $isPrivate = !filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        
        if ($isPrivate) {
            echo "<div class='info'><strong>ℹ This is a PRIVATE/LOCAL IP → ALLOWED (never blocked in local dev)</strong></div>";
            echo "<p>To test blocking, access from a <strong>public IP</strong> outside your allowed ranges.</p>";
        } else {
            // Check blocked IPs
            if (ip_in_list($realIp, $geo_blocked_ips)) {
                echo "<div class='fail'><strong>✗ BLOCKED (IP in blacklist)</strong></div>";
            } elseif (ip_in_list($realIp, $geo_allowed_ips)) {
                echo "<div class='pass'><strong>✓ ALLOWED (IP in whitelist)</strong></div>";
            } else {
                // Check country
                $country = detect_country($realIp);
                $allowed_countries = array_filter(preg_split('/[\s,;]+/', strtoupper($geo_allowed_countries)));
                
                if ($allowed_countries) {
                    if ($country === null) {
                        $status = $geo_block_unknown === '1' ? 'BLOCKED' : 'ALLOWED';
                        $class = $geo_block_unknown === '1' ? 'fail' : 'pass';
                        echo "<div class='" . $class . "'>";
                        echo "<strong>" . ($geo_block_unknown === '1' ? '✗' : '✓') . " Country detection FAILED → " . $status . "</strong>";
                        echo "</div>";
                    } else {
                        $inList = in_array($country, $allowed_countries, true);
                        if ($inList) {
                            echo "<div class='pass'><strong>✓ ALLOWED (Country: " . e($country) . " ✓ in allowed list)</strong></div>";
                        } else {
                            echo "<div class='fail'><strong>✗ BLOCKED (Country: " . e($country) . " ✗ NOT in allowed list [" . e(implode(', ', $allowed_countries)) . "])</strong></div>";
                        }
                    }
                } else {
                    echo "<div class='pass'><strong>✓ ALLOWED (No country restrictions)</strong></div>";
                }
            }
        }
    }
    echo "</div>";
}

echo "<div class='section'>";
echo "<h3>Test with Different IP</h3>";
echo "<p>Enter a test IP to simulate:</p>";
echo "<form method='get'>";
echo "<input type='text' name='test_ip' value='' placeholder='e.g., 103.118.78.200'>";
echo "<button type='submit'>Test IP</button>";
echo "</form>";
echo "</div>";

echo "<hr>";
echo "<p><a href='admin/access.php'>→ Go to Access Control Settings</a></p>";
echo "</body></html>";
?>

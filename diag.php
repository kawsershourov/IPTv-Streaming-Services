<?php
require __DIR__ . '/app/bootstrap.php';

echo "=== GEO-BLOCKING DIAGNOSTIC ===\n\n";

// Check database settings
echo "1. Database Settings:\n";
$geo_enabled = Setting::get('geo_enabled', '0');
$geo_allowed_countries = Setting::get('geo_allowed_countries', '');
$geo_allowed_ips = Setting::get('geo_allowed_ips', '');
$geo_blocked_ips = Setting::get('geo_blocked_ips', '');
$geo_use_api = Setting::get('geo_use_api', '0');
$geo_block_unknown = Setting::get('geo_block_unknown', '0');

echo "   geo_enabled: " . ($geo_enabled === '1' ? "✓ ENABLED" : "✗ DISABLED") . "\n";
echo "   geo_allowed_countries: [" . trim($geo_allowed_countries) . "]\n";
echo "   geo_allowed_ips: [" . trim($geo_allowed_ips) . "]\n";
echo "   geo_blocked_ips: [" . trim($geo_blocked_ips) . "]\n";
echo "   geo_use_api: " . ($geo_use_api === '1' ? "YES" : "NO") . "\n";
echo "   geo_block_unknown: " . ($geo_block_unknown === '1' ? "YES" : "NO") . "\n\n";

// Check current visitor
echo "2. Current Visitor:\n";
$ip = client_ip();
$country = detect_country($ip);
echo "   IP: " . $ip . "\n";
echo "   Country: " . ($country ?? "UNKNOWN") . "\n\n";

// Check if geo_cache table exists
echo "3. Database Table Check:\n";
try {
    $row = db_one("SELECT 1 FROM geo_cache LIMIT 1");
    echo "   geo_cache table: ✓ EXISTS\n";
} catch (Exception $e) {
    echo "   geo_cache table: ✗ MISSING (error: " . $e->getMessage() . ")\n";
}

// Test IP matching
echo "\n4. IP Matching Test:\n";
$test_ip = "103.118.78.200";
$test_cidr = "103.118.78.0/24";
echo "   Testing if " . $test_ip . " matches CIDR " . $test_cidr . ": ";
echo (ip_matches($test_ip, $test_cidr) ? "✓ MATCH" : "✗ NO MATCH") . "\n";

// Test current visitor IP against allowed list
if ($geo_allowed_ips) {
    echo "   Testing if " . $ip . " is in allowed IPs: ";
    echo (ip_in_list($ip, $geo_allowed_ips) ? "✓ MATCH" : "✗ NO MATCH") . "\n";
} else {
    echo "   (no allowed IPs set)\n";
}

// Test current visitor country against allowed list
if ($geo_allowed_countries) {
    $allowed = array_filter(preg_split('/[\s,]+/', strtoupper($geo_allowed_countries)));
    echo "   Allowed countries: " . implode(", ", $allowed) . "\n";
    if ($country) {
        echo "   Your country (" . $country . ") in list: ";
        echo (in_array($country, $allowed, true) ? "✓ YES" : "✗ NO") . "\n";
    }
} else {
    echo "   (no allowed countries set)\n";
}

echo "\n5. Access Decision:\n";
if ($geo_enabled !== '1') {
    echo "   → Geo-blocking is DISABLED, site is OPEN TO ALL\n";
} else {
    // Simulate geo_guard logic
    $blocked = false;
    $reason = "";
    
    // Check if is admin
    if (function_exists('is_admin') && is_admin()) {
        echo "   → You are logged in as ADMIN, would BYPASS all checks\n";
    } else {
        // Check private IP
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            echo "   → IP is PRIVATE/RESERVED, would ALLOW\n";
        } else {
            // Check blocked IPs
            if (ip_in_list($ip, $geo_blocked_ips)) {
                echo "   → IP is in BLOCKED list → BLOCK\n";
                $blocked = true;
                $reason = "IP blocked";
            } elseif (ip_in_list($ip, $geo_allowed_ips)) {
                echo "   → IP is in ALLOWED list → ALLOW\n";
            } else {
                // Check country
                $allowed_countries = array_filter(preg_split('/[\s,]+/', strtoupper($geo_allowed_countries)));
                if ($allowed_countries) {
                    if ($country === null) {
                        if ($geo_block_unknown === '1') {
                            echo "   → Country UNKNOWN + block_unknown=1 → BLOCK\n";
                            $blocked = true;
                            $reason = "Unknown country";
                        } else {
                            echo "   → Country UNKNOWN, but block_unknown=0 → ALLOW\n";
                        }
                    } else {
                        if (in_array($country, $allowed_countries, true)) {
                            echo "   → Country " . $country . " is in ALLOWED list → ALLOW\n";
                        } else {
                            echo "   → Country " . $country . " is NOT in allowed list → BLOCK\n";
                            $blocked = true;
                            $reason = "Country not allowed";
                        }
                    }
                } else {
                    echo "   → No allowed countries set, no country check → ALLOW\n";
                }
            }
        }
    }
}

echo "\n";
?>

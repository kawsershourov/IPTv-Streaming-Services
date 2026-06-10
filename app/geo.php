<?php
declare(strict_types=1);

/**
 * Geo / IP access control.
 * Restrict the site to specific countries and/or IP ranges; block IPs; show a
 * custom "restricted" page to everyone else. Configured in Admin → Access.
 *
 * Country detection order: Cloudflare CF-IPCountry header → Apache GeoIP env →
 * optional cached ip-api.com lookup. Private/localhost IPs and (optionally) the
 * admin area are exempt so you can't lock yourself out.
 */

/** Does an IP match a single rule (exact IP or CIDR, IPv4/IPv6)? */
function ip_matches(string $ip, string $rule): bool
{
    $rule = trim($rule);
    if ($rule === '') {
        return false;
    }
    if (strpos($rule, '/') === false) {
        return $ip === $rule;
    }
    [$subnet, $bits] = explode('/', $rule, 2);
    $bits   = (int) $bits;
    $ipBin  = @inet_pton($ip);
    $subBin = @inet_pton(trim($subnet));
    if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
        return false;
    }
    $whole = intdiv($bits, 8);
    $rem   = $bits % 8;
    if ($whole > 0 && strncmp($ipBin, $subBin, $whole) !== 0) {
        return false;
    }
    if ($rem > 0) {
        $mask = chr((0xff << (8 - $rem)) & 0xff);
        if ((ord($ipBin[$whole]) & ord($mask)) !== (ord($subBin[$whole]) & ord($mask))) {
            return false;
        }
    }
    return true;
}

/** Is $ip listed in a newline/comma/space/semicolon separated list of IPs/CIDRs? */
function ip_in_list(string $ip, string $listText): bool
{
    foreach (preg_split('/[\s,;]+/', $listText) as $rule) {
        if ($rule !== '' && ip_matches($ip, $rule)) {
            return true;
        }
    }
    return false;
}

/** Best-effort ISO country code for an IP, or null if unknown. */
function detect_country(string $ip): ?string
{
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $c = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY']));
        if ($c !== '' && $c !== 'XX' && $c !== 'T1') {
            return $c;
        }
    }
    foreach (['GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_GEOIP_COUNTRY'] as $k) {
        if (!empty($_SERVER[$k])) {
            return strtoupper(trim((string) $_SERVER[$k]));
        }
    }
    if (Setting::get('geo_use_api', '0') === '1') {
        return geo_api_country($ip);
    }
    return null;
}

/** ip-api.com lookup, cached per IP for 7 days. Returns [country, is_proxy, is_hosting] */
function geo_api_lookup(string $ip): array
{
    $row = db_one('SELECT country, is_proxy, is_hosting, checked_at FROM geo_cache WHERE ip = ?', [$ip]);
    if ($row && strtotime((string) $row['checked_at']) > time() - 604800) {
        return [
            $row['country'] !== '' ? $row['country'] : null,
            (int) $row['is_proxy'] === 1,
            (int) $row['is_hosting'] === 1
        ];
    }
    $country = '';
    $is_proxy = false;
    $is_hosting = false;
    $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
    $resp = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=countryCode,proxy,hosting', false, $ctx);
    if ($resp) {
        $d = json_decode($resp, true);
        if (!empty($d['countryCode'])) {
            $country = strtoupper($d['countryCode']);
        }
        $is_proxy = !empty($d['proxy']);
        $is_hosting = !empty($d['hosting']);
    }
    db_run(
        'INSERT INTO geo_cache (ip, country, is_proxy, is_hosting) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE country = VALUES(country), is_proxy = VALUES(is_proxy), is_hosting = VALUES(is_hosting), checked_at = NOW()',
        [$ip, $country, $is_proxy ? 1 : 0, $is_hosting ? 1 : 0]
    );
    return [$country !== '' ? $country : null, $is_proxy, $is_hosting];
}

/** Legacy wrapper for backward compatibility */
function geo_api_country(string $ip): ?string
{
    [$country] = geo_api_lookup($ip);
    return $country;
}

/** Enforce access rules; renders the restricted page and exits when blocked. */
function geo_guard(): void
{
    if (Setting::get('geo_enabled', '0') !== '1') {
        return;
    }
    // Logged-in admins are never geo-blocked (so you can always fix the config).
    if (function_exists('is_admin') && is_admin()) {
        return;
    }

    $ip = client_ip();
    // Private / reserved / localhost addresses always pass (unless testing with ?geo_test=1).
    $testMode = isset($_GET['geo_test']) && $_GET['geo_test'] === '1';
    if (!$testMode && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return;
    }
    // Admin area exempt unless explicitly enabled.
    $isAdminArea = strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/admin') !== false;
    if ($isAdminArea && Setting::get('geo_apply_admin', '0') !== '1') {
        return;
    }

    if (ip_in_list($ip, (string) Setting::get('geo_blocked_ips', ''))) {
        geo_block();
    }
    if (ip_in_list($ip, (string) Setting::get('geo_allowed_ips', ''))) {
        return;
    }

    // Check VPN/Proxy/Datacenter if enabled
    if (Setting::get('geo_block_vpn', '0') === '1' || Setting::get('geo_block_datacenter', '0') === '1') {
        if (Setting::get('geo_use_api', '0') === '1') {
            [$country, $is_proxy, $is_hosting] = geo_api_lookup($ip);
            
            if (Setting::get('geo_block_vpn', '0') === '1' && $is_proxy) {
                geo_block('VPN/Proxy detected');
            }
            if (Setting::get('geo_block_datacenter', '0') === '1' && $is_hosting) {
                geo_block('Datacenter/Hosting provider detected');
            }
        }
    }

    $allowed = array_filter(preg_split('/[\s,]+/', strtoupper((string) Setting::get('geo_allowed_countries', ''))));
    if ($allowed) {
        $country = detect_country($ip);
        if ($country === null) {
            if (Setting::get('geo_block_unknown', '0') === '1') {
                geo_block();
            }
            return; // unknown location → allow unless block_unknown is on
        }
        if (!in_array($country, $allowed, true)) {
            geo_block();
        }
    }
}

/** Render the standalone "access restricted" page (403) and stop. */
function geo_block(string $reason = null): void
{
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $site    = e((string) Setting::get('site_name', 'SunPlex'));
    $logo    = (string) Setting::get('site_logo', '');
    $msg     = trim((string) Setting::get('geo_block_message', ''));
    if ($msg === '') {
        $msg = 'Access to this service is available only on our network. Please connect through your ISP provider to continue.';
    }
    if ($reason) {
        $msg .= "\n\n[Reason: " . $reason . "]";
    }
    $ip      = e(client_ip());
    $country = detect_country(client_ip());
    $cc      = $country ? e($country) : '';
    
    $logoHtml = '';
    if ($logo !== '') {
        $logoUrl = e(asset_url($logo));
        $logoWidth = (int) Setting::get('site_logo_width', '160');
        $logoHtml = '<img src="' . $logoUrl . '" alt="' . $site . '" style="max-width:' . $logoWidth . 'px;max-height:64px;margin-bottom:20px;">';
    }
    
    $msgHtml = e($msg);

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Access Restricted — {$site}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:#0b0e14;color:#e8ecf3;
  font-family:"Segoe UI",Roboto,system-ui,-apple-system,Arial,sans-serif;
  padding:24px;overflow:hidden;
}
body::before,body::after{
  content:'';position:fixed;border-radius:50%;filter:blur(80px);opacity:.35;
  animation:orb 12s ease-in-out infinite alternate;pointer-events:none;z-index:0;
}
body::before{
  width:600px;height:600px;top:-120px;left:-100px;
  background:radial-gradient(circle,rgba(43,139,255,.5),transparent 70%);
}
body::after{
  width:500px;height:500px;bottom:-100px;right:-80px;
  background:radial-gradient(circle,rgba(255,138,0,.4),transparent 70%);
  animation-delay:6s;animation-direction:alternate-reverse;
}
@keyframes orb{0%{transform:translate(0,0) scale(1)}100%{transform:translate(40px,-30px) scale(1.15)}}

.card{
  position:relative;z-index:1;
  max-width:520px;width:100%;text-align:center;
  background:rgba(22,27,39,.85);
  border:1px solid rgba(40,48,65,.7);
  border-radius:20px;padding:48px 36px 40px;
  box-shadow:0 8px 40px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.04);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
}
.card::before{
  content:'';position:absolute;top:0;left:20%;right:20%;height:3px;
  background:linear-gradient(90deg,transparent,#ff8a00,#2b8bff,transparent);
  border-radius:0 0 3px 3px;
}
.logo{margin:0 auto 16px}
.logo img{display:block;max-width:100%;height:auto}
.shield{margin:0 auto 18px;width:64px;height:64px;animation:pulse 2.5s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:scale(1);filter:drop-shadow(0 0 8px rgba(255,94,58,.3))}50%{transform:scale(1.06);filter:drop-shadow(0 0 16px rgba(255,94,58,.5))}}
h1{font-size:24px;font-weight:800;margin:0 0 12px;letter-spacing:-.3px}
.msg{color:#8a93a6;line-height:1.7;font-size:15px;margin:0 0 24px;white-space:pre-wrap}
.info-row{
  display:flex;justify-content:center;gap:24px;flex-wrap:wrap;margin:0 0 28px;
}
.info-item{display:flex;flex-direction:column;gap:2px}
.info-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5a6378}
.info-val{font-size:13px;font-weight:600;font-family:"Cascadia Code","Fira Code",monospace;color:#c0c8d8}
.brand{font-weight:800;font-size:20px;letter-spacing:.3px}
.brand b{background:linear-gradient(90deg,#ff8a00,#ff5e3a);-webkit-background-clip:text;background-clip:text;color:transparent}
.brand span{color:#2b8bff}
.help{margin-top:20px;font-size:12px;color:#5a6378}
@media(max-width:480px){
  .card{padding:36px 22px 30px;border-radius:16px}
  h1{font-size:20px}
  .info-row{gap:16px}
}
</style>
</head>
<body>
<div class="card">
  {$logoHtml}
  <div class="shield">
    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="sg" x1="0" y1="0" x2="64" y2="64">
          <stop offset="0%" stop-color="#ff8a00"/>
          <stop offset="100%" stop-color="#ff5e3a"/>
        </linearGradient>
      </defs>
      <path d="M32 4L8 16v16c0 14.4 10.24 27.84 24 32 13.76-4.16 24-17.6 24-32V16L32 4z" fill="url(#sg)" opacity=".15" stroke="url(#sg)" stroke-width="2"/>
      <path d="M22 32l6 6 14-14" stroke="url(#sg)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".8"/>
      <line x1="18" y1="18" x2="46" y2="46" stroke="#ff5e3a" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
    </svg>
  </div>
  <h1>Access Restricted</h1>
  <p class="msg">{$msgHtml}</p>
  <div class="info-row">
    <div class="info-item">
      <span class="info-label">Your IP</span>
      <span class="info-val">{$ip}</span>
    </div>
HTML;
    if ($cc !== '') {
        echo '    <div class="info-item"><span class="info-label">Country</span><span class="info-val">' . $cc . '</span></div>';
    }
    echo <<<HTML
  </div>
  <!-- <div class="brand"><b>Sun</b><span>Plex</span></div>
  <p class="help">If you believe this is an error, contact your service provider.</p> -->
</div>
</body>
</html>
HTML;
    exit;
}

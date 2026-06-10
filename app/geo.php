<?php
declare(strict_types=1);

/**
 * Geo / IP access control.
 * Restrict the site to specific countries and/or IP ranges; block IPs; show a
 * custom "restricted" page to everyone else. Configured in Admin → Access.
 *
 * Country detection: Cloudflare CF-IPCountry header when present, otherwise a
 * local MaxMind GeoLite2-Country database (uploaded in Admin → Access). No
 * external API. Private/localhost IPs and (optionally) the admin area are exempt
 * so you can't lock yourself out.
 */

require_once APP_DIR . '/lib/MmdbReader.php';

/** Absolute path to the uploaded GeoLite2-Country database. */
function geo_db_path(): string
{
    return APP_DIR . '/data/GeoLite2-Country.mmdb';
}

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

/** Shared GeoLite2 reader for this request (or null if no/invalid database). */
function geo_reader(): ?MmdbReader
{
    static $reader = false; // false = not yet attempted
    if ($reader !== false) {
        return $reader;
    }
    $path = geo_db_path();
    if (!is_file($path)) {
        return $reader = null;
    }
    try {
        return $reader = new MmdbReader($path);
    } catch (\Throwable $e) {
        return $reader = null;
    }
}

/** Best-effort ISO country code for an IP, or null if unknown. */
function detect_country(string $ip): ?string
{
    // Cloudflare provides the country directly — fastest + reliable when present.
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $c = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY']));
        if ($c !== '' && $c !== 'XX' && $c !== 'T1') {
            return $c;
        }
    }
    // Local GeoLite2-Country database.
    $reader = geo_reader();
    if ($reader) {
        try {
            $c = $reader->country($ip);
            if ($c) {
                return $c;
            }
        } catch (\Throwable $e) {
            // unreadable record — fall through
        }
    }
    return null;
}

/** Status of the installed GeoLite2 database, for the admin page. */
function geo_db_status(): array
{
    $path = geo_db_path();
    if (!is_file($path)) {
        return ['installed' => false];
    }
    try {
        $meta = (new MmdbReader($path))->metadata();
        return [
            'installed'  => true,
            'type'       => (string) ($meta['database_type'] ?? 'unknown'),
            'build'      => isset($meta['build_epoch']) ? date('Y-m-d', (int) $meta['build_epoch']) : '?',
            'ip_version' => (int) ($meta['ip_version'] ?? 0),
            'size'       => (int) (filesize($path) ?: 0),
        ];
    } catch (\Throwable $e) {
        return ['installed' => true, 'error' => $e->getMessage()];
    }
}

/**
 * Install an uploaded GeoLite2 database. Accepts a raw .mmdb, a MaxMind
 * .tar.gz/.tgz (the .mmdb is extracted), or a gzip-compressed .mmdb.gz.
 * Returns ['ok' => bool, 'msg' => string].
 */
function geo_db_install(string $tmpPath, string $origName): array
{
    $dest = geo_db_path();
    @mkdir(dirname($dest), 0775, true);
    $name = strtolower($origName);

    try {
        if (substr($name, -5) === '.mmdb') {
            if (!@copy($tmpPath, $dest)) {
                return ['ok' => false, 'msg' => 'Could not save the .mmdb file (check folder permissions).'];
            }
        } elseif (preg_match('/\.(tar\.gz|tgz)$/', $name)) {
            if (!class_exists('PharData')) {
                return ['ok' => false, 'msg' => 'PHP Phar extension is unavailable — upload the raw .mmdb instead.'];
            }
            $work = sys_get_temp_dir() . '/geodb_' . uniqid('', true) . '.tar.gz';
            if (!@copy($tmpPath, $work)) {
                return ['ok' => false, 'msg' => 'Could not stage the archive for extraction.'];
            }
            $found = null;
            foreach (new RecursiveIteratorIterator(new PharData($work)) as $f) {
                if (strtolower(substr($f->getFilename(), -5)) === '.mmdb') {
                    $found = $f->getPathname();
                    break;
                }
            }
            if ($found === null) {
                @unlink($work);
                return ['ok' => false, 'msg' => 'No .mmdb file found inside the archive.'];
            }
            $copied = @copy($found, $dest);
            @unlink($work);
            if (!$copied) {
                return ['ok' => false, 'msg' => 'Extracted the archive but could not save the database.'];
            }
        } elseif (substr($name, -3) === '.gz') {
            $in  = @gzopen($tmpPath, 'rb');
            $out = @fopen($dest, 'wb');
            if (!$in || !$out) {
                return ['ok' => false, 'msg' => 'Could not decompress the .gz file.'];
            }
            while (!gzeof($in)) {
                fwrite($out, gzread($in, 262144));
            }
            gzclose($in);
            fclose($out);
        } else {
            return ['ok' => false, 'msg' => 'Unsupported file. Upload a GeoLite2 .tar.gz, a .mmdb, or a .mmdb.gz.'];
        }
    } catch (\Throwable $e) {
        return ['ok' => false, 'msg' => 'Extraction failed: ' . $e->getMessage()];
    }

    // Validate the result is a real MMDB.
    try {
        $meta  = (new MmdbReader($dest))->metadata();
        $type  = (string) ($meta['database_type'] ?? '');
        $built = isset($meta['build_epoch']) ? date('Y-m-d', (int) $meta['build_epoch']) : '?';
        return ['ok' => true, 'msg' => "Installed: {$type} (built {$built})."];
    } catch (\Throwable $e) {
        @unlink($dest);
        return ['ok' => false, 'msg' => 'The uploaded file is not a valid MMDB database.'];
    }
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

    $allowed = array_filter(preg_split('/[\s,;]+/', strtoupper((string) Setting::get('geo_allowed_countries', ''))));
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

/**
 * Evaluate (without enforcing) what the geo rules would decide for an IP.
 * Returns ['allowed' => bool, 'reason' => string, 'country' => ?string].
 * Used by the admin "test an IP" tool.
 */
function geo_evaluate(string $ip): array
{
    if (Setting::get('geo_enabled', '0') !== '1') {
        return ['allowed' => true, 'reason' => 'Geo restriction is OFF.', 'country' => detect_country($ip)];
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return ['allowed' => true, 'reason' => 'Private / localhost IP — always allowed.', 'country' => null];
    }
    if (ip_in_list($ip, (string) Setting::get('geo_blocked_ips', ''))) {
        return ['allowed' => false, 'reason' => 'IP is in the blocked list.', 'country' => detect_country($ip)];
    }
    if (ip_in_list($ip, (string) Setting::get('geo_allowed_ips', ''))) {
        return ['allowed' => true, 'reason' => 'IP is in the allowed list.', 'country' => detect_country($ip)];
    }
    $allowed = array_filter(preg_split('/[\s,;]+/', strtoupper((string) Setting::get('geo_allowed_countries', ''))));
    $country = detect_country($ip);
    if ($allowed) {
        if ($country === null) {
            return Setting::get('geo_block_unknown', '0') === '1'
                ? ['allowed' => false, 'reason' => 'Country could not be determined and "block unknown" is ON.', 'country' => null]
                : ['allowed' => true, 'reason' => 'Country unknown, but "block unknown" is OFF.', 'country' => null];
        }
        return in_array($country, $allowed, true)
            ? ['allowed' => true, 'reason' => "Country {$country} is in the allowed list.", 'country' => $country]
            : ['allowed' => false, 'reason' => "Country {$country} is not in the allowed list.", 'country' => $country];
    }
    return ['allowed' => true, 'reason' => 'No country filter set.', 'country' => $country];
}

/** Render the standalone "access restricted" page (403) and stop. */
function geo_block(string $reason = null): void
{
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $site = e((string) Setting::get('site_name', 'SunPlex'));
    $logo = (string) Setting::get('site_logo', '');
    $msg  = trim((string) Setting::get('geo_block_message', ''));
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
        $logoUrl   = e(asset_url($logo));
        $logoWidth = (int) Setting::get('site_logo_width', '160');
        $logoHtml  = '<div class="logo"><img src="' . $logoUrl . '" alt="' . $site . '" style="max-width:' . $logoWidth . 'px;max-height:64px;"></div>';
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
.logo img{display:block;max-width:100%;height:auto;margin:0 auto}
.shield{margin:0 auto 18px;width:64px;height:64px;animation:pulse 2.5s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:scale(1);filter:drop-shadow(0 0 8px rgba(255,94,58,.3))}50%{transform:scale(1.06);filter:drop-shadow(0 0 16px rgba(255,94,58,.5))}}
h1{font-size:24px;font-weight:800;margin:0 0 12px;letter-spacing:-.3px}
.msg{color:#8a93a6;line-height:1.7;font-size:15px;margin:0 0 24px;white-space:pre-wrap}
.info-row{
  display:flex;justify-content:center;gap:24px;flex-wrap:wrap;margin:0 0 8px;
}
.info-item{display:flex;flex-direction:column;gap:2px}
.info-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5a6378}
.info-val{font-size:13px;font-weight:600;font-family:"Cascadia Code","Fira Code",monospace;color:#c0c8d8}
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
</div>
</body>
</html>
HTML;
    exit;
}

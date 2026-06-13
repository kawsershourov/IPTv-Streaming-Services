<?php
declare(strict_types=1);

/**
 * Channel health checks. Tests each channel's stream URL; when a stream stays
 * down past the failure threshold it is auto-hidden from the playlist (status →
 * inactive) and an email alert is sent. When it recovers it is auto-restored.
 *
 * Run from cron via cron/health-check.php, or manually from Admin → Notifications.
 */

/**
 * Low-level HTTP probe: ranged GET of the first $maxBytes. Returns the body
 * (possibly empty) on any HTTP response, or null on a transport/connection
 * failure. $code and $err are filled in.
 */
function http_probe(string $url, int $maxBytes, ?int &$code = null, ?string &$err = null): ?string
{
    $code = 0;
    $err  = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SunPlexHealthCheck/1.0)',
            CURLOPT_RANGE          => '0-' . max(0, $maxBytes),
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        return is_string($body) ? $body : '';
    }

    $ctx = stream_context_create(['http' => [
        'timeout'       => 15,
        'ignore_errors' => true,
        'header'        => "User-Agent: Mozilla/5.0\r\nRange: bytes=0-" . max(0, $maxBytes) . "\r\n",
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    if ($body === false && $code === 0) {
        $err = 'connection failed';
        return null;
    }
    return $body === false ? '' : $body;
}

/** First URI line (the first non-comment, non-blank line) in an HLS playlist. */
function hls_first_uri(string $manifest): ?string
{
    foreach (preg_split('/\r\n|\n|\r/', $manifest) as $line) {
        $line = trim($line);
        if ($line !== '' && $line[0] !== '#') {
            return $line;
        }
    }
    return null;
}

/** Resolve a possibly-relative URL against a base URL. */
function resolve_url(string $base, string $rel): string
{
    if (preg_match('#^https?://#i', $rel)) {
        return $rel;
    }
    $p      = parse_url($base);
    $scheme = $p['scheme'] ?? 'https';
    $host   = $p['host'] ?? '';
    $port   = isset($p['port']) ? ':' . $p['port'] : '';
    if (str_starts_with($rel, '//')) {
        return $scheme . ':' . $rel;
    }
    if ($rel !== '' && $rel[0] === '/') {
        return "{$scheme}://{$host}{$port}{$rel}";
    }
    $path = $p['path'] ?? '/';
    $dir  = substr($path, 0, (int) strrpos($path, '/') + 1);
    $segs = [];
    foreach (explode('/', $dir . $rel) as $seg) {
        if ($seg === '..') {
            array_pop($segs);
        } elseif ($seg !== '.' && $seg !== '') {
            $segs[] = $seg;
        }
    }
    return "{$scheme}://{$host}{$port}/" . implode('/', $segs);
}

/**
 * Probe a stream URL. Returns true if it looks reachable/playable.
 * For HLS it requires a real #EXTM3U manifest, and for a master playlist it also
 * verifies the first variant playlist is reachable — catching the common case
 * where the manifest URL answers but the stream behind it is dead.
 * $info is filled with ['code' => int, 'error' => string].
 */
function check_stream_url(string $url, string $type = 'hls', ?array &$info = null): bool
{
    $info = ['code' => 0, 'error' => ''];
    $url  = trim($url);

    // Non-HTTP sources (e.g. youtube ids) can't be probed — treat as OK.
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return true;
    }

    $body = http_probe($url, 16384, $info['code'], $info['error']);
    if ($body === null) {
        return false; // connection/transport failure
    }
    if ($info['code'] < 200 || $info['code'] >= 400) {
        return false;
    }

    if ($type === 'hls') {
        if (!is_string($body) || stripos($body, '#EXTM3U') === false) {
            $info['error'] = 'no #EXTM3U manifest (HTTP ' . $info['code'] . ')';
            return false;
        }
        // Master playlist → verify the first variant playlist is also reachable.
        if (stripos($body, '#EXT-X-STREAM-INF') !== false) {
            $variant = hls_first_uri($body);
            if ($variant !== null) {
                $vbody = http_probe(resolve_url($url, $variant), 16384, $vcode, $verr);
                if ($vbody === null || $vcode < 200 || $vcode >= 400) {
                    $info['code']  = (int) $vcode;
                    $info['error'] = 'variant unreachable (' . ($verr !== '' ? $verr : 'HTTP ' . (int) $vcode) . ')';
                    return false;
                }
                if (is_string($vbody) && stripos($vbody, '#EXTM3U') === false) {
                    $info['code']  = (int) $vcode;
                    $info['error'] = 'variant is not a valid playlist';
                    return false;
                }
            }
        }
    }
    return true;
}

/**
 * Check all active (and previously auto-hidden) channels and apply the policy.
 * Returns ['checked'=>int, 'down'=>[...], 'restored'=>[...], 'emailed'=>bool].
 */
function run_health_check(): array
{
    $threshold = max(1, (int) Setting::get('health_fail_threshold', '2'));
    $autoHide  = Setting::get('health_auto_hide', '1') === '1';
    $notify    = Setting::get('health_notify', '1') === '1';

    $rows = db_all("SELECT * FROM channels WHERE status = 'active' OR auto_hidden = 1");

    $failing  = []; // everything that failed THIS run (any strike count)
    $hidden   = []; // newly hidden this run (just crossed the threshold)
    $restored = [];
    foreach ($rows as $ch) {
        $id = (int) $ch['id'];
        $ok = check_stream_url((string) $ch['stream_url'], (string) $ch['stream_type'], $info);

        if ($ok) {
            db_run('UPDATE channels SET health_status = ?, fail_count = 0, last_checked_at = NOW() WHERE id = ?', ['ok', $id]);
            if ((int) $ch['auto_hidden'] === 1) {
                db_run("UPDATE channels SET status = 'active', auto_hidden = 0 WHERE id = ?", [$id]);
                $restored[] = ['name' => $ch['name'], 'id' => $id];
            }
            continue;
        }

        $fails  = (int) $ch['fail_count'] + 1;
        $reason = $info['error'] !== '' ? $info['error'] : ('HTTP ' . (int) $info['code']);
        db_run('UPDATE channels SET health_status = ?, fail_count = ?, last_checked_at = NOW() WHERE id = ?', ['down', $fails, $id]);
        $failing[] = ['name' => $ch['name'], 'url' => (string) $ch['stream_url'], 'code' => (int) $info['code'], 'error' => $reason, 'fails' => $fails];

        // Hide + alert exactly once, the moment it crosses the threshold.
        if ($fails === $threshold) {
            if ($autoHide && $ch['status'] === 'active') {
                db_run("UPDATE channels SET status = 'inactive', auto_hidden = 1 WHERE id = ?", [$id]);
            }
            $hidden[] = ['name' => $ch['name'], 'url' => (string) $ch['stream_url'], 'code' => (int) $info['code'], 'error' => $reason, 'hidden' => $autoHide];
        }
    }

    $emailed = false;
    if ($notify && ($hidden || $restored) && function_exists('notify_admin') && mailer_configured()) {
        $emailed = health_send_email($hidden, $restored);
    }

    return ['checked' => count($rows), 'failing' => $failing, 'hidden' => $hidden, 'restored' => $restored, 'emailed' => $emailed];
}

/** Compose + send the health summary email. */
function health_send_email(array $down, array $restored): bool
{
    $body = '';
    if ($down) {
        $body .= '<p style="color:#c0392b;font-weight:700;margin:0 0 8px;">Channels down:</p><ul style="color:#1a2030;line-height:1.7;padding-left:18px;margin:0 0 8px;">';
        foreach ($down as $d) {
            $tag = $d['hidden'] ? ' <span style="color:#b26a00;">(hidden from playlist)</span>' : '';
            $why = $d['error'] !== '' ? e($d['error']) : ('HTTP ' . (int) $d['code']);
            $body .= '<li><strong>' . e($d['name']) . '</strong> — ' . $why . $tag . '</li>';
        }
        $body .= '</ul>';
    }
    if ($restored) {
        $body .= '<p style="color:#1e9e54;font-weight:700;margin:16px 0 8px;">Back online (restored):</p><ul style="color:#1a2030;line-height:1.7;padding-left:18px;margin:0 0 8px;">';
        foreach ($restored as $r) {
            $body .= '<li><strong>' . e($r['name']) . '</strong></li>';
        }
        $body .= '</ul>';
    }
    $base = function_exists('mail_site_url') ? mail_site_url() : rtrim((string) config('site.base_url', ''), '/');
    $body .= '<p style="margin:18px 0 0;"><a href="' . e($base . '/admin/channels.php')
        . '" style="display:inline-block;background:#ff8a00;color:#ffffff;text-decoration:none;padding:9px 16px;border-radius:6px;font-weight:600;">Open channels in admin →</a></p>';

    $count   = count($down);
    $subject = $count > 0
        ? '🔴 ' . $count . ' channel' . ($count === 1 ? '' : 's') . ' down — ' . (string) Setting::get('site_name', 'SunPlex')
        : '🟢 Channels restored — ' . (string) Setting::get('site_name', 'SunPlex');

    return notify_admin($subject, mail_template('Channel health update', $body));
}

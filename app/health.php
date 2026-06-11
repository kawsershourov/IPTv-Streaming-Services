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
 * Probe a stream URL. Returns true if it looks reachable/valid.
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

    if (!function_exists('curl_init')) {
        $headers = @get_headers($url);
        $status  = is_array($headers) ? ($headers[0] ?? '') : '';
        $info['code'] = (int) (preg_match('#\s(\d{3})\s#', (string) $status, $m) ? $m[1] : 0);
        return $info['code'] >= 200 && $info['code'] < 400;
    }

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
        CURLOPT_RANGE          => '0-4095', // first 4 KB is enough to see a manifest
    ]);
    $body = curl_exec($ch);
    $info['code'] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if (curl_errno($ch)) {
        $info['error'] = curl_error($ch);
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($info['code'] < 200 || $info['code'] >= 400) {
        return false;
    }
    // HLS manifests always start with #EXTM3U — this catches "200 but it's an error page".
    if ($type === 'hls' && is_string($body) && $body !== '' && stripos($body, '#EXTM3U') === false) {
        $info['error'] = 'HTTP ' . $info['code'] . ' but no #EXTM3U manifest';
        return false;
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

    $down = [];
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

        $fails = (int) $ch['fail_count'] + 1;
        db_run('UPDATE channels SET health_status = ?, fail_count = ?, last_checked_at = NOW() WHERE id = ?', ['down', $fails, $id]);

        // Act exactly once, the moment it crosses the threshold.
        if ($fails === $threshold) {
            if ($autoHide && $ch['status'] === 'active') {
                db_run("UPDATE channels SET status = 'inactive', auto_hidden = 1 WHERE id = ?", [$id]);
            }
            $down[] = ['name' => $ch['name'], 'url' => (string) $ch['stream_url'], 'code' => $info['code'], 'error' => $info['error'], 'hidden' => $autoHide];
        }
    }

    $emailed = false;
    if ($notify && ($down || $restored) && function_exists('notify_admin') && mailer_configured()) {
        $emailed = health_send_email($down, $restored);
    }

    return ['checked' => count($rows), 'down' => $down, 'restored' => $restored, 'emailed' => $emailed];
}

/** Compose + send the health summary email. */
function health_send_email(array $down, array $restored): bool
{
    $body = '';
    if ($down) {
        $body .= '<p style="color:#ff8ea0;font-weight:700;margin:0 0 8px">Channels down:</p><ul style="color:#e8ecf3;line-height:1.7;padding-left:18px">';
        foreach ($down as $d) {
            $tag = $d['hidden'] ? ' <span style="color:#ffb74d">(hidden from playlist)</span>' : '';
            $why = $d['error'] !== '' ? e($d['error']) : ('HTTP ' . (int) $d['code']);
            $body .= '<li><strong>' . e($d['name']) . '</strong> — ' . $why . $tag . '</li>';
        }
        $body .= '</ul>';
    }
    if ($restored) {
        $body .= '<p style="color:#76e39a;font-weight:700;margin:16px 0 8px">Back online (restored):</p><ul style="color:#e8ecf3;line-height:1.7;padding-left:18px">';
        foreach ($restored as $r) {
            $body .= '<li><strong>' . e($r['name']) . '</strong></li>';
        }
        $body .= '</ul>';
    }
    $body .= '<p style="margin-top:16px"><a href="' . e(rtrim((string) config('site.base_url', ''), '/') . '/admin/channels.php')
        . '" style="color:#2b8bff">Open channels in admin →</a></p>';

    $count   = count($down);
    $subject = $count > 0
        ? '🔴 ' . $count . ' channel' . ($count === 1 ? '' : 's') . ' down — ' . (string) Setting::get('site_name', 'SunPlex')
        : '🟢 Channels restored — ' . (string) Setting::get('site_name', 'SunPlex');

    return notify_admin($subject, mail_template('Channel health update', $body));
}

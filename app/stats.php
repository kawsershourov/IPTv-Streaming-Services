<?php
declare(strict_types=1);

/**
 * Lightweight front-end visitor stats.
 * - track_visit(): call once per front-end page render. Inserts one row per
 *   session per day; otherwise just refreshes last_seen (throttled to once/min)
 *   so "online now" works without a write on every page view.
 * - stats_summary(): cached counts (recomputed at most once per 60s) for display.
 */

function track_visit(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $today = date('Y-m-d');

    if (($_SESSION['visit_day'] ?? '') !== $today) {
        db_run(
            'INSERT INTO visits (session_id, ip) VALUES (?, ?)',
            [substr((string) session_id(), 0, 64), function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '')]
        );
        $_SESSION['visit_day']  = $today;
        $_SESSION['visit_id']   = (int) db()->lastInsertId();
        $_SESSION['visit_seen'] = time();
        return;
    }

    // Same session/day: keep "online" fresh, but no more than once per minute.
    if (!empty($_SESSION['visit_id']) && (time() - (int) ($_SESSION['visit_seen'] ?? 0)) > 60) {
        db_run('UPDATE visits SET last_seen = NOW() WHERE id = ?', [(int) $_SESSION['visit_id']]);
        $_SESSION['visit_seen'] = time();
    }
}

/** Visitor/site counts, cached in settings for 60s to stay cheap under traffic. */
function stats_summary(): array
{
    $cached     = Setting::get('stats_cache', '');
    $cachedTime = (int) Setting::get('stats_cache_time', '0');
    if ($cached !== '' && (time() - $cachedTime) < 15) {
        $d = json_decode($cached, true);
        if (is_array($d)) {
            return $d;
        }
    }

    $d = [
        'online'   => (int) (db_one('SELECT COUNT(*) AS c FROM visits WHERE last_seen > (NOW() - INTERVAL 5 MINUTE)')['c'] ?? 0),
        'today'    => (int) (db_one('SELECT COUNT(*) AS c FROM visits WHERE created_at >= CURDATE()')['c'] ?? 0),
        'total'    => (int) (db_one('SELECT COUNT(*) AS c FROM visits')['c'] ?? 0),
        'members'  => User::count(),
        'channels' => Channel::count(),
    ];
    Setting::set('stats_cache', json_encode($d));
    Setting::set('stats_cache_time', (string) time());
    return $d;
}

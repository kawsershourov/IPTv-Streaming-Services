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

/**
 * Visitor/site counts, cached for STATS_CACHE_TTL seconds so the COUNT queries run
 * at most a few times a minute no matter how many visitors are polling. The cache
 * (data + timestamp together) lives in a single settings row to minimise writes
 * under a traffic spike.
 */
function stats_summary(): array
{
    $ttl    = 10;
    $cached = Setting::get('stats_cache', '');
    if ($cached !== '') {
        $d = json_decode($cached, true);
        if (is_array($d) && isset($d['_t']) && (time() - (int) $d['_t']) < $ttl) {
            unset($d['_t']);
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
    Setting::set('stats_cache', json_encode($d + ['_t' => time()])); // one write, not two
    return $d;
}

/** Headline visitor/site counts for the admin report (uncached — admin only). */
function stats_report_counts(): array
{
    $c = static fn (string $sql): int => (int) (db_one($sql)['c'] ?? 0);
    return [
        'online'    => $c('SELECT COUNT(*) c FROM visits WHERE last_seen > (NOW() - INTERVAL 5 MINUTE)'),
        'today'     => $c('SELECT COUNT(*) c FROM visits WHERE created_at >= CURDATE()'),
        'yesterday' => $c('SELECT COUNT(*) c FROM visits WHERE created_at >= (CURDATE() - INTERVAL 1 DAY) AND created_at < CURDATE()'),
        'last7'     => $c('SELECT COUNT(*) c FROM visits WHERE created_at >= (CURDATE() - INTERVAL 6 DAY)'),
        'last30'    => $c('SELECT COUNT(*) c FROM visits WHERE created_at >= (CURDATE() - INTERVAL 29 DAY)'),
        'total'     => $c('SELECT COUNT(*) c FROM visits'),
        'members'   => User::count(),
        'channels'  => Channel::count(),
    ];
}

/**
 * Visitors per day for the last $days days (oldest → newest), with zero-filled
 * gaps. Returns [['date' => 'Y-m-d', 'count' => int], ...].
 */
function stats_daily(int $days = 30): array
{
    $days = max(1, min(365, $days));
    $rows = db_all(
        'SELECT DATE(created_at) d, COUNT(*) c
           FROM visits
          WHERE created_at >= (CURDATE() - INTERVAL ' . ($days - 1) . ' DAY)
          GROUP BY DATE(created_at)'
    );
    $map = [];
    foreach ($rows as $r) {
        $map[(string) $r['d']] = (int) $r['c'];
    }
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} day"));
        $out[] = ['date' => $d, 'count' => $map[$d] ?? 0];
    }
    return $out;
}

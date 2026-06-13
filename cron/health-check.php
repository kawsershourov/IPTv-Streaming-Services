<?php
/**
 * Channel health check — run on a schedule.
 *
 * cPanel cron (recommended, every 15 min):
 *   /usr/local/bin/php /home/USER/public_html/cron/health-check.php
 *
 * Or via URL (set a token in Admin → Notifications):
 *   https://yourdomain.com/cron/health-check.php?token=YOUR_TOKEN
 */
require __DIR__ . '/../app/bootstrap.php';

// CLI runs are always allowed; web runs require the secret token.
if (PHP_SAPI !== 'cli') {
    $token = (string) Setting::get('health_cron_token', '');
    if ($token === '' || !hash_equals($token, (string) ($_GET['token'] ?? ''))) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$start = microtime(true);
$r = run_health_check();
printf(
    "Checked %d channel(s) in %.1fs — failing: %d, newly hidden: %d, restored: %d, email: %s\n",
    $r['checked'],
    microtime(true) - $start,
    count($r['failing']),
    count($r['hidden']),
    count($r['restored']),
    $r['emailed'] ? 'sent' : 'no'
);
foreach ($r['failing'] as $f) {
    printf("  DOWN  %s — %s (strike %d)\n", $f['name'], $f['error'], $f['fails']);
}

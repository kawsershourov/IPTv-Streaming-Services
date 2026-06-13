<?php
/**
 * Live stats feed (JSON) polled by the front-end stats bar.
 * Calling it also refreshes this visitor's "online" status while the page is open.
 */
require __DIR__ . '/app/bootstrap.php';

track_visit();

// The browser reports its true EXTERNAL public IP (from an IP-echo API). A site
// hosted inside the ISP only sees an internal NAT address via REMOTE_ADDR, so we
// store the browser-reported value for display. NOTE: this is client-supplied and
// therefore display-only — geo/security still use the server-side IP.
$reportIp = trim((string) ($_GET['reportip'] ?? ''));
if ($reportIp !== '' && filter_var($reportIp, FILTER_VALIDATE_IP) && !empty($_SESSION['visit_id'])) {
    db_run('UPDATE visits SET public_ip = ? WHERE id = ?', [$reportIp, (int) $_SESSION['visit_id']]);
}

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(stats_summary());

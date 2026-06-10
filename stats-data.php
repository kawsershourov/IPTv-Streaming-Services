<?php
/**
 * Live stats feed (JSON) polled by the front-end stats bar.
 * Calling it also refreshes this visitor's "online" status while the page is open.
 */
require __DIR__ . '/app/bootstrap.php';

track_visit();

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(stats_summary());

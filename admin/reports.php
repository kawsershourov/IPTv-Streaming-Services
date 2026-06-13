<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();

// Live counts for the AJAX auto-refresh (used by Reports + Dashboard).
if (($_GET['ajax'] ?? '') === 'counts') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(stats_report_counts());
    exit;
}

$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90, 180], true)) {
    $days = 30;
}

// CSV export of the daily series.
if (($_GET['export'] ?? '') === 'csv') {
    $daily = stats_daily($days);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sunplex-visitors-' . date('Ymd') . '.csv"');
    echo "Date,Visitors\n";
    foreach ($daily as $row) {
        echo $row['date'] . ',' . $row['count'] . "\n";
    }
    exit;
}

$adminTitle = 'Reports';
$activeNav  = 'reports';
require __DIR__ . '/includes/header.php';

$reportShowRange = true;
$reportHeading   = 'Visitor reports';
require __DIR__ . '/includes/visitor_report.php';

require __DIR__ . '/includes/footer.php';

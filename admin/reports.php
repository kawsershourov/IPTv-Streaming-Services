<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();

// Live counts for the AJAX auto-refresh.
if (($_GET['ajax'] ?? '') === 'counts') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(stats_report_counts());
    exit;
}

$allowed = [7, 30, 90, 180];
$days    = (int) ($_GET['days'] ?? 30);
if (!in_array($days, $allowed, true)) {
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

$counts = stats_report_counts();
$daily  = stats_daily($days);
$max    = max(1, max(array_column($daily, 'count')));
$sum    = array_sum(array_column($daily, 'count'));
$avg    = $days > 0 ? round($sum / $days, 1) : 0;
$peak   = ['date' => '—', 'count' => 0];
foreach ($daily as $row) {
    if ($row['count'] >= $peak['count']) {
        $peak = $row;
    }
}

// Recent visitor sessions (newest first) with their visit time span.
$recent = db_all('SELECT created_at, last_seen, ip FROM visits ORDER BY id DESC LIMIT 60');
$fmtDur = static function (int $s): string {
    if ($s < 60) {
        return '<1 min';
    }
    $m = intdiv($s, 60);
    if ($m < 60) {
        return $m . ' min';
    }
    $h = intdiv($m, 60);
    $m %= 60;
    return $h . 'h' . ($m ? ' ' . $m . 'm' : '');
};

$cards = [
    ['online',    'Online now',    $counts['online'],    '#1e9e54'],
    ['today',     'Today',         $counts['today'],     '#ff8a00'],
    ['yesterday', 'Yesterday',     $counts['yesterday'], '#2b8bff'],
    ['last7',     'Last 7 days',   $counts['last7'],     '#7c5cff'],
    ['last30',    'Last 30 days',  $counts['last30'],    '#e0529c'],
    ['total',     'Total visits',  $counts['total'],     '#5a6378'],
];

$adminTitle = 'Reports';
$activeNav  = 'reports';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <h1 style="margin:0;">Visitor reports</h1>
    <div style="display:flex;gap:8px;align-items:center;">
        <span class="muted" style="font-size:13px;">Range:</span>
        <?php foreach ($allowed as $d): ?>
            <a href="<?= e(url('admin/reports.php?days=' . $d)) ?>"
               class="btn <?= $d === $days ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $d ?>d</a>
        <?php endforeach; ?>
        <a href="<?= e(url('admin/reports.php?days=' . $days . '&export=csv')) ?>" class="btn btn-outline btn-sm">⬇ CSV</a>
    </div>
</div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:18px 0;">
    <?php foreach ($cards as [$key, $label, $value, $color]): ?>
        <div class="card" style="padding:16px 18px;">
            <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;"><?= e($label) ?>
                <?php if ($key === 'online'): ?><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#1e9e54;margin-left:4px;animation:statbump 1.6s ease-in-out infinite;"></span><?php endif; ?>
            </div>
            <div style="font-size:28px;font-weight:800;color:<?= $color ?>;line-height:1.2;margin-top:4px;" data-stat="<?= $key ?>"><?= number_format($value) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Daily chart -->
<div class="card" style="margin-bottom:18px;">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
        <h2 style="margin:0;font-size:16px;">Visitors per day — last <?= (int) $days ?> days</h2>
        <span class="muted" style="font-size:13px;">Total <?= number_format($sum) ?> · avg <?= $avg ?>/day · peak <?= number_format($peak['count']) ?> (<?= e(date('M j', strtotime($peak['date']))) ?>)</span>
    </div>
    <div style="display:flex;align-items:flex-end;gap:<?= $days > 60 ? 1 : 3 ?>px;height:170px;border-bottom:1px solid #283041;padding-bottom:2px;overflow:hidden;">
        <?php foreach ($daily as $row): $h = (int) round($row['count'] / $max * 150) + 2; ?>
            <div title="<?= e(date('D, M j', strtotime($row['date']))) ?>: <?= (int) $row['count'] ?> visitors"
                 style="flex:1;min-width:2px;height:<?= $h ?>px;border-radius:3px 3px 0 0;
                        background:linear-gradient(180deg,#ff8a00,#ff5e3a);opacity:<?= $row['count'] ? 1 : .25 ?>;"></div>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:6px;" class="muted">
        <span style="font-size:11px;"><?= e(date('M j', strtotime($daily[0]['date']))) ?></span>
        <span style="font-size:11px;"><?= e(date('M j', strtotime($daily[count($daily) - 1]['date']))) ?></span>
    </div>
</div>

<?php $thSticky = 'position:sticky;top:0;background:#1b2230;z-index:1;'; ?>
<!-- Day by day  +  Recent visitors, side by side -->
<div style="display:flex;gap:18px;flex-wrap:wrap;align-items:stretch;">

    <!-- Day by day (left) -->
    <div class="card" style="flex:1 1 360px;min-width:300px;margin:0;display:flex;flex-direction:column;">
        <h2 style="margin:0 0 12px;font-size:16px;">Day by day</h2>
        <div style="max-height:480px;overflow-y:auto;border:1px solid #283041;border-radius:8px;">
            <table class="table" style="width:100%;font-size:13px;margin:0; text-align:center;">
                <thead><tr style="text-align:center !important;">
                    <th style="<?= $thSticky ?> width:15%;">Date</th>
                    <th style="<?= $thSticky ?>width:10%;">Day</th>
                    <th style="<?= $thSticky ?>width:12%;">Visitors</th>
                    <th style="<?= $thSticky ?>">&nbsp;</th>
                </tr></thead>
                <tbody>
                <?php foreach (array_reverse($daily) as $row): $w = (int) round($row['count'] / $max * 100); ?>
                    <tr>
                        <td><?= e(date('M j, Y', strtotime($row['date']))) ?></td>
                        <td class="muted"><?= e(date('D', strtotime($row['date']))) ?></td>
                        <td style="text-align:center;font-weight:700;"><?= number_format($row['count']) ?></td>
                        <td>
                            <div style="background:#1d2433;border-radius:4px;height:10px;overflow:hidden;">
                                <div style="width:<?= $w ?>%;height:100%;background:linear-gradient(90deg,#ff8a00,#ff5e3a);"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted" style="font-size:12px;margin-top:10px;">A “visitor” is one browser session per day. Bot/admin views aren’t counted.</p>
    </div>

    <!-- Recent visitors — time span (right) -->
    <div class="card" style="flex:1 1 420px;min-width:320px;margin:0;display:flex;flex-direction:column;">
        <h2 style="margin:0 0 4px;font-size:16px;">Recent visitors — time span</h2>
        <p class="muted" style="margin:0 0 12px;font-size:12px;">First arrival → last activity. Bangladesh time (Asia/Dhaka, AM/PM).</p>
        <?php if ($recent): ?>
        <div style="max-height:480px;overflow-y:auto;border:1px solid #283041;border-radius:8px;">
            <table class="table" style="width:100%;font-size:13px;margin:0;">
                <thead><tr>
                    <?php foreach (['Date', 'From', 'To', 'Duration', 'Country', 'IP'] as $h): ?>
                        <th style="<?= $thSticky ?>"><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($recent as $v):
                    $from = strtotime((string) $v['created_at']);
                    $to   = strtotime((string) $v['last_seen']);
                    $cc   = function_exists('detect_country') ? detect_country((string) $v['ip']) : null;
                ?>
                    <tr style="text-align:center;">
                        <td><?= e(date('M j, Y', $from)) ?></td>
                        <td style="font-weight:600;"><?= e(date('g:i A', $from)) ?></td>
                        <td style="font-weight:600;"><?= e(date('g:i A', $to)) ?></td>
                        <td class="muted"><?= e($fmtDur(max(0, $to - $from))) ?></td>
                        <td><?= $cc ? e($cc) : '<span class="muted">—</span>' ?></td>
                        <td class="muted" style="font-family:monospace;"><?= e((string) $v['ip']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted" style="font-size:12px;margin-top:10px;">Scroll inside the box — latest <?= count($recent) ?> sessions.</p>
        <?php else: ?>
            <p class="muted" style="font-size:13px;">No visits recorded yet.</p>
        <?php endif; ?>
    </div>

</div>

<script>
(function () {
    var feed = <?= json_encode(url('admin/reports.php?ajax=counts')) ?>;
    function tick() {
        if (document.hidden) return;
        fetch(feed, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                document.querySelectorAll('[data-stat]').forEach(function (el) {
                    var k = el.getAttribute('data-stat');
                    if (d[k] === undefined || d[k] === null) return;
                    var v = Number(d[k]).toLocaleString();
                    if (el.textContent !== v) {
                        el.textContent = v;
                        el.classList.remove('stat-bump');
                        void el.offsetWidth;          // restart the animation
                        el.classList.add('stat-bump');
                    }
                });
            })
            .catch(function () {});
    }
    setInterval(tick, 15000); // refresh every 15s
    document.addEventListener('visibilitychange', function () { if (!document.hidden) tick(); });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

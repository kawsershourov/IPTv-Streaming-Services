<?php
/**
 * Visitor report block — shared by Admin → Reports and the Dashboard.
 * Set before including (all optional):
 *   $days            int    range in days (7/30/90/180), default 30
 *   $reportShowRange bool   show the range switch + CSV (reports page only)
 *   $reportHeading   string section title
 */
$days = (int) ($days ?? 30);
$reportAllowed = [7, 30, 90, 180];
if (!in_array($days, $reportAllowed, true)) {
    $days = 30;
}
$reportShowRange = $reportShowRange ?? false;
$reportHeading   = $reportHeading ?? 'Visitor reports';

$rCounts = stats_report_counts();
$rDaily  = stats_daily($days);
$rMax    = max(1, max(array_column($rDaily, 'count')));
$rSum    = array_sum(array_column($rDaily, 'count'));
$rAvg    = $days > 0 ? round($rSum / $days, 1) : 0;
$rPeak   = ['date' => $rDaily[0]['date'] ?? date('Y-m-d'), 'count' => 0];
foreach ($rDaily as $row) {
    if ($row['count'] >= $rPeak['count']) {
        $rPeak = $row;
    }
}
$rRecent = db_all('SELECT created_at, last_seen, ip FROM visits ORDER BY id DESC LIMIT 60');

$rFmtDur = static function (int $s): string {
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

// Country label: ISO code for public IPs, "Local" for private/localhost, else "—".
$rCountry = static function (string $ip): string {
    $ip = trim($ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return '—';
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return 'Local';
    }
    $c = function_exists('detect_country') ? detect_country($ip) : null;
    return $c ?: '—';
};

$rCards = [
    ['online',    'Online now',    $rCounts['online'],    '#1e9e54'],
    ['today',     'Today',         $rCounts['today'],     '#ff8a00'],
    ['yesterday', 'Yesterday',     $rCounts['yesterday'], '#2b8bff'],
    ['last7',     'Last 7 days',   $rCounts['last7'],     '#7c5cff'],
    ['last30',    'Last 30 days',  $rCounts['last30'],    '#e0529c'],
    ['total',     'Total visits',  $rCounts['total'],     '#5a6378'],
];
$rThSticky = 'position:sticky;top:0;background:#1b2230;z-index:1;';
?>
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <h2 style="margin:0;font-size:18px;"><?= e($reportHeading) ?></h2>
    <?php if ($reportShowRange): ?>
    <div style="display:flex;gap:8px;align-items:center;">
        <span class="muted" style="font-size:13px;">Range:</span>
        <?php foreach ($reportAllowed as $d): ?>
            <a href="<?= e(url('admin/reports.php?days=' . $d)) ?>" class="btn <?= $d === $days ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $d ?>d</a>
        <?php endforeach; ?>
        <a href="<?= e(url('admin/reports.php?days=' . $days . '&export=csv')) ?>" class="btn btn-outline btn-sm">⬇ CSV</a>
    </div>
    <?php endif; ?>
</div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:18px 0;">
    <?php foreach ($rCards as [$key, $label, $value, $color]): ?>
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
        <span class="muted" style="font-size:13px;">Total <?= number_format($rSum) ?> · avg <?= $rAvg ?>/day · peak <?= number_format($rPeak['count']) ?> (<?= e(date('M j', strtotime($rPeak['date']))) ?>)</span>
    </div>
    <div style="overflow-x:auto;overflow-y:hidden;padding-bottom:2px;">
        <div style="display:flex;gap:2px;min-width:100%;">
            <?php foreach ($rDaily as $i => $row):
                $h  = (int) round($row['count'] / $rMax * 140) + 2;
                $ts = strtotime($row['date']);
                $showMonth = ($i === 0 || (int) date('j', $ts) === 1);
            ?>
                <div title="<?= e(date('D, M j', $ts)) ?>: <?= (int) $row['count'] ?> visitors"
                     style="flex:1 0 <?= $days > 7 ? '22px' : '0' ?>;min-width:6px;text-align:center;">
                    <?php if ($days <= 31): ?>
                        <div style="font-size:10px;font-weight:700;color:#c0c8d8;height:14px;"><?= $row['count'] ? (int) $row['count'] : '' ?></div>
                    <?php endif; ?>
                    <div style="height:150px;display:flex;align-items:flex-end;justify-content:center;">
                        <div style="width:72%;max-width:24px;min-width:5px;height:<?= $h ?>px;border-radius:3px 3px 0 0;background:linear-gradient(180deg,#ff8a00,#ff5e3a);opacity:<?= $row['count'] ? 1 : .3 ?>;"></div>
                    </div>
                    <div style="font-size:9px;color:#8a93a6;margin-top:5px;line-height:1.15;white-space:nowrap;">
                        <?= (int) date('j', $ts) ?>
                        <?php if ($showMonth): ?><br><span style="color:#ff8a00;font-weight:700;"><?= e(date('M', $ts)) ?></span><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="muted" style="font-size:11px;margin:8px 0 0;">Each bar is one day (<?= e(date('M j', strtotime($rDaily[0]['date']))) ?> → <?= e(date('M j', strtotime($rDaily[count($rDaily) - 1]['date']))) ?>). Scroll sideways to see every date · hover a bar for the exact count.</p>
</div>

<!-- Day by day  +  Recent visitors, side by side -->
<div style="display:flex;gap:18px;flex-wrap:wrap;align-items:stretch;">

    <div class="card" style="flex:1 1 360px;min-width:300px;margin:0;display:flex;flex-direction:column;">
        <h2 style="margin:0 0 12px;font-size:16px;">Day by day</h2>
        <div style="max-height:480px;overflow-y:auto;border:1px solid #283041;border-radius:8px;">
            <table class="table" style="width:100%;font-size:13px;margin:0;text-align:center;">
                <thead><tr>
                    <th style="<?= $rThSticky ?>width:15%;">Date</th>
                    <th style="<?= $rThSticky ?>width:10%;">Day</th>
                    <th style="<?= $rThSticky ?>width:12%;">Visitors</th>
                    <th style="<?= $rThSticky ?>">&nbsp;</th>
                </tr></thead>
                <tbody>
                <?php foreach (array_reverse($rDaily) as $row): $w = (int) round($row['count'] / $rMax * 100); ?>
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

    <div class="card" style="flex:1 1 420px;min-width:320px;margin:0;display:flex;flex-direction:column;">
        <h2 style="margin:0 0 4px;font-size:16px;">Recent visitors — time span</h2>
        <p class="muted" style="margin:0 0 12px;font-size:12px;">First arrival → last activity. Bangladesh time (Asia/Dhaka, AM/PM).</p>
        <?php if ($rRecent): ?>
        <div style="max-height:480px;overflow-y:auto;border:1px solid #283041;border-radius:8px;">
            <table class="table" style="width:100%;font-size:13px;margin:0;">
                <thead><tr>
                    <?php foreach (['Date', 'From', 'To', 'Duration', 'Country', 'IP'] as $h): ?>
                        <th style="<?= $rThSticky ?>"><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($rRecent as $v):
                    $from = strtotime((string) $v['created_at']);
                    $to   = strtotime((string) $v['last_seen']);
                    $cc   = $rCountry((string) $v['ip']);
                ?>
                    <tr style="text-align:center;">
                        <td><?= e(date('M j, Y', $from)) ?></td>
                        <td style="font-weight:600;"><?= e(date('g:i A', $from)) ?></td>
                        <td style="font-weight:600;"><?= e(date('g:i A', $to)) ?></td>
                        <td class="muted"><?= e($rFmtDur(max(0, $to - $from))) ?></td>
                        <td><?= $cc === '—' ? '<span class="muted">—</span>' : ($cc === 'Local' ? '<span class="muted">Local</span>' : e($cc)) ?></td>
                        <td class="muted" style="font-family:monospace;"><?= e((string) $v['ip']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted" style="font-size:12px;margin-top:10px;">Scroll inside the box — latest <?= count($rRecent) ?> sessions.</p>
        <?php else: ?>
            <p class="muted" style="font-size:13px;">No visits recorded yet.</p>
        <?php endif; ?>
    </div>

</div>

<script>
(function () {
    var feed = <?= json_encode(url('admin/reports.php?ajax=counts')) ?>;
    function tick() {
        if (document.hidden) { return; }
        fetch(feed, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) { return; }
                document.querySelectorAll('[data-stat]').forEach(function (el) {
                    var k = el.getAttribute('data-stat');
                    if (d[k] === undefined || d[k] === null) { return; }
                    var v = Number(d[k]).toLocaleString();
                    if (el.textContent !== v) {
                        el.textContent = v;
                        el.classList.remove('stat-bump');
                        void el.offsetWidth;
                        el.classList.add('stat-bump');
                    }
                });
            })
            .catch(function () {});
    }
    setInterval(tick, 1000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { tick(); } });
})();
</script>

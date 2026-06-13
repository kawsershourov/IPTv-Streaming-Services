<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();

$stats = [
    ['Users',          User::count()],
    ['Channels',       Channel::count()],
    ['Categories',     Category::count()],
    ['Active subs',    Subscription::activeCount()],
];
$recent = array_slice(Channel::allWithCategory(), 0, 8);

$adminTitle = 'Dashboard';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<h1>Dashboard</h1>

<div class="stat-grid">
    <?php foreach ($stats as [$label, $num]): ?>
        <div class="stat"><div class="num"><?= (int) $num ?></div><div class="label"><?= e($label) ?></div></div>
    <?php endforeach; ?>
</div>

<div class="toolbar"><h2 style="margin:0;font-size:18px;">Recent channels</h2>
    <a href="<?= e(url('admin/channels.php?action=new')) ?>" class="btn btn-primary btn-sm">+ Add channel</a>
</div>
<div class="table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Category</th><th>Type</th><th>Premium</th><th>Status</th></tr></thead>
        <tbody>
            <?php foreach ($recent as $ch): ?>
                <tr>
                    <td><?= e($ch['name']) ?></td>
                    <td><?= e($ch['category_name']) ?></td>
                    <td><?= e(strtoupper($ch['stream_type'])) ?></td>
                    <td><?= (int) $ch['is_premium'] ? '<span class="tag tag-prem">Premium</span>' : '—' ?></td>
                    <td><?= $ch['status'] === 'active' ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">inactive</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?><tr><td colspan="5" class="muted">No channels yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top:28px;"></div>
<?php
// Mirror the visitor report onto the dashboard (last 30 days, no range switch).
$days            = 30;
$reportShowRange = false;
$reportHeading   = 'Visitor analytics';
require __DIR__ . '/includes/visitor_report.php';
?>
<?php require __DIR__ . '/includes/footer.php'; ?>

</main>

<?php
track_visit();
if (Setting::get('show_visitor_stats', '1') === '1'):
    $sp_stats = stats_summary();
?>
<section class="site-stats" data-feed="<?= e(url('stats-data.php')) ?>">
    <div class="wrap stats-inner">
        <div class="stat-item"><span class="stat-dot"></span><span class="stat-num" data-stat="online"><?= number_format($sp_stats['online']) ?></span><span class="stat-label">Online now</span></div>
        <div class="stat-item"><span class="stat-num" data-stat="today"><?= number_format($sp_stats['today']) ?></span><span class="stat-label">Today</span></div>
        <div class="stat-item"><span class="stat-num" data-stat="total"><?= number_format($sp_stats['total']) ?></span><span class="stat-label">Total visitors</span></div>
        <!-- <div class="stat-item"><span class="stat-num" data-stat="members"><?= number_format($sp_stats['members']) ?></span><span class="stat-label">Members</span></div> -->
        <div class="stat-item"><span class="stat-num" data-stat="channels"><?= number_format($sp_stats['channels']) ?></span><span class="stat-label">Channels</span></div>
    </div>
</section>
<?php endif; ?>

<footer class="site-footer">
    <div class="wrap footer-inner">
        <p>&copy; <?= date('Y') ?> <?= e(Setting::get('site_name', 'SunPlex')) ?>. All rights reserved.</p>
        <p class="muted"><?= e(Setting::get('site_tagline', 'Live TV &amp; Streaming')) ?></p>
    </div>
</footer>

<script src="<?= e(asset('js/site.js')) ?>"></script>
<?= Setting::get('footer_code', '') // trusted admin-entered code ?>
</body>
</html>

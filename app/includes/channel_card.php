<?php
/**
 * Renders one channel tile. Expects $channel (array).
 * Used by the home rows and category grid.
 */
$logo = asset_url($channel['logo'] ?? '');
$initials = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', $channel['name']) ?: $channel['name'], 0, 2));
$watchUrl = url('watch.php?c=' . urlencode($channel['slug']));
?>
<a class="channel-card" href="<?= e($watchUrl) ?>" title="<?= e($channel['name']) ?>">
    <div class="channel-thumb">
        <?php if ($logo !== ''): ?>
            <img src="<?= e($logo) ?>" alt="<?= e($channel['name']) ?>" loading="lazy">
        <?php else: ?>
            <span class="channel-initials"><?= e($initials) ?></span>
        <?php endif; ?>
        <?php if ((int) ($channel['is_live'] ?? 0) === 1): ?>
            <span class="badge badge-live">LIVE</span>
        <?php endif; ?>
        <?php if ((int) ($channel['is_premium'] ?? 0) === 1): ?>
            <span class="badge badge-premium" title="Premium channel">★</span>
        <?php endif; ?>
    </div>
    <span class="channel-name"><?= e($channel['name']) ?></span>
</a>

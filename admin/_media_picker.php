<?php
/** Media-picker grid — returned for the modal (expects $items, $page, $pages). */
?>
<?php if (!$items): ?>
    <p class="empty" style="padding:24px;">No media yet — use “Upload new” above.</p>
<?php else: ?>
    <div class="mp-grid">
        <?php foreach ($items as $m): $k = media_kind($m['url']); ?>
            <button type="button" class="mp-item" data-url="<?= e($m['url']) ?>" title="<?= e($m['filename']) ?>">
                <?php if ($k === 'img'): ?>
                    <img src="<?= e($m['url']) ?>" alt="" loading="lazy">
                <?php elseif ($k === 'video'): ?>
                    <span class="media-icon">🎬</span>
                <?php elseif ($k === 'audio'): ?>
                    <span class="media-icon">🎵</span>
                <?php else: ?>
                    <span class="media-icon">📄</span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="pager-wrap"><?= pager_html($page, $pages) ?></div>
<?php endif; ?>

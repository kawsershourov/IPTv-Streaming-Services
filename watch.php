<?php
/**
 * Watch page — embeds the FWD Ultimate Video Player v11.0 JS engine for one channel.
 *
 * Verified UVP v11.0 embed contract (from player/js/FWDUVP-unminified.js and
 * player/php/FWDUVP.php):
 *   - Required props: instanceName, parentId (holder div), playlistsId (data island),
 *     mainFolderPath (= <player>/content), skinPath (relative to mainFolderPath).
 *   - Playlists are read from HTML markup, not a JS array:
 *       #playlists > div[data-source=<videosId>][data-thumbnail-path]   (a category)
 *       #<videosId> > a[data-thumb-source][data-video-source][data-is-live]
 *                         > *[data-video-short-description] = title
 *   - HLS is auto-detected from the .m3u8 extension (loads content/java/hls.js).
 *
 * Access gating: only channels the user is allowed to watch are placed in the
 * player playlist, so a free user can never switch to a premium stream inside the
 * player. Premium-blocked users see an upsell instead of the player.
 */
require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['c'] ?? ''));
$channel = $slug !== '' ? Channel::findBySlug($slug) : null;

if (!$channel || $channel['status'] !== 'active') {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/app/includes/header.php';
    echo '<p class="empty">Channel not found.</p>';
    require __DIR__ . '/app/includes/footer.php';
    exit;
}

// Guests may watch only when guest access is enabled; otherwise require login.
if (!guest_access_enabled()) {
    require_login();
}
$me = current_user();

$category = Category::find((int) $channel['category_id']);
$siblings = $category ? Channel::activeByCategory((int) $category['id']) : [$channel];

$reason = watch_block_reason($channel, $me); // null | 'subscribe'

$playerBase     = url('player');
$mainFolderPath = $playerBase . '/content';
$skin           = Setting::get('default_skin', config('site.default_skin', 'minimal_skin_dark'));
$fallbackThumb  = $playerBase . '/content/logo.png';

$thumbFor = static function (array $ch) use ($fallbackThumb): string {
    $logo = trim((string) ($ch['logo'] ?? ''));
    return $logo !== '' ? $logo : $fallbackThumb;
};

// Only channels this user may watch go into the player playlist (gating).
$playable = array_values(array_filter($siblings, fn ($ch) => can_watch($ch, $me)));
$startIndex = 0;
foreach ($playable as $i => $ch) {
    if ((int) $ch['id'] === (int) $channel['id']) {
        $startIndex = $i;
        break;
    }
}

$pageTitle = $channel['name'];
$headExtra = '<link rel="stylesheet" href="' . e($playerBase . '/css/fwduvp.css') . '">'
           . '<link rel="stylesheet" href="' . e($playerBase . '/css/fwd_ui.css') . '">';
require __DIR__ . '/app/includes/header.php';
?>
<section class="watch">
    <div class="watch-head">
        <h1><?= e($channel['name']) ?></h1>
        <div class="watch-meta">
            <?php if ($category): ?>
                <a href="<?= e(url('category.php?cat=' . urlencode($category['slug']))) ?>" class="chip"><?= e($category['name']) ?></a>
            <?php endif; ?>
            <?php if ((int) $channel['is_live'] === 1): ?><span class="chip chip-live">LIVE</span><?php endif; ?>
            <?php if ((int) $channel['is_premium'] === 1): ?><span class="chip chip-premium">Premium</span><?php endif; ?>
        </div>
    </div>

    <?php if ($reason === 'subscribe'): ?>
        <div class="upsell">
            <h2>This is a premium channel</h2>
            <p>Upgrade to a premium plan to watch <strong><?= e($channel['name']) ?></strong> and all premium channels.</p>
            <a href="<?= e(url('plans.php')) ?>" class="btn btn-primary btn-lg">View plans</a>
            <a href="<?= e(url('')) ?>" class="btn btn-outline btn-lg">Back to home</a>
        </div>
    <?php elseif ($reason === 'login'): ?>
        <div class="upsell">
            <h2>Log in to watch this channel</h2>
            <p><strong><?= e($channel['name']) ?></strong> is a premium channel. Sign in or create an account to continue.</p>
            <a href="<?= e(url('login.php')) ?>" class="btn btn-primary btn-lg">Log in</a>
            <a href="<?= e(url('register.php')) ?>" class="btn btn-outline btn-lg">Sign up</a>
        </div>
    <?php else: ?>
        <div class="player-stage">
            <div id="player_holder"></div>
        </div>

        <!-- UVP data island (hidden) -->
        <div style="display:none">
            <div id="uvp_playlists">
                <div data-source="uvp_cat" data-thumbnail-path="<?= e($category ? $thumbFor($playable[$startIndex]) : $fallbackThumb) ?>">
                    <?= e($category['name'] ?? 'Channels') ?>
                </div>
            </div>
            <div id="uvp_cat">
                <?php foreach ($playable as $ch): ?>
                    <a data-thumb-source="<?= e($thumbFor($ch)) ?>"
                       data-video-source="<?= e($ch['stream_url']) ?>"
                       data-is-live="<?= ((int) $ch['is_live'] === 1) ? 'yes' : 'no' ?>">
                        <div data-video-short-description><?= e($ch['name']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
        // Player config from admin Player settings + single-category playlist layout.
        $watchConfig = array_merge(uvp_base_config(), [
            'instanceName'                    => 'sunplexPlayer',
            'parentId'                        => 'player_holder',
            'playlistsId'                     => 'uvp_playlists',
            'startAtPlaylist'                 => 0,
            'startAtVideo'                    => (int) $startIndex,
            'showPlaylistsButtonAndPlaylists' => 'no',
            'showPlaylistButtonAndPlaylist'   => 'yes',
        ]);
        ?>
        <script src="<?= e($playerBase . '/js/FWDUVP.js') ?>"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof FWDUVPUtils !== 'undefined' && FWDUVPUtils.checkIfHasTransofrms) {
                FWDUVPUtils.checkIfHasTransofrms();
            }
            <?= uvp_player_script($watchConfig) ?>
        });
        </script>
    <?php endif; ?>

    <?php if ($category && count($siblings) > 1): ?>
        <section class="channel-row watch-more">
            <div class="row-head"><h2>More in <?= e($category['name']) ?></h2></div>
            <div class="card-scroller">
                <?php foreach ($siblings as $channel): // reuse the card partial ?>
                    <?php require __DIR__ . '/app/includes/channel_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/app/includes/footer.php'; ?>

<?php
/**
 * Home page = full FWD UVP player with an "All Channels" + per-category dropdown,
 * a right-side vertical playlist, and search (the sunplex.live live-TV layout).
 *
 * Gating-safe: channels the viewer may watch carry the real stream URL; channels
 * they may NOT watch carry no stream — instead a data-redirect-url sends them to
 * watch.php (login / upsell). The player auto-starts on the first watchable channel.
 */
require __DIR__ . '/app/bootstrap.php';

$me = current_user();
$categories = Category::active();

// Build category => channels, and a flat "all" list (de-duplicated by id, in category order).
$catChannels = [];
$allChannels = [];
foreach ($categories as $cat) {
    $chs = Channel::activeByCategory((int) $cat['id']);
    if ($chs) {
        $catChannels[(int) $cat['id']] = ['cat' => $cat, 'channels' => $chs];
        foreach ($chs as $ch) {
            $allChannels[(int) $ch['id']] = $ch;
        }
    }
}
$allChannels = array_values($allChannels);

$playerBase     = url('player');
$mainFolderPath = $playerBase . '/content';
$skin           = Setting::get('default_skin', config('site.default_skin', 'minimal_skin_dark'));
$fallbackThumb  = $playerBase . '/content/logo.png';

$thumbFor = static function (array $ch) use ($fallbackThumb): string {
    $logo = trim((string) ($ch['logo'] ?? ''));
    return $logo !== '' ? $logo : $fallbackThumb;
};

// First watchable channel in the "All Channels" list — the player starts there so
// autoplay never lands on a redirect item.
$startIndex = 0;
foreach ($allChannels as $i => $ch) {
    if (can_watch($ch, $me)) {
        $startIndex = $i;
        break;
    }
}

/** Render one playlist <a> item (real stream if watchable, else a redirect to watch.php). */
$renderItem = static function (array $ch) use ($me, $thumbFor): string {
    $watchable = can_watch($ch, $me);
    $watchUrl  = url('watch.php?c=' . urlencode($ch['slug']));
    $attrs = 'data-thumb-source="' . e($thumbFor($ch)) . '" '
           . 'data-is-live="' . ((int) $ch['is_live'] === 1 ? 'yes' : 'no') . '" ';
    if ($watchable) {
        $attrs .= 'data-video-source="' . e($ch['stream_url']) . '"';
    } else {
        // No stream URL emitted — selecting this item redirects to the watch page.
        $attrs .= 'data-video-source="' . e($watchUrl) . '" '
               .  'data-redirect-url="' . e($watchUrl) . '" data-redirect-target="_self"';
    }
    $lock = $watchable ? '' : ' 🔒';
    return '<a ' . $attrs . '><div data-video-short-description>' . e($ch['name']) . $lock . '</div></a>';
};

$pageTitle = '';
$bodyClass = 'page-home';
$headExtra = '<link rel="stylesheet" href="' . e($playerBase . '/css/fwduvp.css') . '">'
           . '<link rel="stylesheet" href="' . e($playerBase . '/css/fwd_ui.css') . '">';
require __DIR__ . '/app/includes/header.php';
?>
<?php if ($allChannels): ?>
<div class="home-player">
    <div id="player_holder"></div>
</div>

<!-- UVP data island (hidden) -->
<div style="display:none">
    <div id="uvp_playlists">
        <div data-source="pl_all" data-thumbnail-path="<?= e($thumbFor($allChannels[0])) ?>">All Channels</div>
        <?php foreach ($catChannels as $cid => $grp): ?>
            <div data-source="pl_<?= (int) $cid ?>" data-thumbnail-path="<?= e($thumbFor($grp['channels'][0])) ?>"><?= e($grp['cat']['name']) ?></div>
        <?php endforeach; ?>
    </div>

    <div id="pl_all">
        <?php foreach ($allChannels as $ch) { echo $renderItem($ch); } ?>
    </div>
    <?php foreach ($catChannels as $cid => $grp): ?>
        <div id="pl_<?= (int) $cid ?>">
            <?php foreach ($grp['channels'] as $ch) { echo $renderItem($ch); } ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
// Build the player config from admin Player settings + home-page layout (multi-playlist + dropdown).
$homeConfig = array_merge(uvp_base_config(), [
    'instanceName'                    => 'sunplexHome',
    'parentId'                        => 'player_holder',
    'playlistsId'                     => 'uvp_playlists',
    'startAtPlaylist'                 => 0,
    'startAtVideo'                    => (int) $startIndex,
    'showPlaylistsButtonAndPlaylists' => 'yes',
    'usePlaylistsSelectBox'           => player_yn('player_use_playlists_select_box'),
    'showPlaylistsByDefault'          => player_yn('player_show_playlists_popup'),
    'showPlaylistsSearchInput'        => player_yn('player_show_search'),
]);
?>
<script src="<?= e($playerBase . '/js/FWDUVP.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof FWDUVPUtils !== 'undefined' && FWDUVPUtils.checkIfHasTransofrms) {
        FWDUVPUtils.checkIfHasTransofrms();
    }
    <?= uvp_player_script($homeConfig) ?>
});
</script>
<?php else: ?>
    <p class="empty">No channels available yet. Check back soon.</p>
<?php endif; ?>
<?php require __DIR__ . '/app/includes/footer.php'; ?>

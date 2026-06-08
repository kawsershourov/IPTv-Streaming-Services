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

// Categories dropdown / playlists feature (off = a single "All Channels" list).
$showPlaylists = player_yn('player_show_playlists_button') === 'yes';

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
        // UVP skips reloading when the new source string equals the current one, so two
        // channels sharing the same URL won't both play. Make direct-media sources unique
        // with a #fragment (dropped by the browser before fetching, so the stream is intact).
        $src = $ch['stream_url'];
        if (in_array($ch['stream_type'], ['hls', 'dash', 'mp4'], true) && strpos($src, '#') === false) {
            $src .= '#uvp' . (int) $ch['id'];
        }
        $attrs .= 'data-video-source="' . e($src) . '"';
    } else {
        // No stream URL emitted — selecting this item redirects to the watch page.
        $attrs .= 'data-video-source="' . e($watchUrl) . '" '
               .  'data-redirect-url="' . e($watchUrl) . '" data-redirect-target="_self"';
    }
    $lock = $watchable ? '' : ' 🔒';
    return '<a ' . $attrs . '><div data-video-short-description>'
         . '<span class="sp-chname">' . e($ch['name']) . $lock . '</span></div></a>';
};

// Channel-name styling (font size + alignment) from admin Player settings.
$nameSize  = max(8, (int) player_setting('player_channel_name_size'));
$nameAlign = in_array(player_setting('player_channel_name_align'), ['left', 'center', 'right'], true)
    ? player_setting('player_channel_name_align') : 'center';
$justify   = $nameAlign === 'center' ? 'center' : ($nameAlign === 'right' ? 'flex-end' : 'flex-start');

// The engine sizes/positions the title box responsively (right of the logo, width tracks the
// playlist width). Don't override its width/left — only flex-center the text inside it so it
// stays dynamic when the playlist width changes.
// 10px gap between the logo and the name, only when the name is left-aligned.
$nameGap = $nameAlign === 'left' ? '10' : '0';
$nameCss = '.fwduvp-playlist-thumbnail-dark-text,.fwduvp-playlist-thumbnail-white-text{'
         . 'display:flex !important;align-items:center !important;justify-content:' . $justify . ' !important;}'
         . '.sp-chname{font-size:' . $nameSize . 'px !important;line-height:1.2;margin-left:' . $nameGap . 'px;}';

$pageTitle = '';
$bodyClass = 'page-home';
$headExtra = '<link rel="stylesheet" href="' . e($playerBase . '/css/fwduvp.css') . '">'
           . '<link rel="stylesheet" href="' . e($playerBase . '/css/fwd_ui.css') . '">'
           . '<style>' . $nameCss . '</style>';
require __DIR__ . '/app/includes/header.php';
?>
<?php if ($allChannels): ?>
<div class="home-player">
    <div id="player_holder"></div>
</div>

<!-- UVP data island (hidden) -->
<div style="display:none">
    <div id="uvp_playlists">
        <div data-source="pl_all" data-thumbnail-path="<?= e($thumbFor($allChannels[0])) ?>" data-playlist-name="All Channels">All Channels</div>
        <?php if ($showPlaylists): foreach ($catChannels as $cid => $grp): ?>
            <div data-source="pl_<?= (int) $cid ?>" data-thumbnail-path="<?= e($thumbFor($grp['channels'][0])) ?>" data-playlist-name="<?= e($grp['cat']['name']) ?>"><?= e($grp['cat']['name']) ?></div>
        <?php endforeach; endif; ?>
    </div>

    <div id="pl_all">
        <?php foreach ($allChannels as $ch) { echo $renderItem($ch); } ?>
    </div>
    <?php if ($showPlaylists): foreach ($catChannels as $cid => $grp): ?>
        <div id="pl_<?= (int) $cid ?>">
            <?php foreach ($grp['channels'] as $ch) { echo $renderItem($ch); } ?>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php
// Build the player config from admin Player settings + home-page layout (multi-playlist + dropdown).
$homeConfig = array_merge(uvp_base_config(), [
    'instanceName'                    => 'sunplexHome',
    'parentId'                        => 'player_holder',
    'playlistsId'                     => 'uvp_playlists',
    'startAtPlaylist'                 => 0,
    'startAtVideo'                    => (int) $startIndex,
    'showPlaylistsButtonAndPlaylists' => player_yn('player_show_playlists_button'),
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

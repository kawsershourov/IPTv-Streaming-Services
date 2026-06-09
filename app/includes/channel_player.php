<?php
/**
 * Renders the FWD UVP live-TV player (video + right-side channel playlist).
 * Used by the home page (all channels) and the category pages (one category).
 *
 * Set before including:
 *   $pp_channels : flat array of channel rows for the main playlist (required, non-empty)
 *   $pp_name     : playlist display name (e.g. "All Channels" or a category name)
 *   $pp_groups   : optional [cid => ['cat'=>row, 'channels'=>[rows]]] for the categories dropdown
 *   $pp_instance : unique player instance name
 *
 * Gating-safe: watchable channels carry the real stream (with a unique #uvp<id> fragment so
 * duplicate URLs still reload); non-watchable channels carry only a redirect to watch.php.
 */
$pp_groups   = $pp_groups ?? [];
$pp_name     = $pp_name ?? 'Channels';
$pp_instance = $pp_instance ?? 'sunplexPlayer';
$pp_me       = current_user();

$pp_showLists = !empty($pp_groups) && player_yn('player_show_playlists_button') === 'yes';

$pp_base      = url('player');
$pp_fallback  = $pp_base . '/content/logo.png';

$pp_thumbFor = static function (array $ch) use ($pp_fallback): string {
    $logo = trim((string) ($ch['logo'] ?? ''));
    return $logo !== '' ? asset_url($logo) : $pp_fallback;
};

$pp_renderItem = static function (array $ch) use ($pp_me, $pp_thumbFor): string {
    $watchable = can_watch($ch, $pp_me);
    $watchUrl  = url('watch.php?c=' . urlencode($ch['slug']));
    $attrs = 'data-thumb-source="' . e($pp_thumbFor($ch)) . '" '
           . 'data-is-live="' . ((int) $ch['is_live'] === 1 ? 'yes' : 'no') . '" ';
    if ($watchable) {
        $attrs .= 'data-video-source="' . e(player_source($ch['stream_url'], $ch['stream_type'], (int) $ch['id'])) . '"';
    } else {
        $attrs .= 'data-video-source="' . e($watchUrl) . '" '
               .  'data-redirect-url="' . e($watchUrl) . '" data-redirect-target="_self"';
    }
    $lock = $watchable ? '' : ' 🔒';
    return '<a ' . $attrs . '><div data-video-short-description>'
         . '<span class="sp-chname">' . e($ch['name']) . $lock . '</span></div></a>';
};

// Autoplay starts on the first watchable channel (never a redirect item).
$pp_start = 0;
foreach ($pp_channels as $i => $ch) {
    if (can_watch($ch, $pp_me)) { $pp_start = $i; break; }
}

$pp_config = array_merge(uvp_base_config(), [
    'instanceName'                    => $pp_instance,
    'parentId'                        => 'player_holder',
    'playlistsId'                     => 'uvp_playlists',
    'startAtPlaylist'                 => 0,
    'startAtVideo'                    => (int) $pp_start,
    'showPlaylistsButtonAndPlaylists' => $pp_showLists ? 'yes' : 'no',
    'usePlaylistsSelectBox'           => $pp_showLists ? player_yn('player_use_playlists_select_box') : 'no',
    'showPlaylistsByDefault'          => player_yn('player_show_playlists_popup'),
    'showPlaylistsSearchInput'        => player_yn('player_show_search'),
    'showPlaylistButtonAndPlaylist'   => 'yes',
]);
?>
<div class="home-player"><div id="player_holder"></div></div>

<div style="display:none">
    <div id="uvp_playlists">
        <div data-source="pl_all" data-thumbnail-path="<?= e($pp_thumbFor($pp_channels[0])) ?>" data-playlist-name="<?= e($pp_name) ?>"><?= e($pp_name) ?></div>
        <?php if ($pp_showLists): foreach ($pp_groups as $cid => $grp): ?>
            <div data-source="pl_<?= (int) $cid ?>" data-thumbnail-path="<?= e($pp_thumbFor($grp['channels'][0])) ?>" data-playlist-name="<?= e($grp['cat']['name']) ?>"><?= e($grp['cat']['name']) ?></div>
        <?php endforeach; endif; ?>
    </div>

    <div id="pl_all"><?php foreach ($pp_channels as $ch) { echo $pp_renderItem($ch); } ?></div>
    <?php if ($pp_showLists): foreach ($pp_groups as $cid => $grp): ?>
        <div id="pl_<?= (int) $cid ?>"><?php foreach ($grp['channels'] as $ch) { echo $pp_renderItem($ch); } ?></div>
    <?php endforeach; endif; ?>
</div>

<script src="<?= e($pp_base . '/js/FWDUVP.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof FWDUVPUtils !== 'undefined' && FWDUVPUtils.checkIfHasTransofrms) {
        FWDUVPUtils.checkIfHasTransofrms();
    }
    <?= uvp_player_script($pp_config) ?>
});
</script>

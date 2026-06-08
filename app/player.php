<?php
declare(strict_types=1);

/**
 * Player configuration — turns admin "Player" settings into FWD UVP constructor props.
 * index.php and watch.php both start from uvp_base_config() and add page-specific props
 * (instanceName, parentId, playlistsId, start indices, playlist-source layout).
 */

/** Default values for every admin-managed player setting (also used by the admin form + seed). */
function player_setting_defaults(): array
{
    return [
        'default_skin'                  => 'minimal_skin_dark',
        'player_autoplay'               => 'yes',
        'player_volume'                 => '0.8',
        'player_max_width'              => '1280',
        'player_max_height'             => '720',
        'player_bg_color'               => '#000000',
        'player_use_vector_icons'       => 'no',
        'player_playlist_position'      => 'right',
        'player_playlist_right_width'   => '320',
        'player_show_playlist_by_default' => 'yes',
        'player_use_playlists_select_box' => 'yes',
        'player_show_search'            => 'yes',
        'player_show_fullscreen_button' => 'yes',
        'player_show_volume_button'     => 'yes',
        'player_show_playbackrate_button' => 'no',
        'player_show_rewind_button'     => 'no',
        'player_show_share_button'      => 'no',
        'player_show_embed_button'      => 'no',
        'player_show_download_button'   => 'no',
        'player_show_info_button'       => 'yes',
        'player_show_time'              => 'yes',
        'player_show_next_prev'         => 'yes',
        'player_show_loop_button'       => 'no',
        'player_show_shuffle_button'    => 'no',
    ];
}

/** Read a player setting (falls back to its default). */
function player_setting(string $key): string
{
    $defaults = player_setting_defaults();
    return (string) (Setting::get($key, $defaults[$key] ?? '') ?? ($defaults[$key] ?? ''));
}

/** Normalize a yes/no player setting to 'yes' | 'no'. */
function player_yn(string $key): string
{
    return player_setting($key) === 'yes' ? 'yes' : 'no';
}

/** Common UVP props derived from admin Player settings. */
function uvp_base_config(): array
{
    $base = url('player');
    $pos  = player_setting('player_playlist_position') === 'bottom' ? 'bottom' : 'right';

    return [
        'mainFolderPath'        => $base . '/content',
        'skinPath'              => player_setting('default_skin'),
        'displayType'           => 'responsive',
        'autoScale'             => 'yes',
        'playsinline'           => 'yes',
        'useVectorIcons'        => player_yn('player_use_vector_icons'),
        'autoPlay'              => player_yn('player_autoplay'),
        'volume'                => (float) player_setting('player_volume'),
        'maxWidth'              => (int) player_setting('player_max_width'),
        'maxHeight'             => (int) player_setting('player_max_height'),
        'backgroundColor'       => player_setting('player_bg_color'),
        'videoBackgroundColor'  => '#000000',
        'posterBackgroundColor' => '#000000',
        'playlistPosition'      => $pos,
        'playlistRightWidth'    => (int) player_setting('player_playlist_right_width'),
        'showPlaylistByDefault' => player_yn('player_show_playlist_by_default'),
        'showSearchInput'       => player_yn('player_show_search'),
        'showThumbnail'         => 'yes',
        'showPlaylistName'      => 'yes',
        'showFullScreenButton'  => player_yn('player_show_fullscreen_button'),
        'showVolumeButton'      => player_yn('player_show_volume_button'),
        'showPlaybackRateButton' => player_yn('player_show_playbackrate_button'),
        'showRewindButton'      => player_yn('player_show_rewind_button'),
        'showShareButton'       => player_yn('player_show_share_button'),
        'showEmbedButton'       => player_yn('player_show_embed_button'),
        'showDownloadButton'    => player_yn('player_show_download_button'),
        'showInfoButton'        => player_yn('player_show_info_button'),
        'showTime'              => player_yn('player_show_time'),
        'showNextAndPrevButtons' => player_yn('player_show_next_prev'),
        'showNextAndPrevButtonsInController' => player_yn('player_show_next_prev'),
        'showLoopButton'        => player_yn('player_show_loop_button'),
        'showShuffleButton'     => player_yn('player_show_shuffle_button'),
    ];
}

/** Render a `new FWDUVPlayer({...})` call from a full config array. */
function uvp_player_script(array $config): string
{
    return 'new FWDUVPlayer(' . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
}

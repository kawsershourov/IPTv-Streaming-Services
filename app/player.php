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
        'player_show_playlists_popup'     => 'no',
        'player_show_playlists_button'    => 'no',
        'player_use_playlists_select_box' => 'no',
        'player_thumb_width'              => '40',
        'player_thumb_height'             => '40',
        'player_channel_name_size'        => '13',
        'player_channel_name_align'       => 'center',
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
        'player_show_prevnext_controller' => 'yes',
        'player_show_playlist_button'   => 'yes',
        'player_show_subtitle_button'   => 'no',
        'player_show_quality_button'    => 'no',
        'player_show_audio_tracks_button' => 'no',
        'player_show_chromecast_button' => 'no',
        'player_show_vr_button'         => 'no',
        // Colors
        'player_use_hex_colors'         => 'yes',
        'player_buttons_color'          => '#ffffff',
        'player_time_color'             => '#ffffff',
        'player_playlist_bg_color'      => '#11151f',
        'player_playlist_name_color'    => '#ffffff',
        'player_thumb_normal_bg'        => '#161b27',
        'player_thumb_hover_bg'         => '#1d2433',
        'player_channel_title_color'    => '#ffffff',
        'player_search_bg_color'        => '#0b0e14',
        'player_search_text_color'      => '#ffffff',
        'player_selector_bg_selected'   => '#ff8a00',
        'player_selector_text_normal'   => '#ffffff',
        'player_selector_text_selected' => '#1a1206',
        'player_preloader_bg'           => '#000000',
        'player_preloader_fill'         => '#ff8a00',
        'player_thumb_disabled_bg'      => '#241a1a',
    ];
}

/** Color setting keys => UVP prop names (only these are saved as colors). */
function player_color_map(): array
{
    return [
        'player_buttons_color'          => 'normalHEXButtonsColor',
        'player_time_color'             => 'timeColor',
        'player_playlist_bg_color'      => 'playlistBackgroundColor',
        'player_playlist_name_color'    => 'playlistNameColor',
        'player_thumb_normal_bg'        => 'thumbnailNormalBackgroundColor',
        'player_thumb_hover_bg'         => 'thumbnailHoverBackgroundColor',
        'player_thumb_disabled_bg'      => 'thumbnailDisabledBackgroundColor',
        'player_channel_title_color'    => 'youtubeAndFolderVideoTitleColor',
        'player_search_bg_color'        => 'searchInputBackgroundColor',
        'player_search_text_color'      => 'searchInputColor',
        'player_selector_bg_selected'   => 'mainSelectorBackgroundSelectedColor',
        'player_selector_text_normal'   => 'mainSelectorTextNormalColor',
        'player_selector_text_selected' => 'mainSelectorTextSelectedColor',
        'player_preloader_bg'           => 'preloaderBackgroundColor',
        'player_preloader_fill'         => 'preloaderFillColor',
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
        // Controller layout — engine default volumeScrubberHeight is only 10px, which
        // makes the hover volume slider unusable; give it a proper height.
        'controllerHeight'      => 50,
        'volumeScrubberHeight'  => 80,
        'showButtonsToolTips'   => 'yes',
        'backgroundColor'       => player_setting('player_bg_color'),
        'videoBackgroundColor'  => '#000000',
        'posterBackgroundColor' => '#000000',
        'playlistPosition'      => $pos,
        'playlistRightWidth'    => (int) player_setting('player_playlist_right_width'),
        'thumbnailWidth'        => (int) player_setting('player_thumb_width'),
        'thumbnailHeight'       => (int) player_setting('player_thumb_height'),
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
        'showNextAndPrevButtonsInController' => player_yn('player_show_prevnext_controller'),
        'showLoopButton'        => player_yn('player_show_loop_button'),
        'showShuffleButton'     => player_yn('player_show_shuffle_button'),
        'showPlaylistButtonAndPlaylist' => player_yn('player_show_playlist_button'),
        'showSubtitleButton'    => player_yn('player_show_subtitle_button'),
        'showYoutubeQualityButton' => player_yn('player_show_quality_button'),
        'showAudioTracksButton' => player_yn('player_show_audio_tracks_button'),
        'showChromecastButton'  => player_yn('player_show_chromecast_button'),
        'show360DegreeVideoVrButton' => player_yn('player_show_vr_button'),
        // Colors
        'useHEXColorsForSkin'   => player_yn('player_use_hex_colors'),
    ] + array_combine(
        array_values(player_color_map()),
        array_map('player_setting', array_keys(player_color_map()))
    );
}

/** Render a responsive `new FWDUVPlayer({...})` call from a full config array. */
function uvp_player_script(array $config): string
{
    $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    // On phones, move the channel playlist below the video so it gets full width.
    return '(function(){var cfg=' . $json . ';'
         . 'if(window.matchMedia&&window.matchMedia("(max-width:760px)").matches){'
         . 'cfg.playlistPosition="bottom";cfg.playlistBottomHeight=240;}'
         . 'new FWDUVPlayer(cfg);})();';
}

/** <head> assets for any page that embeds the player: UVP CSS + channel-name styling. */
function player_head_assets(): string
{
    $base  = url('player');
    $size  = max(8, (int) player_setting('player_channel_name_size'));
    $align = in_array(player_setting('player_channel_name_align'), ['left', 'center', 'right'], true)
        ? player_setting('player_channel_name_align') : 'left';
    $justify = $align === 'center' ? 'center' : ($align === 'right' ? 'flex-end' : 'flex-start');
    $gap = $align === 'left' ? '10' : '0';

    $css = '.fwduvp-playlist-thumbnail-dark-text,.fwduvp-playlist-thumbnail-white-text{'
         . 'display:flex !important;align-items:center !important;justify-content:' . $justify . ' !important;}'
         . '.sp-chname{font-size:' . $size . 'px !important;line-height:1.2;margin-left:' . $gap . 'px;}';

    return '<link rel="stylesheet" href="' . e($base . '/css/fwduvp.css') . '">'
         . '<link rel="stylesheet" href="' . e($base . '/css/fwd_ui.css') . '">'
         . '<style>' . $css . '</style>';
}

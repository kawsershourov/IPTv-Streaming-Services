<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$skins = ['minimal_skin_dark', 'minimal_skin_white', 'classic_skin_dark', 'classic_skin_white',
          'metal_skin_dark', 'metal_skin_white', 'modern_skin_dark', 'modern_skin_white'];

// yes/no checkbox settings => label
$toggles = [
    'player_autoplay'                 => 'Autoplay on load',
    'player_use_vector_icons'         => 'Use vector icons (instead of graphic skin icons)',
    'player_show_playlist_by_default' => 'Open the channel playlist on page load (otherwise it stays closed until the playlist button is clicked)',
    'player_use_playlists_select_box' => 'Show the playlists dropdown (All Channels / categories)',
    'player_show_search'              => 'Show the playlist search box',
    'player_use_hex_colors'           => 'Recolor controller buttons with the “Controller buttons” color',
];

// Color setting => label (keys come from player_color_map()).
$colorLabels = [
    'player_buttons_color'          => 'Controller buttons',
    'player_time_color'             => 'Time text',
    'player_playlist_bg_color'      => 'Playlist background',
    'player_playlist_name_color'    => 'Playlist title text',
    'player_thumb_normal_bg'        => 'Channel item background',
    'player_thumb_hover_bg'         => 'Channel item (hover)',
    'player_channel_title_color'    => 'Channel name text',
    'player_search_bg_color'        => 'Search box background',
    'player_search_text_color'      => 'Search box text',
    'player_selector_bg_selected'   => 'Dropdown selected background',
    'player_selector_text_normal'   => 'Dropdown text',
    'player_selector_text_selected' => 'Dropdown selected text',
    'player_preloader_bg'           => 'Loader background',
    'player_preloader_fill'         => 'Loader fill',
];
// Control-bar buttons grouped by where they sit on the bar.
$leftButtons = [
    'player_show_prevnext_controller' => 'Rewind / forward (◀◀ ▶▶)',
    'player_show_rewind_button'       => 'Replay (rewind 10s)',
];
$rightButtons = [
    'player_show_volume_button'       => 'Volume',
    'player_show_playlist_button'     => 'Playlist list',
    'player_show_subtitle_button'     => 'Subtitles / CC',
    'player_show_audio_tracks_button' => 'Audio tracks',
    'player_show_share_button'        => 'Share',
    'player_show_embed_button'        => 'Embed',
    'player_show_playbackrate_button' => 'Playback speed',
    'player_show_quality_button'      => 'Quality (HD)',
    'player_show_chromecast_button'   => 'Chromecast',
    'player_show_vr_button'           => '360 / VR',
    'player_show_info_button'         => 'Info',
    'player_show_download_button'     => 'Download',
    'player_show_time'                => 'Time / duration',
    'player_show_fullscreen_button'   => 'Fullscreen',
];
$playlistButtons = [
    'player_show_next_prev'           => 'Next / previous (playlist)',
    'player_show_loop_button'         => 'Loop',
    'player_show_shuffle_button'      => 'Shuffle',
];
$buttons = $leftButtons + $rightButtons + $playlistButtons; // all, for the save loop

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    Setting::set('default_skin', in_array($_POST['default_skin'] ?? '', $skins, true) ? $_POST['default_skin'] : 'minimal_skin_dark');

    $color = trim($_POST['player_bg_color'] ?? '#000000');
    Setting::set('player_bg_color', preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#000000');

    Setting::set('player_max_width',  (string) max(320, (int) ($_POST['player_max_width'] ?? 1280)));
    Setting::set('player_max_height', (string) max(180, (int) ($_POST['player_max_height'] ?? 720)));

    $vol = (float) ($_POST['player_volume'] ?? 0.8);
    $vol = max(0, min(1, $vol));
    Setting::set('player_volume', (string) $vol);

    Setting::set('player_playlist_position', ($_POST['player_playlist_position'] ?? 'right') === 'bottom' ? 'bottom' : 'right');
    Setting::set('player_playlist_right_width', (string) max(160, (int) ($_POST['player_playlist_right_width'] ?? 320)));

    foreach (array_merge(array_keys($toggles), array_keys($buttons)) as $key) {
        Setting::set($key, isset($_POST[$key]) ? 'yes' : 'no');
    }

    // Colors (validate #rrggbb, fall back to default).
    $defaults = player_setting_defaults();
    foreach (array_keys(player_color_map()) as $ck) {
        $val = strtolower(trim($_POST[$ck] ?? ''));
        Setting::set($ck, preg_match('/^#[0-9a-f]{6}$/', $val) ? $val : $defaults[$ck]);
    }

    flash('success', 'Player settings saved.');
    redirect('admin/player.php');
}

/** Render a color-picker row for a player setting. */
function player_color_input(string $key, string $label): void
{
    echo '<label class="color-field">' . e($label)
       . '<input type="color" name="' . e($key) . '" value="' . e(player_setting($key)) . '"></label>';
}

/** Render a yes/no checkbox row for a player setting. */
function player_check(string $key, string $label): void
{
    $on = player_setting($key) === 'yes';
    echo '<label class="check"><input type="checkbox" name="' . e($key) . '" ' . ($on ? 'checked' : '') . '> ' . e($label) . '</label>';
}

$adminTitle = 'Player';
$activeNav  = 'player';
require __DIR__ . '/includes/header.php';
?>
<h1>Player settings</h1>
<p class="muted" style="margin-top:-8px;">These control the FWD Ultimate Video Player on the home page and watch pages.</p>

<div class="admin-form" style="max-width:760px;">
    <form method="post" action="<?= e(url('admin/player.php')) ?>" class="form">
        <?= csrf_field() ?>

        <h2 style="font-size:16px;margin:4px 0 12px;">Appearance</h2>
        <div class="row2">
            <label>Skin
                <select name="default_skin">
                    <?php foreach ($skins as $s): ?>
                        <option value="<?= e($s) ?>" <?= player_setting('default_skin') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Background color
                <input type="color" name="player_bg_color" value="<?= e(player_setting('player_bg_color')) ?>" style="height:42px;padding:4px;">
            </label>
        </div>
        <div class="row2">
            <label>Max width (px) <input type="number" name="player_max_width" value="<?= e(player_setting('player_max_width')) ?>"></label>
            <label>Max height (px) <input type="number" name="player_max_height" value="<?= e(player_setting('player_max_height')) ?>"></label>
        </div>
        <?php player_check('player_use_vector_icons', $toggles['player_use_vector_icons']); ?>

        <h2 style="font-size:16px;margin:18px 0 12px;">Playback</h2>
        <?php player_check('player_autoplay', $toggles['player_autoplay']); ?>
        <label>Start volume (0.0 – 1.0)
            <input type="number" name="player_volume" min="0" max="1" step="0.1" value="<?= e(player_setting('player_volume')) ?>">
        </label>

        <h2 style="font-size:16px;margin:18px 0 12px;">Channel playlist</h2>
        <div class="row2">
            <label>Playlist position
                <select name="player_playlist_position">
                    <option value="right"  <?= player_setting('player_playlist_position') === 'right' ? 'selected' : '' ?>>Right (vertical)</option>
                    <option value="bottom" <?= player_setting('player_playlist_position') === 'bottom' ? 'selected' : '' ?>>Bottom (horizontal)</option>
                </select>
            </label>
            <label>Right playlist width (px) <input type="number" name="player_playlist_right_width" value="<?= e(player_setting('player_playlist_right_width')) ?>"></label>
        </div>
        <?php player_check('player_show_playlist_by_default', $toggles['player_show_playlist_by_default']); ?>
        <?php player_check('player_use_playlists_select_box', $toggles['player_use_playlists_select_box']); ?>
        <?php player_check('player_show_search', $toggles['player_show_search']); ?>

        <h2 style="font-size:16px;margin:18px 0 12px;">Control bar — left side</h2>
        <?php foreach ($leftButtons as $key => $label) { player_check($key, $label); } ?>
        <p class="muted" style="margin:-6px 0 8px;font-size:13px;">Play / pause is always shown.</p>

        <h2 style="font-size:16px;margin:18px 0 12px;">Control bar — right side</h2>
        <div style="columns:2;-webkit-columns:2;">
            <?php foreach ($rightButtons as $key => $label) { player_check($key, $label); } ?>
        </div>

        <h2 style="font-size:16px;margin:18px 0 12px;">Playlist controls</h2>
        <?php foreach ($playlistButtons as $key => $label) { player_check($key, $label); } ?>

        <h2 style="font-size:16px;margin:18px 0 12px;">Colors</h2>
        <?php player_check('player_use_hex_colors', $toggles['player_use_hex_colors']); ?>
        <div class="color-grid">
            <?php foreach ($colorLabels as $key => $label) { player_color_input($key, $label); } ?>
        </div>

        <div class="form-actions" style="margin-top:16px;"><button class="btn btn-primary">Save player settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

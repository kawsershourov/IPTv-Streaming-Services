<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$skins = ['minimal_skin_dark', 'minimal_skin_white', 'classic_skin_dark', 'classic_skin_white',
          'metal_skin_dark', 'metal_skin_white', 'modern_skin_dark', 'modern_skin_white'];

// yes/no checkbox settings => label
$toggles = [
    'player_autoplay'                 => 'Autoplay on load',
    'player_use_vector_icons'         => 'Use vector icons (instead of graphic skin icons)',
    'player_show_playlist_by_default' => 'Show the channel playlist by default',
    'player_use_playlists_select_box' => 'Show the playlists dropdown (All Channels / categories)',
    'player_show_search'              => 'Show the playlist search box',
];
$buttons = [
    'player_show_fullscreen_button'   => 'Fullscreen button',
    'player_show_volume_button'       => 'Volume button',
    'player_show_time'                => 'Time / duration',
    'player_show_next_prev'           => 'Next / previous buttons',
    'player_show_playbackrate_button' => 'Playback-rate button',
    'player_show_rewind_button'       => 'Rewind button',
    'player_show_info_button'         => 'Info button',
    'player_show_share_button'        => 'Share button',
    'player_show_embed_button'        => 'Embed button',
    'player_show_download_button'     => 'Download button',
    'player_show_loop_button'         => 'Loop button',
    'player_show_shuffle_button'      => 'Shuffle button',
];

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

    flash('success', 'Player settings saved.');
    redirect('admin/player.php');
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

        <h2 style="font-size:16px;margin:18px 0 12px;">Controller buttons</h2>
        <div style="columns:2;-webkit-columns:2;">
            <?php foreach ($buttons as $key => $label) { player_check($key, $label); } ?>
        </div>

        <div class="form-actions" style="margin-top:16px;"><button class="btn btn-primary">Save player settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

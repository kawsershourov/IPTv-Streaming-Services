<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$skins = ['minimal_skin_dark', 'minimal_skin_white', 'classic_skin_dark', 'classic_skin_white',
          'metal_skin_dark', 'metal_skin_white', 'modern_skin_dark', 'modern_skin_white'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    Setting::set('site_name',             trim($_POST['site_name'] ?? 'SunPlex'));
    Setting::set('site_tagline',          trim($_POST['site_tagline'] ?? ''));
    Setting::set('registration_open',     isset($_POST['registration_open']) ? '1' : '0');
    Setting::set('guest_access',          isset($_POST['guest_access']) ? '1' : '0');
    Setting::set('subscriptions_enabled', isset($_POST['subscriptions_enabled']) ? '1' : '0');
    $skin = in_array($_POST['default_skin'] ?? '', $skins, true) ? $_POST['default_skin'] : 'minimal_skin_dark';
    Setting::set('default_skin', $skin);
    Setting::set('player_width',  (string) max(320, (int) ($_POST['player_width'] ?? 960)));
    Setting::set('player_height', (string) max(180, (int) ($_POST['player_height'] ?? 540)));
    flash('success', 'Settings saved.');
    redirect('admin/settings.php');
}

$adminTitle = 'Settings';
$activeNav  = 'settings';
require __DIR__ . '/includes/header.php';
?>
<h1>Settings</h1>
<div class="admin-form">
    <form method="post" action="<?= e(url('admin/settings.php')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Site name <input type="text" name="site_name" value="<?= e(Setting::get('site_name', 'SunPlex')) ?>"></label>
        <label>Tagline <input type="text" name="site_tagline" value="<?= e(Setting::get('site_tagline', '')) ?>"></label>

        <label class="check"><input type="checkbox" name="registration_open" <?= Setting::get('registration_open', '1') === '1' ? 'checked' : '' ?>> Allow new user registration</label>

        <label class="check">
            <input type="checkbox" name="guest_access" <?= Setting::get('guest_access', '0') === '1' ? 'checked' : '' ?>>
            Guest viewing (watch without logging in)
        </label>
        <p class="muted" style="margin:-8px 0 16px;font-size:13px;">
            When ON, visitors can watch channels without an account. With subscriptions also ON they
            get the free channels (premium still asks them to log in); with subscriptions OFF they can watch everything.
        </p>

        <label class="check">
            <input type="checkbox" name="subscriptions_enabled" <?= Setting::get('subscriptions_enabled', '1') === '1' ? 'checked' : '' ?>>
            Subscriptions / plans active
        </label>
        <p class="muted" style="margin:-8px 0 16px;font-size:13px;">
            When OFF, the plans page and premium gating are disabled and every signed-in member can watch all channels.
        </p>

        <div class="row2">
            <label>Default player skin
                <select name="default_skin">
                    <?php foreach ($skins as $s): ?>
                        <option value="<?= e($s) ?>" <?= Setting::get('default_skin', 'minimal_skin_dark') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div></div>
        </div>
        <div class="row2">
            <label>Player width (px) <input type="number" name="player_width" value="<?= e(Setting::get('player_width', '960')) ?>"></label>
            <label>Player height (px) <input type="number" name="player_height" value="<?= e(Setting::get('player_height', '540')) ?>"></label>
        </div>

        <div class="form-actions"><button class="btn btn-primary">Save settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

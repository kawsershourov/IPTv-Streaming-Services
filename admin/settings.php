<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    Setting::set('site_name',             trim($_POST['site_name'] ?? 'SunPlex'));
    Setting::set('site_tagline',          trim($_POST['site_tagline'] ?? ''));
    Setting::set('registration_open',     isset($_POST['registration_open']) ? '1' : '0');
    Setting::set('guest_access',          isset($_POST['guest_access']) ? '1' : '0');
    Setting::set('subscriptions_enabled', isset($_POST['subscriptions_enabled']) ? '1' : '0');
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

        <p class="muted" style="font-size:13px;">Player appearance and controls are managed on the
            <a href="<?= e(url('admin/player.php')) ?>">Player</a> page.</p>

        <div class="form-actions"><button class="btn btn-primary">Save settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

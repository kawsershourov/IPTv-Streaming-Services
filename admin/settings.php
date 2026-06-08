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

    // Site logo: remove, or upload a new one.
    if (isset($_POST['remove_logo'])) {
        Setting::set('site_logo', '');
    } elseif ($logo = upload_image('site_logo_file', 'site')) {
        Setting::set('site_logo', $logo);
    }

    // Site icon (favicon): remove, or upload a new one.
    if (isset($_POST['remove_icon'])) {
        Setting::set('site_icon', '');
    } elseif ($icon = upload_image('site_icon_file', 'icon')) {
        Setting::set('site_icon', $icon);
    }

    flash('success', 'Settings saved.');
    redirect('admin/settings.php');
}

$adminTitle = 'Settings';
$activeNav  = 'settings';
require __DIR__ . '/includes/header.php';
?>
<h1>Settings</h1>
<div class="admin-form">
    <form method="post" action="<?= e(url('admin/settings.php')) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Site name <input type="text" name="site_name" value="<?= e(Setting::get('site_name', 'SunPlex')) ?>"></label>
        <label>Tagline <input type="text" name="site_tagline" value="<?= e(Setting::get('site_tagline', '')) ?>"></label>

        <?php $siteLogo = Setting::get('site_logo', ''); ?>
        <label>Site logo (header) <input type="file" name="site_logo_file" accept="image/*,.ico,.svg"></label>
        <?php if ($siteLogo): ?>
            <p class="muted" style="margin:-8px 0 6px;">Current logo:
                <img src="<?= e($siteLogo) ?>" alt="logo" style="height:28px;vertical-align:middle;background:#11151f;padding:3px 6px;border-radius:6px;">
            </p>
            <label class="check"><input type="checkbox" name="remove_logo"> Remove logo (use the text “SunPlex” instead)</label>
        <?php else: ?>
            <p class="muted" style="margin:-8px 0 16px;font-size:13px;">No logo set — the text “SunPlex” shows in the header.</p>
        <?php endif; ?>

        <?php $siteIcon = Setting::get('site_icon', ''); ?>
        <label>Site icon / favicon <input type="file" name="site_icon_file" accept="image/*,.ico,.svg"></label>
        <?php if ($siteIcon): ?>
            <p class="muted" style="margin:-8px 0 6px;">Current icon:
                <img src="<?= e($siteIcon) ?>" alt="icon" style="height:24px;vertical-align:middle;background:#11151f;padding:3px;border-radius:6px;">
            </p>
            <label class="check"><input type="checkbox" name="remove_icon"> Remove site icon</label>
        <?php else: ?>
            <p class="muted" style="margin:-8px 0 16px;font-size:13px;">No icon set — browsers use their default tab icon.</p>
        <?php endif; ?>
        <p class="muted" style="margin:-4px 0 16px;font-size:12px;">Supported: PNG, JPG, JPEG, GIF, WEBP, SVG, ICO, BMP, AVIF.</p>

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

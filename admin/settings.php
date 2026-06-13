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
    Setting::set('show_visitor_stats',    isset($_POST['show_visitor_stats']) ? '1' : '0');
    Setting::set('stats_refresh',         (string) max(0, min(600, (int) ($_POST['stats_refresh'] ?? 30))));

    // Site logo: remove, pick from Media (URL), or upload a new file.
    if (isset($_POST['remove_logo'])) {
        Setting::set('site_logo', '');
    } elseif (trim($_POST['site_logo_url'] ?? '') !== '') {
        Setting::set('site_logo', trim($_POST['site_logo_url']));
    } elseif ($logo = upload_image('site_logo_file', 'site')) {
        Setting::set('site_logo', $logo);
    }
    Setting::set('site_logo_width', (string) max(40, min(400, (int) ($_POST['site_logo_width'] ?? 160))));

    // Site icon (favicon): remove, pick from Media (URL), or upload a new file.
    if (isset($_POST['remove_icon'])) {
        Setting::set('site_icon', '');
    } elseif (trim($_POST['site_icon_url'] ?? '') !== '') {
        Setting::set('site_icon', trim($_POST['site_icon_url']));
    } elseif ($icon = upload_image('site_icon_file', 'icon')) {
        Setting::set('site_icon', $icon);
    }

    // Tracking / custom code (trusted admin input — stored and output verbatim).
    Setting::set('head_code',   (string) ($_POST['head_code'] ?? ''));
    Setting::set('footer_code', (string) ($_POST['footer_code'] ?? ''));

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
        <label>Site logo (header)</label>
        <span class="media-field" style="margin-bottom:12px;">
            <input type="text" id="siteLogoUrl" name="site_logo_url" value="" placeholder="Pick from Media or paste a URL">
            <button type="button" class="btn btn-outline btn-sm" data-media-target="#siteLogoUrl" data-media-url="<?= e(url('admin/media.php')) ?>">📁 Media</button>
        </span>
        <label>…or upload a logo file <input type="file" name="site_logo_file" accept="image/*,.ico,.svg"></label>
        <?php if ($siteLogo): ?>
            <p class="muted" style="margin:-8px 0 6px;">Current logo:
                <img src="<?= e($siteLogo) ?>" alt="logo" style="max-width:<?= (int) Setting::get('site_logo_width', '160') ?>px;max-height:46px;vertical-align:middle;background:#11151f;padding:3px 6px;border-radius:6px;">
            </p>
            <label class="check"><input type="checkbox" name="remove_logo"> Remove logo (use the text “SunPlex” instead)</label>
        <?php else: ?>
            <p class="muted" style="margin:-8px 0 16px;font-size:13px;">No logo set — the text “SunPlex” shows in the header.</p>
        <?php endif; ?>
        <label>Logo width in header (px) <input type="number" name="site_logo_width" min="40" max="400" value="<?= e(Setting::get('site_logo_width', '160')) ?>"></label>
        <p class="muted" style="margin:-8px 0 16px;font-size:12px;">The logo scales to this width and always fits the header height.</p>

        <?php $siteIcon = Setting::get('site_icon', ''); ?>
        <label>Site icon / favicon</label>
        <span class="media-field" style="margin-bottom:12px;">
            <input type="text" id="siteIconUrl" name="site_icon_url" value="" placeholder="Pick from Media or paste a URL">
            <button type="button" class="btn btn-outline btn-sm" data-media-target="#siteIconUrl" data-media-url="<?= e(url('admin/media.php')) ?>">📁 Media</button>
        </span>
        <label>…or upload an icon file <input type="file" name="site_icon_file" accept="image/*,.ico,.svg"></label>
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

        <label class="check">
            <input type="checkbox" name="show_visitor_stats" <?= Setting::get('show_visitor_stats', '1') === '1' ? 'checked' : '' ?>>
            Show the visitor stats bar on the public site (online / today / total visitors)
        </label>
        <label>Front-end stats auto-refresh (seconds) — <code>0</code> = update on page load only
            <input type="number" name="stats_refresh" min="0" max="600" value="<?= e(Setting::get('stats_refresh', '30')) ?>">
        </label>
        <p class="muted" style="margin:-8px 0 16px;font-size:12px;">
            How often each visitor's stats bar refreshes itself. Lower = more "live" but more requests.
            For big match/event traffic, set <strong>60</strong> (or <strong>0</strong> to stop visitor polling entirely). The admin reports always stay live. Minimum effective value is 15s.
        </p>

        <p class="muted" style="font-size:13px;">Player appearance and controls are managed on the
            <a href="<?= e(url('admin/player.php')) ?>">Player</a> page.</p>

        <h2 style="font-size:16px;margin:18px 0 10px;">Tracking &amp; custom code</h2>
        <label>Head code (Google Analytics, meta tags, etc.) — injected before &lt;/head&gt;
            <textarea name="head_code" rows="5" spellcheck="false" style="font-family:monospace;font-size:13px;"><?= e(Setting::get('head_code', '')) ?></textarea>
        </label>
        <label>Footer code — injected before &lt;/body&gt;
            <textarea name="footer_code" rows="4" spellcheck="false" style="font-family:monospace;font-size:13px;"><?= e(Setting::get('footer_code', '')) ?></textarea>
        </label>
        <p class="muted" style="margin:-8px 0 16px;font-size:12px;">
            Paste your Google Analytics / Tag Manager snippet here. It runs on the public site only (not the admin panel).
        </p>

        <div class="form-actions"><button class="btn btn-primary">Save settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

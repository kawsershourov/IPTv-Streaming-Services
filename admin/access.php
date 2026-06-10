<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    Setting::set('geo_enabled',           isset($_POST['geo_enabled']) ? '1' : '0');
    Setting::set('geo_apply_admin',       isset($_POST['geo_apply_admin']) ? '1' : '0');
    Setting::set('trust_proxy',           isset($_POST['trust_proxy']) ? '1' : '0');
    Setting::set('geo_allowed_countries', strtoupper(trim($_POST['geo_allowed_countries'] ?? '')));
    Setting::set('geo_allowed_ips',       trim($_POST['geo_allowed_ips'] ?? ''));
    Setting::set('geo_blocked_ips',       trim($_POST['geo_blocked_ips'] ?? ''));
    Setting::set('geo_block_unknown',     isset($_POST['geo_block_unknown']) ? '1' : '0');
    Setting::set('geo_block_message',     trim($_POST['geo_block_message'] ?? ''));
    Setting::set('geo_use_api',           isset($_POST['geo_use_api']) ? '1' : '0');
    Setting::set('geo_block_vpn',         isset($_POST['geo_block_vpn']) ? '1' : '0');
    Setting::set('geo_block_datacenter',  isset($_POST['geo_block_datacenter']) ? '1' : '0');
    flash('success', 'Access settings saved.');
    redirect('admin/access.php');
}

// --- Live diagnostics for the current request ---
$myIp      = client_ip();
$myCountry = detect_country($myIp);
$isPrivate = !filter_var($myIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

$adminTitle = 'Access';
$activeNav  = 'access';
require __DIR__ . '/includes/header.php';
?>
<h1>Access control — geo / IP restriction</h1>

<div class="card" style="margin-bottom:18px;">
    <h2 style="margin:0 0 10px;font-size:16px;">This request</h2>
    <div style="display:flex;gap:24px;flex-wrap:wrap;font-size:14px;">
        <div><span class="muted">Your IP</span><br><strong><?= e($myIp) ?></strong> <?= $isPrivate ? '<span class="tag tag-off">private</span>' : '' ?></div>
        <div><span class="muted">Detected country</span><br><strong><?= $myCountry ? e($myCountry) : '—' ?></strong></div>
        <div><span class="muted">Geo restriction</span><br><strong><?= Setting::get('geo_enabled', '0') === '1' ? 'ON' : 'OFF' ?></strong></div>
    </div>
    <?php if (Setting::get('geo_enabled', '0') === '1' && !$myCountry && Setting::get('geo_use_api', '0') !== '1'): ?>
        <p class="flash flash-error" style="margin:12px 0 0;">Country can't be detected on this server (no Cloudflare header / GeoIP module). The <strong>country allow-list won't work</strong> unless you enable “Use IP-API lookup” below, or put your site behind Cloudflare. Your <strong>IP allow-list still works</strong>.</p>
    <?php endif; ?>
</div>

<div class="admin-form" style="max-width:720px;">
    <form method="post" action="<?= e(url('admin/access.php')) ?>" class="form">
        <?= csrf_field() ?>

        <label class="check"><input type="checkbox" name="geo_enabled" <?= Setting::get('geo_enabled', '0') === '1' ? 'checked' : '' ?>> <strong>Enable access restriction</strong></label>
        <label class="check"><input type="checkbox" name="geo_apply_admin" <?= Setting::get('geo_apply_admin', '0') === '1' ? 'checked' : '' ?>> Also restrict the <strong>admin area</strong> (logged-in admins are never blocked)</label>

        <label class="check"><input type="checkbox" name="trust_proxy" <?= Setting::get('trust_proxy', '0') === '1' ? 'checked' : '' ?>> Site is behind <strong>Cloudflare / a proxy</strong> (read the real visitor IP from CF-Connecting-IP / X-Forwarded-For)</label>
        <p class="muted" style="margin:-8px 0 16px;font-size:12px;">
            ⚠️ Turn this ON <em>only</em> if you actually use Cloudflare or a load balancer. On direct hosting it must stay OFF, otherwise visitors could spoof an allowed IP. If your real IP shows as a Cloudflare address above, turn this ON.
        </p>

        <label>Allowed countries — ISO codes, comma separated (e.g. <code>BD,IN</code>). Leave empty for no country filter.
            <input type="text" name="geo_allowed_countries" value="<?= e(Setting::get('geo_allowed_countries', '')) ?>">
        </label>

        <label>Allowed IPs / ranges — one per line or semicolon-separated; supports CIDR (e.g. <code>103.118.78.0/24</code>). These always pass.
            <textarea name="geo_allowed_ips" rows="5" style="font-family:monospace;font-size:13px;"><?= e(Setting::get('geo_allowed_ips', '')) ?></textarea>
        </label>

        <label>Blocked IPs / ranges — always denied.
            <textarea name="geo_blocked_ips" rows="3" style="font-family:monospace;font-size:13px;"><?= e(Setting::get('geo_blocked_ips', '')) ?></textarea>
        </label>

        <label class="check"><input type="checkbox" name="geo_block_unknown" <?= Setting::get('geo_block_unknown', '0') === '1' ? 'checked' : '' ?>> Block visitors whose country can't be determined (strict — pairs with the IP allow-list to restrict to your network only)</label>

        <label>Block message (shown on the restricted page)
            <textarea name="geo_block_message" rows="4"><?= e(Setting::get('geo_block_message', '')) ?></textarea>
        </label>

        <h2 style="font-size:15px;margin:18px 0 10px;">Country detection (optional)</h2>
        <label class="check"><input type="checkbox" name="geo_use_api" <?= Setting::get('geo_use_api', '0') === '1' ? 'checked' : '' ?>> Use IP-API.com lookups (enables country + VPN/datacenter detection when not behind Cloudflare; results cached per IP for 7 days)</label>
        <label class="check"><input type="checkbox" name="geo_block_vpn" <?= Setting::get('geo_block_vpn', '0') === '1' ? 'checked' : '' ?>> Block VPN / proxy users (needs IP-API)</label>
        <label class="check"><input type="checkbox" name="geo_block_datacenter" <?= Setting::get('geo_block_datacenter', '0') === '1' ? 'checked' : '' ?>> Block datacenter / hosting IPs (needs IP-API)</label>

        <div class="form-actions"><button class="btn btn-primary">Save access settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

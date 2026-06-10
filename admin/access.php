<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

// --- GeoLite2 database upload -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_db') {
    csrf_verify();
    if (!empty($_FILES['geo_db']) && $_FILES['geo_db']['error'] === UPLOAD_ERR_OK) {
        $res = geo_db_install($_FILES['geo_db']['tmp_name'], (string) $_FILES['geo_db']['name']);
        flash($res['ok'] ? 'success' : 'error', $res['msg']);
    } else {
        $err  = $_FILES['geo_db']['error'] ?? UPLOAD_ERR_NO_FILE;
        $tooBig = in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
        flash('error', $tooBig
            ? 'File too large for this server\'s upload limit. Raise upload_max_filesize / post_max_size, or upload the raw .mmdb.'
            : 'No file received. Choose a GeoLite2 .tar.gz or .mmdb file.');
    }
    redirect('admin/access.php');
}

// --- Settings save -------------------------------------------------------
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
    flash('success', 'Access settings saved.');
    redirect('admin/access.php');
}

// --- Live diagnostics for the current request ---
$myIp      = client_ip();
$myCountry = detect_country($myIp);
$isPrivate = !filter_var($myIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
$db        = geo_db_status();

// --- "Test an IP" tool ---
$testIp     = trim($_GET['test_ip'] ?? '');
$testResult = ($testIp !== '' && filter_var($testIp, FILTER_VALIDATE_IP)) ? geo_evaluate($testIp) : null;
$testError  = ($testIp !== '' && $testResult === null) ? 'Not a valid IP address.' : '';

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
    <?php if ($isPrivate): ?>
        <p class="muted" style="margin:12px 0 0;font-size:13px;">You're on a local/private address, which has no country — that's why "Detected country" shows “—”. Use the <strong>Test an IP</strong> box below to check any public IP.</p>
    <?php elseif (Setting::get('geo_enabled', '0') === '1' && !$myCountry && !$db['installed']): ?>
        <p class="flash flash-error" style="margin:12px 0 0;">No GeoLite2 database is installed, so country can't be detected. The <strong>country allow-list won't work</strong> until you upload one below (or sit behind Cloudflare). Your <strong>IP allow-list still works</strong>.</p>
    <?php endif; ?>
</div>

<!-- Test an IP ---------------------------------------------------------- -->
<div class="card" style="margin-bottom:18px;">
    <h2 style="margin:0 0 10px;font-size:16px;">Test an IP</h2>
    <form method="get" action="<?= e(url('admin/access.php')) ?>" class="form" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <label style="flex:1;min-width:220px;margin:0;">IP address (IPv4 or IPv6)
            <input type="text" name="test_ip" value="<?= e($testIp) ?>" placeholder="e.g. 103.31.176.1 or 8.8.8.8">
        </label>
        <button class="btn btn-primary">Check</button>
    </form>
    <?php if ($testError): ?>
        <p class="flash flash-error" style="margin:12px 0 0;"><?= e($testError) ?></p>
    <?php elseif ($testResult): ?>
        <div style="margin-top:14px;display:flex;gap:24px;flex-wrap:wrap;align-items:center;font-size:14px;">
            <div><span class="muted">IP</span><br><strong><?= e($testIp) ?></strong></div>
            <div><span class="muted">Country</span><br><strong><?= $testResult['country'] ? e($testResult['country']) : '—' ?></strong></div>
            <div><span class="muted">Result</span><br>
                <strong style="color:<?= $testResult['allowed'] ? '#1e9e54' : '#d23f3f' ?>;">
                    <?= $testResult['allowed'] ? '✓ ALLOWED' : '✗ BLOCKED' ?>
                </strong>
            </div>
            <div style="flex:1;min-width:200px;"><span class="muted">Why</span><br><?= e($testResult['reason']) ?></div>
        </div>
    <?php endif; ?>
</div>

<!-- GeoLite2 database --------------------------------------------------- -->
<div class="card" style="margin-bottom:18px;">
    <h2 style="margin:0 0 10px;font-size:16px;">Country database (MaxMind GeoLite2)</h2>
    <?php if (!empty($db['installed']) && empty($db['error'])): ?>
        <p style="margin:0 0 12px;font-size:14px;">
            <span class="tag tag-on">Installed</span>
            <strong><?= e($db['type']) ?></strong> · built <?= e($db['build']) ?> ·
            IPv<?= (int) $db['ip_version'] ?> · <?= number_format($db['size'] / 1048576, 1) ?> MB
        </p>
    <?php elseif (!empty($db['error'])): ?>
        <p class="flash flash-error" style="margin:0 0 12px;">Installed file is not readable: <?= e($db['error']) ?></p>
    <?php else: ?>
        <p class="muted" style="margin:0 0 12px;font-size:14px;">No database installed yet. Download <code>GeoLite2-Country</code> from your MaxMind account and upload the <code>.tar.gz</code> here.</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="<?= e(url('admin/access.php')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_db">
        <label>Upload GeoLite2-Country database (<code>.tar.gz</code>, <code>.mmdb</code>, or <code>.mmdb.gz</code>)
            <input type="file" name="geo_db" accept=".tar.gz,.tgz,.mmdb,.gz" required>
        </label>
        <div class="form-actions"><button class="btn btn-primary">Upload database</button></div>
    </form>
</div>

<!-- Rules --------------------------------------------------------------- -->
<div class="admin-form" style="max-width:720px;">
    <form method="post" action="<?= e(url('admin/access.php')) ?>" class="form">
        <?= csrf_field() ?>

        <label class="check"><input type="checkbox" name="geo_enabled" <?= Setting::get('geo_enabled', '0') === '1' ? 'checked' : '' ?>> <strong>Enable access restriction</strong></label>
        <label class="check"><input type="checkbox" name="geo_apply_admin" <?= Setting::get('geo_apply_admin', '0') === '1' ? 'checked' : '' ?>> Also restrict the <strong>admin area</strong> (logged-in admins are never blocked)</label>

        <label class="check"><input type="checkbox" name="trust_proxy" <?= Setting::get('trust_proxy', '0') === '1' ? 'checked' : '' ?>> Site is behind <strong>Cloudflare / a proxy</strong> (read the real visitor IP from CF-Connecting-IP / X-Forwarded-For)</label>
        <p class="muted" style="margin:-8px 0 16px;font-size:12px;">
            ⚠️ Turn this ON <em>only</em> if you actually use Cloudflare, a load balancer, or an ngrok-style tunnel. On direct hosting it must stay OFF, otherwise visitors could spoof an allowed IP. If your real IP shows as a Cloudflare/tunnel address above, turn this ON.
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

        <div class="form-actions"><button class="btn btn-primary">Save access settings</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

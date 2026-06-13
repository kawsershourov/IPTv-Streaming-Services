<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($action === 'save_smtp') {
        Setting::set('smtp_host',      trim($_POST['smtp_host'] ?? ''));
        Setting::set('smtp_port',      (string) (int) ($_POST['smtp_port'] ?? 465));
        Setting::set('smtp_secure',    in_array($_POST['smtp_secure'] ?? 'ssl', ['ssl', 'tls', 'none'], true) ? $_POST['smtp_secure'] : 'ssl');
        Setting::set('smtp_user',      trim($_POST['smtp_user'] ?? ''));
        // Only overwrite the password when a new one is typed (the field is left blank otherwise).
        if (trim($_POST['smtp_pass'] ?? '') !== '') {
            Setting::set('smtp_pass', trim($_POST['smtp_pass']));
        }
        Setting::set('smtp_from',      trim($_POST['smtp_from'] ?? ''));
        Setting::set('smtp_from_name', trim($_POST['smtp_from_name'] ?? ''));
        Setting::set('notify_email',   trim($_POST['notify_email'] ?? ''));
        Setting::set('notify_errors',  isset($_POST['notify_errors']) ? '1' : '0');
        flash('success', 'Email settings saved.');
        redirect('admin/notifications.php');
    }

    if ($action === 'send_test') {
        $to = trim($_POST['test_to'] ?? '') ?: (string) Setting::get('notify_email', '');
        if ($to === '') {
            flash('error', 'Enter a recipient (or set a notification email).');
        } else {
            $err = null;
            $ok  = send_mail($to, ((string) Setting::get('site_name', 'SunPlex')) . ' — test email', mail_template('Your email is working ✅',
                '<p style="margin:0;">Your SMTP settings are correct, so channel-down alerts and website notifications will be delivered here.</p>'), $err);
            $n   = count(mail_parse_recipients($to));
            flash($ok ? 'success' : 'error', $ok ? "Test email sent to {$n} recipient(s)." : "Send failed: {$err}");
        }
        redirect('admin/notifications.php');
    }

    if ($action === 'save_health') {
        Setting::set('health_enabled',        isset($_POST['health_enabled']) ? '1' : '0');
        Setting::set('health_auto_hide',      isset($_POST['health_auto_hide']) ? '1' : '0');
        Setting::set('health_notify',         isset($_POST['health_notify']) ? '1' : '0');
        Setting::set('health_fail_threshold', (string) max(1, min(10, (int) ($_POST['health_fail_threshold'] ?? 2))));
        if (trim($_POST['health_cron_token'] ?? '') !== '') {
            Setting::set('health_cron_token', preg_replace('/[^a-zA-Z0-9]/', '', $_POST['health_cron_token']));
        }
        flash('success', 'Health-check settings saved.');
        redirect('admin/notifications.php');
    }

    if ($action === 'run_health') {
        $r     = run_health_check();
        $nf    = count($r['failing']);
        $names = implode(', ', array_map(static fn ($x) => $x['name'], array_slice($r['failing'], 0, 10)));
        $msg   = "Checked {$r['checked']} channel(s). Failing now: {$nf}"
            . ($nf ? " — {$names}" : '')
            . '. Newly hidden: ' . count($r['hidden']) . ', restored: ' . count($r['restored'])
            . ($r['emailed'] ? ' (email sent).' : '.');
        flash($nf ? 'error' : 'success', $msg);
        redirect('admin/notifications.php');
    }
}

// Generate a cron token on first visit if none set.
if ((string) Setting::get('health_cron_token', '') === '') {
    Setting::set('health_cron_token', bin2hex(random_bytes(12)));
}

$cronToken = (string) Setting::get('health_cron_token', '');
$cronUrl   = rtrim((string) config('site.base_url', ''), '/') . '/cron/health-check.php?token=' . $cronToken;
$downList  = db_all("SELECT name, stream_type, health_status, fail_count, last_checked_at, auto_hidden, status FROM channels WHERE health_status = 'down' OR auto_hidden = 1 ORDER BY last_checked_at DESC");

$adminTitle = 'Notifications';
$activeNav  = 'notifications';
require __DIR__ . '/includes/header.php';
?>
<h1>Notifications &amp; channel health</h1>

<!-- SMTP / email -------------------------------------------------------- -->
<div class="card" style="margin-bottom:18px;max-width:1080px;">
    <h2 style="margin:0 0 12px;font-size:16px;">Email (SMTP)</h2>
    <p class="muted" style="margin:0 0 14px;font-size:13px;">
        For Gmail: Host <code>smtp.gmail.com</code>, Port <code>465</code>, Security <code>SSL</code>, Username your full Gmail
        address, Password a <strong>Google App Password</strong> (Google Account → Security → App passwords — needs 2-Step Verification ON).
    </p>
    <form method="post" action="<?= e(url('admin/notifications.php')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_smtp">
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <label style="flex:2;min-width:220px;">SMTP host <input type="text" name="smtp_host" value="<?= e(Setting::get('smtp_host', '')) ?>" placeholder="smtp.gmail.com"></label>
            <label style="flex:1;min-width:90px;">Port <input type="number" name="smtp_port" value="<?= e(Setting::get('smtp_port', '465')) ?>"></label>
            <label style="flex:1;min-width:110px;">Security
                <select name="smtp_secure">
                    <?php $sec = Setting::get('smtp_secure', 'ssl'); ?>
                    <option value="ssl" <?= $sec === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
                    <option value="tls" <?= $sec === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
                    <option value="none" <?= $sec === 'none' ? 'selected' : '' ?>>None</option>
                </select>
            </label>
        </div>
        <label>Username (full email) <input type="text" name="smtp_user" value="<?= e(Setting::get('smtp_user', '')) ?>" placeholder="you@gmail.com"></label>
        <label>Password / App Password
            <input type="password" name="smtp_pass" value="" placeholder="<?= Setting::get('smtp_pass', '') !== '' ? '•••••••• (leave blank to keep)' : 'app password' ?>" autocomplete="new-password">
        </label>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <label style="flex:1;min-width:220px;">From address <input type="text" name="smtp_from" value="<?= e(Setting::get('smtp_from', '')) ?>" placeholder="defaults to username"></label>
            <label style="flex:1;min-width:220px;">From name <input type="text" name="smtp_from_name" value="<?= e(Setting::get('smtp_from_name', '')) ?>" placeholder="SunPlex"></label>
        </div>
        <label>Send alerts to (notification email)
            <input type="text" name="notify_email" value="<?= e(Setting::get('notify_email', '')) ?>" placeholder="you@gmail.com, ops@yourcompany.com">
        </label>
        <p class="muted" style="margin:-8px 0 16px;font-size:12px;">Separate multiple recipients with commas — every alert goes to all of them.</p>
        <label class="check"><input type="checkbox" name="notify_errors" <?= Setting::get('notify_errors', '0') === '1' ? 'checked' : '' ?>> Email me when the website hits a fatal error (throttled to once / 30 min)</label>
        <div class="form-actions"><button class="btn btn-primary">Save email settings</button></div>
    </form>

    <form method="post" action="<?= e(url('admin/notifications.php')) ?>" class="form" style="margin-top:14px;border-top:1px solid #283041;padding-top:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="send_test">
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <label style="flex:1;min-width:240px;margin:0;">Send a test email to
                <input type="text" name="test_to" value="<?= e(Setting::get('notify_email', '')) ?>" placeholder="recipient@example.com">
            </label>
            <button class="btn btn-outline">Send test</button>
        </div>
    </form>
</div>

<!-- Channel health ------------------------------------------------------ -->
<div class="card" style="margin-bottom:18px;max-width:1080px;">
    <h2 style="margin:0 0 12px;font-size:16px;">Channel health</h2>
    <form method="post" action="<?= e(url('admin/notifications.php')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_health">
        <label class="check"><input type="checkbox" name="health_enabled" <?= Setting::get('health_enabled', '0') === '1' ? 'checked' : '' ?>> Enable health checks</label>
        <label class="check"><input type="checkbox" name="health_auto_hide" <?= Setting::get('health_auto_hide', '1') === '1' ? 'checked' : '' ?>> Auto-hide a channel from the playlist when its stream is down</label>
        <label class="check"><input type="checkbox" name="health_notify" <?= Setting::get('health_notify', '1') === '1' ? 'checked' : '' ?>> Email me when channels go down / come back</label>
        <label>Mark a channel down after this many consecutive failures
            <input type="number" name="health_fail_threshold" min="1" max="10" value="<?= e(Setting::get('health_fail_threshold', '2')) ?>" style="max-width:100px;">
        </label>
        <div class="form-actions"><button class="btn btn-primary">Save health settings</button></div>
    </form>

    <form method="post" action="<?= e(url('admin/notifications.php')) ?>" style="margin-top:14px;border-top:1px solid #283041;padding-top:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="run_health">
        <button class="btn btn-outline">▶ Run health check now</button>
        <span class="muted" style="font-size:13px;margin-left:8px;">Tests every channel; may take a moment.</span>
    </form>

    <h3 style="margin:18px 0 8px;font-size:14px;">Schedule it (cron)</h3>
    <p class="muted" style="font-size:13px;margin:0 0 8px;">Add a cPanel cron job (every 15 min) using <strong>one</strong> of these:</p>
    <pre style="background:#0b0e14;border:1px solid #283041;border-radius:8px;padding:10px;font-size:12px;white-space:pre-wrap;word-break:break-all;color:#c0c8d8;">/usr/local/bin/php /home/USER/public_html/cron/health-check.php

# or via URL:
wget -qO- "<?= e($cronUrl) ?>"</pre>

    <?php if ($downList): ?>
        <h3 style="margin:18px 0 8px;font-size:14px;">Currently flagged</h3>
        <table class="table" style="width:100%;font-size:13px;">
            <thead><tr><th>Channel</th><th>Type</th><th>Health</th><th>Fails</th><th>Hidden?</th><th>Last checked</th></tr></thead>
            <tbody>
            <?php foreach ($downList as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['stream_type']) ?></td>
                    <td><strong style="color:<?= $c['health_status'] === 'down' ? '#d23f3f' : '#1e9e54' ?>"><?= e($c['health_status']) ?></strong></td>
                    <td><?= (int) $c['fail_count'] ?></td>
                    <td><?= ((int) $c['auto_hidden'] === 1) ? 'yes' : 'no' ?></td>
                    <td><?= e($c['last_checked_at'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="muted" style="font-size:13px;margin-top:16px;">No channels are flagged. ✅</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

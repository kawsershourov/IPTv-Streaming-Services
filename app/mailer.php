<?php
declare(strict_types=1);

/**
 * SMTP email — sends mail through an SMTP server (e.g. Gmail) configured in
 * Admin → Notifications. Self-contained SMTP client (SSL 465 or STARTTLS 587),
 * no Composer / PHPMailer dependency.
 *
 * Gmail note: use an "App Password" (Google Account → Security → App passwords),
 * not your normal password, and keep 2-Step Verification ON.
 */

/** Current SMTP settings from the admin panel. */
function smtp_settings(): array
{
    $user = trim((string) Setting::get('smtp_user', ''));
    return [
        'host'      => trim((string) Setting::get('smtp_host', '')),
        'port'      => (int) Setting::get('smtp_port', '465'),
        'user'      => $user,
        'pass'      => (string) Setting::get('smtp_pass', ''),
        'secure'    => (string) Setting::get('smtp_secure', 'ssl'), // ssl | tls | none
        'from'      => trim((string) Setting::get('smtp_from', '')) ?: $user,
        'from_name' => trim((string) Setting::get('smtp_from_name', '')) ?: (string) Setting::get('site_name', 'SunPlex'),
    ];
}

/** Is SMTP set up enough to send mail? */
function mailer_configured(): bool
{
    $s = smtp_settings();
    return $s['host'] !== '' && $s['user'] !== '' && $s['pass'] !== '' && $s['from'] !== '';
}

/** Parse a comma/semicolon separated address string into a list of valid emails. */
function mail_parse_recipients($to): array
{
    $list = is_array($to) ? $to : preg_split('/[,;]+/', (string) $to);
    $out  = [];
    foreach ($list as $addr) {
        $addr = trim((string) $addr);
        if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            $out[] = $addr;
        }
    }
    return array_values(array_unique($out));
}

/**
 * Send an HTML email. $to may be a single address or a comma/semicolon separated
 * list. Returns true on success; sets $error on failure.
 */
function send_mail($to, string $subject, string $html, ?string &$error = null): bool
{
    $s = smtp_settings();
    if ($s['host'] === '' || $s['from'] === '') {
        $error = 'SMTP is not configured (Admin → Notifications).';
        return false;
    }
    $recipients = mail_parse_recipients($to);
    if (!$recipients) {
        $error = 'No valid recipient email address.';
        return false;
    }
    try {
        (new SmtpMailer($s))->send($recipients, $subject, $html);
        return true;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        error_log('[SunPlex mail] ' . $error);
        return false;
    }
}

/** Send an alert to the configured notification address. */
function notify_admin(string $subject, string $html, ?string &$error = null): bool
{
    $to = trim((string) Setting::get('notify_email', '')) ?: smtp_settings()['from'];
    if ($to === '') {
        $error = 'No notification email set.';
        return false;
    }
    return send_mail($to, $subject, $html, $error);
}

/** Absolute public base URL of the site (for links/images in emails sent from cron). */
function mail_site_url(): string
{
    $stored = trim((string) Setting::get('site_url', ''));
    if ($stored !== '') {
        return rtrim($stored, '/');
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
    // Last resort: derive from the From address domain (e.g. admin@sunplex.live → https://sunplex.live).
    $from = smtp_settings()['from'];
    if (strpos($from, '@') !== false) {
        return 'https://' . ltrim(strrchr($from, '@'), '@');
    }
    return '';
}

/** Absolute URL of the site logo for use in emails, or '' if none/unknown. */
function mail_logo_url(): string
{
    $logo = trim((string) Setting::get('site_logo', ''));
    if ($logo === '') {
        return '';
    }
    // Most email clients (Gmail, Outlook) don't render SVG — fall back to text branding.
    if (preg_match('#\.svg($|\?)#i', $logo)) {
        return '';
    }
    if (preg_match('#^https?://#i', $logo)) {
        return $logo;
    }
    $path = asset_url($logo); // re-anchored path or full URL
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $base = mail_site_url();
    return $base !== '' ? $base . '/' . ltrim($path, '/') : '';
}

/** Wrap content in a professional, email-client-safe HTML shell with a logo header. */
function mail_template(string $heading, string $bodyHtml): string
{
    $site    = e((string) Setting::get('site_name', 'SunPlex'));
    $siteUrl = mail_site_url();
    $logoUrl = mail_logo_url();
    $font    = 'font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;';

    $header = $logoUrl !== ''
        ? '<img src="' . e($logoUrl) . '" alt="' . $site . '" height="42" style="height:42px;max-height:42px;width:auto;border:0;display:inline-block;">'
        : '<span style="font-size:22px;font-weight:800;color:#ff8a00;' . $font . '">' . $site . '</span>';

    $brandLink = $siteUrl !== ''
        ? '<a href="' . e($siteUrl) . '" style="color:#ff8a00;text-decoration:none;">' . $site . '</a>'
        : $site;

    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f4f6f9;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e6e9ef;border-radius:12px;overflow:hidden;">'
        . '<tr><td align="center" style="padding:22px 24px;background:#0b0e14;border-bottom:3px solid #ff8a00;">' . $header . '</td></tr>'
        . '<tr><td style="padding:30px 30px 10px;">'
        . '<h1 style="margin:0 0 14px;font-size:20px;color:#1a2030;' . $font . '">' . e($heading) . '</h1>'
        . '<div style="font-size:14px;color:#3a4252;line-height:1.65;' . $font . '">' . $bodyHtml . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 30px 26px;border-top:1px solid #eef1f5;color:#9aa3b2;font-size:12px;' . $font . '">'
        . 'This is an automated message from ' . $brandLink . '. &copy; ' . date('Y') . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/**
 * Email the admin about a fatal site error (throttled to once per 30 min so a
 * recurring error can't flood the inbox). Skipped in debug mode.
 */
function notify_site_error(array $err): void
{
    try {
        if (config('debug')) {
            return;
        }
        if (Setting::get('notify_errors', '0') !== '1' || !mailer_configured()) {
            return;
        }
        $last = (int) Setting::get('error_notify_last', '0');
        if (time() - $last < 1800) {
            return;
        }
        Setting::set('error_notify_last', (string) time());

        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? 'CLI');
        $body = '<p style="margin:0 0 12px;">A fatal error occurred on your website:</p>'
            . '<pre style="background:#f8f9fb;border:1px solid #e6e9ef;border-radius:8px;padding:12px;'
            . 'white-space:pre-wrap;word-break:break-word;color:#c0392b;font-size:13px;margin:0 0 12px;">'
            . e(($err['message'] ?? '') . "\n" . ($err['file'] ?? '') . ':' . ($err['line'] ?? '')) . '</pre>'
            . '<p style="margin:0;color:#6b7280;font-size:13px;">Page: ' . e($uri) . '</p>';
        notify_admin('⚠️ Website error on ' . (string) Setting::get('site_name', 'SunPlex'), mail_template('Website error', $body));
    } catch (\Throwable $e) {
        // never let error reporting cause more errors
    }
}

/** Minimal SMTP client (SSL / STARTTLS / plain). */
class SmtpMailer
{
    private array $cfg;
    /** @var resource */
    private $conn;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public function send($to, string $subject, string $html): void
    {
        $recipients = is_array($to) ? array_values($to) : [$to];
        $host   = $this->cfg['host'];
        $port   = (int) $this->cfg['port'];
        $secure = $this->cfg['secure'];

        $transport = ($secure === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $this->conn = @stream_socket_client("{$transport}:{$port}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->conn) {
            throw new RuntimeException("Connection to {$host}:{$port} failed: {$errstr}");
        }
        stream_set_timeout($this->conn, 20);
        $this->expect('220');

        $ehlo = $this->ehloName();
        $this->cmd("EHLO {$ehlo}", '250');

        if ($secure === 'tls') {
            $this->cmd('STARTTLS', '220');
            $ok = stream_socket_enable_crypto(
                $this->conn,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
            );
            if (!$ok) {
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            $this->cmd("EHLO {$ehlo}", '250');
        }

        $this->cmd('AUTH LOGIN', '334');
        $this->cmd(base64_encode($this->cfg['user']), '334');
        $this->cmd(base64_encode($this->cfg['pass']), '235');

        $from = $this->cfg['from'];
        $this->cmd("MAIL FROM:<{$from}>", '250');
        foreach ($recipients as $rcpt) {
            $this->cmd("RCPT TO:<{$rcpt}>", '250');
        }
        $this->cmd('DATA', '354');

        $message = $this->buildMessage($recipients, $subject, $html);
        $message = preg_replace('/^\./m', '..', $message); // dot-stuffing
        $this->write($message . "\r\n.");
        $this->expect('250');

        $this->write('QUIT');
        fclose($this->conn);
    }

    private function buildMessage(array $recipients, string $subject, string $html): string
    {
        $toHeader = implode(', ', array_map(static fn ($r) => '<' . $r . '>', $recipients));
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($this->cfg['from_name']) . ' <' . $this->cfg['from'] . '>',
            'To: ' . $toHeader,
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: SunPlex',
        ];
        $body = preg_replace("/\r\n|\r|\n/", "\r\n", $html);
        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function encodeHeader(string $v): string
    {
        return preg_match('/[^\x20-\x7e]/', $v) ? '=?UTF-8?B?' . base64_encode($v) . '?=' : $v;
    }

    private function ehloName(): string
    {
        $h = $_SERVER['SERVER_NAME'] ?? (gethostname() ?: 'localhost');
        return preg_replace('/[^a-zA-Z0-9.\-]/', '', (string) $h) ?: 'localhost';
    }

    private function cmd(string $cmd, string $code): void
    {
        $this->write($cmd);
        $this->expect($code);
    }

    private function write(string $data): void
    {
        fwrite($this->conn, $data . "\r\n");
    }

    private function expect(string $code): string
    {
        $resp = '';
        while (($line = fgets($this->conn, 600)) !== false) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break; // last line of a (possibly multiline) reply
            }
        }
        if (strncmp($resp, $code, strlen($code)) !== 0) {
            throw new RuntimeException('SMTP: expected ' . $code . ', got: ' . trim($resp));
        }
        return $resp;
    }
}

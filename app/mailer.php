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

/** Send an HTML email. Returns true on success; sets $error on failure. */
function send_mail(string $to, string $subject, string $html, ?string &$error = null): bool
{
    $s = smtp_settings();
    if ($s['host'] === '' || $s['from'] === '') {
        $error = 'SMTP is not configured (Admin → Notifications).';
        return false;
    }
    try {
        (new SmtpMailer($s))->send($to, $subject, $html);
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

/** Wrap content in a simple branded HTML email shell. */
function mail_template(string $heading, string $bodyHtml): string
{
    $site = e((string) Setting::get('site_name', 'SunPlex'));
    return '<div style="font-family:Segoe UI,Roboto,Arial,sans-serif;background:#0b0e14;padding:24px;color:#e8ecf3">'
        . '<div style="max-width:560px;margin:0 auto;background:#161b27;border:1px solid #283041;border-radius:14px;overflow:hidden">'
        . '<div style="padding:18px 24px;border-bottom:1px solid #283041;font-weight:800;font-size:18px">'
        . '<span style="color:#ff8a00">' . $site . '</span> <span style="color:#8a93a6;font-weight:600">notifications</span></div>'
        . '<div style="padding:24px"><h2 style="margin:0 0 12px;font-size:18px">' . e($heading) . '</h2>'
        . $bodyHtml . '</div>'
        . '<div style="padding:14px 24px;border-top:1px solid #283041;color:#5a6378;font-size:12px">'
        . 'Automated message from ' . $site . '.</div></div></div>';
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
        $body = '<p style="color:#8a93a6;line-height:1.6">A fatal error occurred on your website:</p>'
            . '<pre style="background:#0b0e14;border:1px solid #283041;border-radius:8px;padding:12px;'
            . 'white-space:pre-wrap;word-break:break-word;color:#ff8ea0;font-size:13px">'
            . e(($err['message'] ?? '') . "\n" . ($err['file'] ?? '') . ':' . ($err['line'] ?? '')) . '</pre>'
            . '<p style="color:#8a93a6;font-size:13px">Page: ' . e($uri) . '</p>';
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

    public function send(string $to, string $subject, string $html): void
    {
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
        $this->cmd("RCPT TO:<{$to}>", '250');
        $this->cmd('DATA', '354');

        $message = $this->buildMessage($to, $subject, $html);
        $message = preg_replace('/^\./m', '..', $message); // dot-stuffing
        $this->write($message . "\r\n.");
        $this->expect('250');

        $this->write('QUIT');
        fclose($this->conn);
    }

    private function buildMessage(string $to, string $subject, string $html): string
    {
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($this->cfg['from_name']) . ' <' . $this->cfg['from'] . '>',
            'To: <' . $to . '>',
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

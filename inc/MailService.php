<?php

class MailService
{
    private PDO $pdo;
    private string $storeFile;
    private const PASSWORD_PREFIX = 'enc:';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->storeFile = dirname(__DIR__) . '/hr-app/data/mail_settings.json';
    }

    public static function getDefaultResetSubject(): string
    {
        return 'إعادة تعيين كلمة المرور - Vision HR';
    }

    public static function getDefaultResetTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
</head>
<body style="margin:0;padding:0;background:#eef2ff;font-family:'Cairo','Segoe UI',Tahoma,sans-serif;color:#1f2937;direction:rtl;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#eef2ff;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 24px 48px rgba(13,33,165,.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#071466 0%,#0d21a5 52%,#6d7dcb 100%);padding:36px 28px;text-align:center;">
                            <img src="https://hr.gt-academy.com/dist/img/brand/logo-secondary.png" alt="Vision HR" style="max-width:180px;width:100%;height:auto;">
                            <div style="margin-top:18px;color:#ffffff;font-size:28px;font-weight:800;">إعادة تعيين كلمة المرور</div>
                            <div style="margin-top:10px;color:rgba(255,255,255,.82);font-size:15px;">رابط آمن لاستعادة الوصول إلى حسابك في نظام الموارد البشرية</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px 16px;">
                            <div style="font-size:18px;font-weight:700;color:#0d21a5;margin-bottom:14px;">مرحباً {{USER_NAME}}</div>
                            <div style="font-size:15px;line-height:1.9;color:#4b5563;">
                                تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك. إذا كان هذا الطلب منك، اضغط على الزر التالي لإكمال العملية بشكل آمن.
                            </div>
                            <div style="margin:28px 0;text-align:center;">
                                <a href="{{RESET_LINK}}" style="display:inline-block;background:linear-gradient(135deg,#0d21a5 0%,#3d4fb8 55%,#6d7dcb 100%);color:#ffffff !important;text-decoration:none;padding:15px 34px;border-radius:14px;font-size:16px;font-weight:700;box-shadow:0 12px 24px rgba(13,33,165,.22);">
                                    إعادة تعيين كلمة المرور
                                </a>
                            </div>
                            <div style="background:#e9edfd;border:1px solid #d4dcfb;border-radius:18px;padding:18px 20px;color:#0d21a5;line-height:1.8;">
                                هذا الرابط صالح لمدة {{EXPIRY_TIME}} فقط. إذا لم تطلب إعادة التعيين، يمكنك تجاهل هذه الرسالة وسيظل حسابك آمناً.
                            </div>
                            <div style="margin-top:22px;font-size:13px;color:#6b7280;line-height:1.8;">
                                إذا لم يعمل الزر، انسخ الرابط التالي والصقه في المتصفح:
                                <div style="margin-top:8px;word-break:break-all;color:#0a1a85;">{{RESET_LINK}}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 28px;">
                            <div style="height:1px;background:linear-gradient(90deg,transparent,#b9c7f8,transparent);margin:10px 0 20px;"></div>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <div style="font-size:13px;color:#6b7280;">هذه رسالة آلية من {{COMPANY_NAME}}</div>
                                <div style="font-size:12px;color:#9ca3af;">© {{CURRENT_YEAR}} {{COMPANY_NAME}}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    public function getSettings(bool $includeSecrets = false): array
    {
        $data = $this->readStore();
        $smtp = $data['smtp'] ?? [];
        $template = $data['template'] ?? [];
        $password = $this->decryptPassword($smtp['smtp_password'] ?? '');

        return [
            'smtp_host' => (string)($smtp['smtp_host'] ?? ''),
            'smtp_port' => (int)($smtp['smtp_port'] ?? 587),
            'smtp_encryption' => (string)($smtp['smtp_encryption'] ?? 'tls'),
            'smtp_username' => (string)($smtp['smtp_username'] ?? ''),
            'smtp_password' => $includeSecrets ? $password : '',
            'smtp_from_email' => (string)($smtp['smtp_from_email'] ?? ''),
            'smtp_from_name' => (string)($smtp['smtp_from_name'] ?? (defined('SITE_TITLE') ? SITE_TITLE : 'Vision HR')),
            'reset_email_subject' => (string)($template['reset_email_subject'] ?? self::getDefaultResetSubject()),
            'reset_email_template' => (string)($template['reset_email_template'] ?? self::getDefaultResetTemplate()),
            'has_smtp_password' => $password !== '',
        ];
    }

    public function saveSmtpSettings(array $input): array
    {
        $settings = $this->getEffectiveSmtpSettings($input);
        $missing = [];

        foreach (['smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_from_email', 'smtp_from_name'] as $key) {
            if ($settings[$key] === '' || $settings[$key] === 0) {
                $missing[] = $key;
            }
        }

        if (!filter_var($settings['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            return ['result' => false, 'msg' => 'بريد المرسل غير صالح'];
        }

        if (!in_array($settings['smtp_encryption'], ['tls', 'ssl'], true)) {
            return ['result' => false, 'msg' => 'نوع التشفير غير صالح'];
        }

        if ($settings['smtp_port'] < 1 || $settings['smtp_port'] > 65535) {
            return ['result' => false, 'msg' => 'منفذ SMTP غير صالح'];
        }

        if ($settings['smtp_password'] === '') {
            return ['result' => false, 'msg' => 'يرجى إدخال كلمة مرور SMTP مرة واحدة على الأقل'];
        }

        if (!empty($missing)) {
            return ['result' => false, 'msg' => 'يرجى تعبئة جميع حقول SMTP المطلوبة'];
        }

        $data = $this->readStore();
        $data['smtp'] = [
            'smtp_host' => $settings['smtp_host'],
            'smtp_port' => $settings['smtp_port'],
            'smtp_encryption' => $settings['smtp_encryption'],
            'smtp_username' => $settings['smtp_username'],
            'smtp_password' => $this->encryptPassword($settings['smtp_password']),
            'smtp_from_email' => $settings['smtp_from_email'],
            'smtp_from_name' => $settings['smtp_from_name'],
        ];

        $this->writeStore($data);

        return ['result' => true, 'msg' => 'تم حفظ إعدادات SMTP بنجاح'];
    }

    public function saveTemplateSettings(array $input): array
    {
        $subject = trim((string)($input['reset_email_subject'] ?? ''));
        $template = trim((string)($input['reset_email_template'] ?? ''));

        if ($subject === '' || $template === '') {
            return ['result' => false, 'msg' => 'العنوان والقالب مطلوبان'];
        }

        $data = $this->readStore();
        $data['template'] = [
            'reset_email_subject' => $subject,
            'reset_email_template' => $template,
        ];
        $this->writeStore($data);

        return ['result' => true, 'msg' => 'تم حفظ قالب البريد بنجاح'];
    }

    public function testConnection(array $input = []): array
    {
        try {
            $smtp = $this->getEffectiveSmtpSettings($input);
            $this->assertConfigured($smtp);

            $socket = $this->connect($smtp);
            $this->smtpHandshake($socket, $smtp);
            $this->smtpQuit($socket);

            return ['result' => true, 'msg' => 'تم الاتصال بخادم SMTP بنجاح'];
        } catch (Throwable $e) {
            error_log('MailService testConnection failed: ' . $e->getMessage());
            return ['result' => false, 'msg' => $e->getMessage()];
        }
    }

    public function sendTestEmail(string $toEmail): array
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['result' => false, 'msg' => 'البريد الإلكتروني غير صالح'];
        }

        try {
            $settings = $this->getSettings(true);
            $this->assertConfigured($settings);

            $subject = 'رسالة تجريبية من إعدادات Vision HR';
            $html = $this->renderTemplate(
                $settings['reset_email_template'],
                [
                    '{{USER_NAME}}' => 'مستخدم الاختبار',
                    '{{RESET_LINK}}' => rtrim(SITE_URL, '/') . '/reset-password?token=test-token',
                    '{{EXPIRY_TIME}}' => '60 دقيقة',
                    '{{CURRENT_YEAR}}' => date('Y'),
                    '{{COMPANY_NAME}}' => defined('SITE_TITLE') ? SITE_TITLE : 'Vision HR',
                ]
            );

            $this->sendHtmlEmail($toEmail, $subject, $html, 'مستخدم الاختبار', $settings);

            return ['result' => true, 'msg' => 'تم إرسال البريد التجريبي بنجاح'];
        } catch (Throwable $e) {
            error_log('MailService sendTestEmail failed: ' . $e->getMessage());
            return ['result' => false, 'msg' => $e->getMessage()];
        }
    }

    public function sendPasswordResetEmail(string $toEmail, string $userName, string $token, string $expiresAt): array
    {
        try {
            $settings = $this->getSettings(true);
            $this->assertConfigured($settings);

            $subject = $this->renderTemplate(
                $settings['reset_email_subject'],
                [
                    '{{USER_NAME}}' => $userName,
                    '{{RESET_LINK}}' => $this->buildResetUrl($token),
                    '{{EXPIRY_TIME}}' => $this->formatExpiryTime($expiresAt),
                    '{{CURRENT_YEAR}}' => date('Y'),
                    '{{COMPANY_NAME}}' => defined('SITE_TITLE') ? SITE_TITLE : 'Vision HR',
                ]
            );

            $html = $this->renderTemplate(
                $settings['reset_email_template'],
                [
                    '{{USER_NAME}}' => $userName,
                    '{{RESET_LINK}}' => $this->buildResetUrl($token),
                    '{{EXPIRY_TIME}}' => $this->formatExpiryTime($expiresAt),
                    '{{CURRENT_YEAR}}' => date('Y'),
                    '{{COMPANY_NAME}}' => defined('SITE_TITLE') ? SITE_TITLE : 'Vision HR',
                ]
            );

            $this->sendHtmlEmail($toEmail, $subject, $html, $userName, $settings);

            return ['result' => true, 'msg' => 'تم إرسال رسالة إعادة التعيين بنجاح'];
        } catch (Throwable $e) {
            error_log('MailService sendPasswordResetEmail failed: ' . $e->getMessage());
            return ['result' => false, 'msg' => $e->getMessage()];
        }
    }

    public function isConfigured(): bool
    {
        $settings = $this->getSettings(true);

        return $settings['smtp_host'] !== ''
            && $settings['smtp_port'] > 0
            && $settings['smtp_username'] !== ''
            && $settings['smtp_password'] !== ''
            && $settings['smtp_from_email'] !== ''
            && $settings['smtp_from_name'] !== '';
    }

    private function getEffectiveSmtpSettings(array $input): array
    {
        $current = $this->getSettings(true);

        return [
            'smtp_host' => trim((string)($input['smtp_host'] ?? $current['smtp_host'])),
            'smtp_port' => (int)($input['smtp_port'] ?? $current['smtp_port'] ?? 587),
            'smtp_encryption' => strtolower(trim((string)($input['smtp_encryption'] ?? $current['smtp_encryption'] ?? 'tls'))),
            'smtp_username' => trim((string)($input['smtp_username'] ?? $current['smtp_username'])),
            'smtp_password' => (string)(
                array_key_exists('smtp_password', $input) && trim((string)$input['smtp_password']) !== ''
                    ? trim((string)$input['smtp_password'])
                    : ($current['smtp_password'] ?? '')
            ),
            'smtp_from_email' => trim((string)($input['smtp_from_email'] ?? $current['smtp_from_email'])),
            'smtp_from_name' => trim((string)($input['smtp_from_name'] ?? $current['smtp_from_name'] ?? (defined('SITE_TITLE') ? SITE_TITLE : 'Vision HR'))),
        ];
    }

    private function assertConfigured(array $settings): void
    {
        foreach (['smtp_host', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'] as $key) {
            if (trim((string)($settings[$key] ?? '')) === '') {
                throw new RuntimeException('إعدادات SMTP غير مكتملة. يرجى مراجعة شاشة الإعدادات.');
            }
        }

        if (!filter_var($settings['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('بريد المرسل غير صالح');
        }

        if (!in_array($settings['smtp_encryption'], ['tls', 'ssl'], true)) {
            throw new RuntimeException('نوع تشفير SMTP غير مدعوم');
        }
    }

    private function sendHtmlEmail(string $toEmail, string $subject, string $html, string $toName, array $settings): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('البريد الإلكتروني غير صالح');
        }

        $socket = $this->connect($settings);

        try {
            $this->smtpHandshake($socket, $settings);

            $this->sendCommand($socket, 'MAIL FROM:<' . $settings['smtp_from_email'] . '>', [250]);
            $this->sendCommand($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
            $this->sendCommand($socket, 'DATA', [354]);

            $message = $this->buildMimeMessage(
                $settings['smtp_from_email'],
                $settings['smtp_from_name'],
                $toEmail,
                $toName,
                $subject,
                $html
            );

            fwrite($socket, $message . "\r\n.\r\n");
            $this->readResponse($socket, [250]);
            $this->smtpQuit($socket);
        } catch (Throwable $e) {
            $this->smtpQuit($socket);
            throw $e;
        }
    }

    private function buildMimeMessage(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $html
    ): string {
        $boundary = 'b1_' . bin2hex(random_bytes(12));
        $plain = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'From: ' . $this->formatAddress($fromEmail, $fromName),
            'To: ' . $this->formatAddress($toEmail, $toName ?: $toEmail),
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $this->messageHost() . '>',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = [];
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/plain; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: base64';
        $body[] = '';
        $body[] = trim(chunk_split(base64_encode($plain), 76, "\r\n"));
        $body[] = '';
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/html; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: base64';
        $body[] = '';
        $body[] = trim(chunk_split(base64_encode($html), 76, "\r\n"));
        $body[] = '';
        $body[] = '--' . $boundary . '--';

        return implode("\r\n", array_merge($headers, [''], $body));
    }

    private function connect(array $settings)
    {
        $scheme = $settings['smtp_encryption'] === 'ssl' ? 'ssl' : 'tcp';
        $target = $scheme . '://' . $settings['smtp_host'] . ':' . $settings['smtp_port'];

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'SNI_enabled' => true,
            ],
        ]);

        $socket = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException('تعذر الاتصال بخادم SMTP: ' . $errstr);
        }

        stream_set_timeout($socket, 15);
        $this->readResponse($socket, [220]);

        return $socket;
    }

    private function smtpHandshake($socket, array $settings): void
    {
        $this->sendCommand($socket, 'EHLO ' . $this->messageHost(), [250]);

        if ($settings['smtp_encryption'] === 'tls') {
            $this->sendCommand($socket, 'STARTTLS', [220]);
            $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoEnabled !== true) {
                throw new RuntimeException('فشل تفعيل تشفير TLS مع خادم البريد');
            }
            $this->sendCommand($socket, 'EHLO ' . $this->messageHost(), [250]);
        }

        $this->sendCommand($socket, 'AUTH LOGIN', [334]);
        $this->sendCommand($socket, base64_encode($settings['smtp_username']), [334]);
        $this->sendCommand($socket, base64_encode($settings['smtp_password']), [235]);
    }

    private function smtpQuit($socket): void
    {
        if (is_resource($socket)) {
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
        }
    }

    private function sendCommand($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->readResponse($socket, $expectedCodes);
    }

    private function readResponse($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('لم يتم استلام رد من خادم SMTP');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP ' . $code . ': ' . trim($response));
        }

        return $response;
    }

    private function renderTemplate(string $template, array $vars): string
    {
        return strtr($template, $vars);
    }

    private function buildResetUrl(string $token): string
    {
        return rtrim(SITE_URL, '/') . '/reset-password?token=' . rawurlencode($token);
    }

    private function formatExpiryTime(string $expiresAt): string
    {
        $seconds = strtotime($expiresAt) - time();
        if ($seconds <= 0) {
            return 'وقت محدود';
        }

        $minutes = (int)max(1, round($seconds / 60));
        if ($minutes >= 60 && $minutes % 60 === 0) {
            $hours = (int)($minutes / 60);
            return $hours . ' ساعة';
        }

        return $minutes . ' دقيقة';
    }

    private function formatAddress(string $email, string $name): string
    {
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function messageHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? gethostname() ?: 'localhost';
        return preg_replace('/:\d+$/', '', $host);
    }

    private function readStore(): array
    {
        if (!file_exists($this->storeFile)) {
            return [];
        }

        $json = @file_get_contents($this->storeFile);
        if ($json === false || trim($json) === '') {
            return [];
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function writeStore(array $data): void
    {
        $dir = dirname($this->storeFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->storeFile,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function encryptPassword(string $plainText): string
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return '';
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plainText, 'AES-256-CBC', $this->encryptionKey(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('تعذر تشفير كلمة مرور SMTP');
        }

        return self::PASSWORD_PREFIX . base64_encode($iv . $cipher);
    }

    private function decryptPassword(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (strpos($value, self::PASSWORD_PREFIX) !== 0) {
            return $value;
        }

        $raw = base64_decode(substr($value, strlen(self::PASSWORD_PREFIX)), true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $this->encryptionKey(), OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : $plain;
    }

    private function encryptionKey(): string
    {
        $base = getenv('MAIL_SETTINGS_KEY');
        if (!$base) {
            $base = (defined('SITE_URL') ? SITE_URL : 'vision-hr')
                . '|'
                . (defined('DB_HOST') ? DB_HOST : 'localhost')
                . '|'
                . (defined('DB_NAME') ? DB_NAME : 'db')
                . '|'
                . (defined('DB_USER') ? DB_USER : 'user')
                . '|'
                . (defined('DB_PASS') ? DB_PASS : 'pass');
        }

        return hash('sha256', $base, true);
    }
}

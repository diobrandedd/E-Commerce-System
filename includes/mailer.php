<?php

declare(strict_types=1);

require_once __DIR__ . '/app_settings.php';

const MAIL_SMTP_USER_KEY = 'mail.smtp_user';
const MAIL_SMTP_PASS_KEY = 'mail.smtp_pass';
const MAIL_FROM_NAME_KEY = 'mail.from_name';
const MAIL_APP_PUBLIC_BASE_KEY = 'mail.app_public_base';

/**
 * @return array{smtp_user: string, from_name: string, password_set: bool, app_public_base: string, host: string, port: int}
 */
function app_mail_settings_public(PDO $pdo): array
{
    return [
        'smtp_user' => (string) app_setting_get($pdo, MAIL_SMTP_USER_KEY, ''),
        'from_name' => (string) app_setting_get($pdo, MAIL_FROM_NAME_KEY, 'Pyx Tracker'),
        'password_set' => ((string) app_setting_get($pdo, MAIL_SMTP_PASS_KEY, '')) !== '',
        'app_public_base' => (string) app_setting_get($pdo, MAIL_APP_PUBLIC_BASE_KEY, ''),
        'host' => 'smtp.gmail.com',
        'port' => 587,
    ];
}

/**
 * @param ?string $smtpPass null = leave unchanged; '' = clear
 * @return array{smtp_user: string, from_name: string, password_set: bool, app_public_base: string, host: string, port: int}
 */
function app_mail_settings_save(
    PDO $pdo,
    string $smtpUser,
    string $fromName,
    ?string $smtpPass,
    string $appPublicBase,
    ?int $userId
): array {
    $smtpUser = trim($smtpUser);
    $fromName = trim($fromName);
    if ($fromName === '') {
        $fromName = 'Pyx Tracker';
    }
    if ($smtpUser !== '' && !filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Gmail address is not valid.');
    }
    $appPublicBase = rtrim(trim($appPublicBase), '/');
    app_setting_set($pdo, MAIL_SMTP_USER_KEY, $smtpUser, $userId);
    app_setting_set($pdo, MAIL_FROM_NAME_KEY, $fromName, $userId);
    app_setting_set($pdo, MAIL_APP_PUBLIC_BASE_KEY, $appPublicBase, $userId);
    if ($smtpPass !== null) {
        app_setting_set($pdo, MAIL_SMTP_PASS_KEY, trim($smtpPass), $userId);
    }

    return app_mail_settings_public($pdo);
}

function app_mail_configured(PDO $pdo): bool
{
    $pub = app_mail_settings_public($pdo);

    return $pub['smtp_user'] !== '' && $pub['password_set'];
}

/**
 * Absolute SPA base for approval links (no trailing slash), e.g. https://host/rts/app/dist
 */
function app_mail_spa_base_url(PDO $pdo): string
{
    $configured = rtrim(trim((string) app_setting_get($pdo, MAIL_APP_PUBLIC_BASE_KEY, '')), '/');
    if ($configured !== '') {
        return $configured;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $https ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    if ($host === '') {
        $host = 'localhost';
    }

    return $scheme . '://' . $host . '/rts/app/dist';
}

/**
 * Send HTML + plain email via Gmail SMTP (PHPMailer).
 *
 * @return array{ok: bool, error?: string}
 */
function app_mail_send(PDO $pdo, string $to, string $subject, string $html, string $text = ''): array
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid recipient email.'];
    }
    if (!app_mail_configured($pdo)) {
        return ['ok' => false, 'error' => 'Gmail SMTP is not configured in System Settings.'];
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return ['ok' => false, 'error' => 'PHPMailer is not installed (run composer require phpmailer/phpmailer).'];
    }
    require_once $autoload;

    $user = (string) app_setting_get($pdo, MAIL_SMTP_USER_KEY, '');
    $pass = (string) app_setting_get($pdo, MAIL_SMTP_PASS_KEY, '');
    $fromName = (string) app_setting_get($pdo, MAIL_FROM_NAME_KEY, 'Pyx Tracker');
    if ($fromName === '') {
        $fromName = 'Pyx Tracker';
    }
    if ($text === '') {
        $text = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)));
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($user, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();

        return ['ok' => true];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (class_exists(PHPMailer\PHPMailer\PHPMailer::class)) {
            // Prefer PHPMailer ErrorInfo when available
        }
        error_log('app_mail_send: ' . $msg);

        return ['ok' => false, 'error' => 'Could not send email: ' . $msg];
    }
}

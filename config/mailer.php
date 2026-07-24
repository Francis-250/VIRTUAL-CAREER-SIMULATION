<?php
use PHPMailer\PHPMailer\PHPMailer;
require_once dirname(__DIR__) . '/vendor/autoload.php';

function env_values(): array {
    static $env;
    if ($env !== null) return $env;
    $env = [];
    $path = dirname(__DIR__) . '/.env';
    if (is_file($path)) foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = trim($value, "\"'");
    }
    return $env;
}

function configured_mailer(): PHPMailer {
    $e = env_values();
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $e['SMTP_HOST'] ?? '';
    $mail->Port = (int)($e['SMTP_PORT'] ?? 587);
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Username = $e['SMTP_USER'] ?? '';
    $mail->Password = $e['SMTP_PASS'] ?? '';
    $from = $e['SMTP_FROM'] ?? ($e['SMTP_USER'] ?? '');
    $mail->setFrom($from, 'CareerSim');
    $mail->isHTML(true);
    return $mail;
}

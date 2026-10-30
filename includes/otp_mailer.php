
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function send_security_otp(string $otp, string $userName): bool
{
    // Load central SMTP configuration
    $config = require __DIR__ . '/mail_config.php';

    if (!is_array($config)) {
        error_log('OTP: Invalid mail configuration.');
        return false;
    }

    $required = [
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'from_otp'
    ];

    foreach ($required as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            error_log("OTP: Missing mail configuration: {$key}");
            return false;
        }
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

        // All SMTP settings come from mail_config.php
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->Port       = (int)$config['port'];

        if (strtolower($config['encryption']) === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (strtolower($config['encryption']) === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            throw new RuntimeException('Unsupported SMTP encryption setting.');
        }

        // Sender from central configuration
        $mail->setFrom(
            $config['from_email'],
            'Unire CRM Security'
        );

        // OTP receiver: same mailbox as SMTP username
        $mail->addAddress($config['username']);

        $mail->isHTML(true);
        $mail->Subject = 'Unire CRM Login Verification OTP';

        $safeName = htmlspecialchars(
            $userName,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeOtp = htmlspecialchars(
            $otp,
            ENT_QUOTES,
            'UTF-8'
        );

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;padding:25px'>
                <h2>Unire CRM Login Verification</h2>
                <p>Hello {$safeName},</p>
                <p>Your login verification OTP is:</p>
                <h1 style='color:#218c5a;letter-spacing:8px'>
                    {$safeOtp}
                </h1>
                <p>This OTP is valid for 5 minutes.</p>
                <p>If you did not initiate this login, please ignore this email.</p>
            </div>
        ";

        $mail->AltBody =
            "Your Unire CRM login OTP is {$otp}. Valid for 5 minutes.";

        $mail->send();

        return true;

    } catch (Throwable $e) {
        error_log('CRM OTP SMTP Error: ' . $e->getMessage());
        return false;
    }
}

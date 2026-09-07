<?php
/*
 * Daily Follow-up Reminder Cron
 * 1 day before: Pending/Hold
 * Same day: Pending/Hold
 * Overdue: Pending/Hold/Overdue once every day until Completed
 */

require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$mailConfig = require __DIR__ . '/../includes/mail_config.php';

/* Load CRM settings */
$settingsRows = fetch_all("
    SELECT setting_key, setting_value
    FROM crm_settings
    WHERE setting_key IN (
        'email_reminders',
        'reminder_1_day',
        'reminder_same_day',
        'reminder_overdue',
        'reminder_from_email',
        'reminder_from_name',
        'reminder_to_email',
        'reminder_cc_email',
        'timezone'
    )
");

$settings = [];
foreach ($settingsRows as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$timezone = $settings['timezone'] ?? 'Asia/Kolkata';
date_default_timezone_set($timezone);

$emailReminders  = $settings['email_reminders'] ?? '1';
$reminder1Day    = $settings['reminder_1_day'] ?? '1';
$reminderSameDay = $settings['reminder_same_day'] ?? '1';
$reminderOverdue = $settings['reminder_overdue'] ?? '1';

$fromEmail = trim($settings['reminder_from_email']
    ?? ($mailConfig['from_email'] ?? $mailConfig['username'] ?? ''));

$fromName = trim($settings['reminder_from_name']
    ?? ($mailConfig['from_name'] ?? 'CRM Follow-up Reminder'));

$toEmail = trim($settings['reminder_to_email'] ?? '');
$ccEmail = trim($settings['reminder_cc_email'] ?? '');

echo "CRM Follow-up Reminder\n";
echo "Time: " . date('d M Y h:i A') . "\n";

if ($emailReminders !== '1') {
    echo "Email reminders are disabled.\n";
    exit;
}

if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid or missing To Email in CRM Settings.\n";
    exit;
}

if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid or missing From Email in CRM Settings.\n";
    exit;
}

/*
 * Important:
 * Cron runs once per day, so we check whole calendar dates.
 */
$followups = fetch_all("
    SELECT
        f.id,
        f.client_id,
        f.followup_date,
        f.status,
        f.notes,
        f.platform,
        f.reminder_1_sent_at,
        f.reminder_same_day_sent_at,
        f.reminder_overdue_sent_at,
        CONCAT(c.first_name, ' ', c.last_name) AS client_name,
        co.company_name
    FROM follow_ups f
    INNER JOIN clients c ON c.id = f.client_id
    LEFT JOIN companies co ON co.id = c.company_id
    WHERE f.status IN ('Pending', 'Hold', 'Overdue')
      AND (
          DATE(f.followup_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
          OR DATE(f.followup_date) = CURDATE()
          OR DATE(f.followup_date) < CURDATE()
      )
    ORDER BY f.followup_date ASC, f.id ASC
");

echo "Follow-ups checked: " . count($followups) . "\n";

$sentCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($followups as $row) {

    $followupDate = date('Y-m-d', strtotime($row['followup_date']));
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $reminderType = null;

    /* 1 day before */
    if (
        $reminder1Day === '1'
        && in_array($row['status'], ['Pending', 'Hold'], true)
        && $followupDate === $tomorrow
        && empty($row['reminder_1_sent_at'])
    ) {
        $reminderType = '1_day';
    }

    /* Same day */
    elseif (
        $reminderSameDay === '1'
        && in_array($row['status'], ['Pending', 'Hold'], true)
        && $followupDate === $today
        && empty($row['reminder_same_day_sent_at'])
    ) {
        $reminderType = 'same_day';
    }

    /* Daily overdue */
    elseif (
        $reminderOverdue === '1'
        && in_array($row['status'], ['Pending', 'Hold', 'Overdue'], true)
        && $followupDate < $today
        && (
            empty($row['reminder_overdue_sent_at'])
            || date('Y-m-d', strtotime($row['reminder_overdue_sent_at'])) < $today
        )
    ) {
        $reminderType = 'overdue';
    }

    if (!$reminderType) {
        $skipCount++;
        continue;
    }

    $clientName = trim($row['client_name'] ?? '') ?: 'Client';
    $companyName = trim($row['company_name'] ?? '') ?: 'No Company';

    $formattedFollowupDate = date(
        'd M Y h:i A',
        strtotime($row['followup_date'])
    );

    if ($reminderType === '1_day') {
        $subject = "CRM Follow-up Reminder - Tomorrow - {$clientName}";
        $messageTitle = "Follow-up Reminder - Tomorrow";
        $messageText = "This follow-up is scheduled for tomorrow.";
    } elseif ($reminderType === 'same_day') {
        $subject = "CRM Follow-up Reminder - Today - {$clientName}";
        $messageTitle = "Follow-up Reminder - Today";
        $messageText = "This follow-up is scheduled for today.";
    } else {
        $subject = "CRM Follow-up OVERDUE - {$clientName}";
        $messageTitle = "Follow-up OVERDUE";
        $messageText = "This follow-up is overdue. A daily reminder will continue until it is completed.";
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mailConfig['port'];

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);

        if ($ccEmail !== '' && filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addCC($ccEmail);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $safeTitle = htmlspecialchars($messageTitle, ENT_QUOTES, 'UTF-8');
        $safeText = htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8');
        $safeClient = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
        $safeCompany = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
        $safeDate = htmlspecialchars($formattedFollowupDate, ENT_QUOTES, 'UTF-8');
        $safeStatus = htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8');
        $safePlatform = htmlspecialchars((string)($row['platform'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $safeNotes = nl2br(
            htmlspecialchars((string)($row['notes'] ?? '-'), ENT_QUOTES, 'UTF-8')
        );

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;font-size:14px;line-height:1.6'>
                <h2>{$safeTitle}</h2>
                <p>{$safeText}</p>
                <table cellpadding='6' cellspacing='0' border='0'>
                    <tr><td><strong>Client</strong></td><td>{$safeClient}</td></tr>
                    <tr><td><strong>Company</strong></td><td>{$safeCompany}</td></tr>
                    <tr><td><strong>Follow-up Date &amp; Time</strong></td><td>{$safeDate}</td></tr>
                    <tr><td><strong>Status</strong></td><td>{$safeStatus}</td></tr>
                    <tr><td><strong>Platform</strong></td><td>{$safePlatform}</td></tr>
                    <tr><td><strong>Notes</strong></td><td>{$safeNotes}</td></tr>
                </table>
                <p>Please check the CRM for complete follow-up details.</p>
            </div>
        ";

        $mail->AltBody =
            "{$messageTitle}\n\n" .
            "{$messageText}\n\n" .
            "Client: {$clientName}\n" .
            "Company: {$companyName}\n" .
            "Follow-up: {$formattedFollowupDate}\n" .
            "Status: {$row['status']}\n" .
            "Platform: " . ($row['platform'] ?? '-') . "\n" .
            "Notes: " . ($row['notes'] ?? '-');

        $mail->send();

        if ($reminderType === '1_day') {
            execute_query(
                "UPDATE follow_ups SET reminder_1_sent_at = NOW() WHERE id = ?",
                [(int)$row['id']]
            );
            echo "1-day reminder sent for follow-up #{$row['id']} to {$toEmail}\n";

        } elseif ($reminderType === 'same_day') {
            execute_query(
                "UPDATE follow_ups SET reminder_same_day_sent_at = NOW() WHERE id = ?",
                [(int)$row['id']]
            );
            echo "Same-day reminder sent for follow-up #{$row['id']} to {$toEmail}\n";

        } else {
            execute_query(
                "UPDATE follow_ups SET reminder_overdue_sent_at = NOW() WHERE id = ?",
                [(int)$row['id']]
            );
            echo "Daily overdue reminder sent for follow-up #{$row['id']} to {$toEmail}\n";
        }

        $sentCount++;

    } catch (Exception $e) {
        $errorCount++;
        echo "Failed for follow-up #{$row['id']}: {$mail->ErrorInfo}\n";
    }
}

echo "Done. Sent: {$sentCount}, Skipped: {$skipCount}, Errors: {$errorCount}\n";

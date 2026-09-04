<?php

require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$mailConfig = require __DIR__ . '/../includes/mail_config.php';


/*
|--------------------------------------------------------------------------
| FIND REMINDERS
|--------------------------------------------------------------------------
|
| Pending / Hold:
|   1. 24 hours before
|   2. 2 hours before
|
| Overdue:
|   Send once after follow-up time has passed
|
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
        f.reminder_2_sent_at,
        f.reminder_overdue_sent_at,

        CONCAT(c.first_name, ' ', c.last_name) AS client_name,
        co.company_name

    FROM follow_ups f

    INNER JOIN clients c
        ON c.id = f.client_id

    LEFT JOIN companies co
        ON co.id = c.company_id

    WHERE
        (
            f.status IN ('Pending', 'Hold')
            AND
            (
                (
                    f.followup_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
                    AND f.followup_date > DATE_ADD(NOW(), INTERVAL 19 HOUR)
                    AND f.reminder_1_sent_at IS NULL
                )

                OR

                (
                    f.followup_date <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
                    AND f.followup_date > DATE_ADD(NOW(), INTERVAL 1 HOUR)
                    AND f.reminder_2_sent_at IS NULL
                )

                OR

                (
                    f.followup_date <= NOW()
                    AND f.reminder_overdue_sent_at IS NULL
                )
            )
        )

        OR

        (
            f.status = 'Overdue'
            AND f.reminder_overdue_sent_at IS NULL
        )

    ORDER BY f.followup_date ASC
");


foreach ($followups as $row) {

    $now = time();
    $followupTime = strtotime($row['followup_date']);

    /*
    |--------------------------------------------------------------------------
    | Decide reminder type
    |--------------------------------------------------------------------------
    */

    $reminderType = null;

    if (
        in_array($row['status'], ['Pending', 'Hold'], true)
        && $followupTime > $now
        && $followupTime <= ($now + 24 * 60 * 60)
        && $followupTime > ($now + 19 * 60 * 60)
        && empty($row['reminder_1_sent_at'])
    ) {

        $reminderType = '1_day';

    } elseif (
        in_array($row['status'], ['Pending', 'Hold'], true)
        && $followupTime > $now
        && $followupTime <= ($now + 2 * 60 * 60)
        && $followupTime > ($now + 1 * 60 * 60)
        && empty($row['reminder_2_sent_at'])
    ) {

        $reminderType = '2_hours';

    } elseif (
        $followupTime <= $now
        && empty($row['reminder_overdue_sent_at'])
    ) {

        $reminderType = 'overdue';

    }


    if (!$reminderType) {
        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */

    $mail = new PHPMailer(true);

    try {

        /*
        | SMTP
        */

        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailConfig['port'];


        /*
        | Sender
        */

        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );


        /*
        | Receiver
        */

        $mail->addAddress($mailConfig['to_email']);
        $mail->addCC($mailConfig['cc_email']);


        /*
        | Data
        */

        $clientName = $row['client_name'] ?: 'Client';
        $companyName = $row['company_name'] ?: 'No Company';

        $followupDate = date(
            'd M Y h:i A',
            $followupTime
        );


        /*
        |--------------------------------------------------------------------------
        | Subject / Message
        |--------------------------------------------------------------------------
        */

        if ($reminderType === '1_day') {

            $subject = "CRM Follow-up Reminder - 1 Day - {$clientName}";
            $messageTitle = "Follow-up Reminder - 1 Day Before";
            $messageText = "This follow-up is scheduled for tomorrow.";

        } elseif ($reminderType === '2_hours') {

            $subject = "CRM Follow-up Reminder - 2 Hours - {$clientName}";
            $messageTitle = "Follow-up Reminder - 2 Hours Before";
            $messageText = "This follow-up is scheduled in approximately 2 hours.";

        } else {

            $subject = "CRM Follow-up OVERDUE - {$clientName}";
            $messageTitle = "Follow-up OVERDUE";
            $messageText = "This follow-up time has already passed.";

        }


        /*
        |--------------------------------------------------------------------------
        | HTML Email
        |--------------------------------------------------------------------------
        */

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;font-size:14px;line-height:1.6;'>

                <h2>" . htmlspecialchars($messageTitle) . "</h2>

                <p>" . htmlspecialchars($messageText) . "</p>

                <table cellpadding='6' cellspacing='0' border='0'>

                    <tr>
                        <td><strong>Client</strong></td>
                        <td>" . htmlspecialchars($clientName) . "</td>
                    </tr>

                    <tr>
                        <td><strong>Company</strong></td>
                        <td>" . htmlspecialchars($companyName) . "</td>
                    </tr>

                    <tr>
                        <td><strong>Follow-up Date & Time</strong></td>
                        <td>" . htmlspecialchars($followupDate) . "</td>
                    </tr>

                    <tr>
                        <td><strong>Status</strong></td>
                        <td>" . htmlspecialchars($row['status']) . "</td>
                    </tr>

                    <tr>
                        <td><strong>Platform</strong></td>
                        <td>" . htmlspecialchars($row['platform'] ?? '-') . "</td>
                    </tr>

                    <tr>
                        <td><strong>Notes</strong></td>
                        <td>" . nl2br(
                            htmlspecialchars($row['notes'] ?? '-')
                        ) . "</td>
                    </tr>

                </table>

                <p>
                    Please check the CRM for complete follow-up details.
                </p>

            </div>
        ";


        /*
        |--------------------------------------------------------------------------
        | Plain Text
        |--------------------------------------------------------------------------
        */

        $mail->AltBody =
            "{$messageTitle}\n\n" .
            "{$messageText}\n\n" .
            "Client: {$clientName}\n" .
            "Company: {$companyName}\n" .
            "Follow-up: {$followupDate}\n" .
            "Status: {$row['status']}\n" .
            "Platform: " . ($row['platform'] ?? '-') . "\n" .
            "Notes: " . ($row['notes'] ?? '-');


        /*
        |--------------------------------------------------------------------------
        | SEND
        |--------------------------------------------------------------------------
        */

        $mail->send();


        /*
        |--------------------------------------------------------------------------
        | MARK REMINDER AS SENT
        |--------------------------------------------------------------------------
        */

        if ($reminderType === '1_day') {

            execute_query(
                "UPDATE follow_ups
                 SET reminder_1_sent_at = NOW()
                 WHERE id = ?",
                [(int)$row['id']]
            );

            echo "1-day reminder sent for follow-up #{$row['id']}\n";


        } elseif ($reminderType === '2_hours') {

            execute_query(
                "UPDATE follow_ups
                 SET reminder_2_sent_at = NOW()
                 WHERE id = ?",
                [(int)$row['id']]
            );

            echo "2-hour reminder sent for follow-up #{$row['id']}\n";


        } elseif ($reminderType === 'overdue') {

            execute_query(
                "UPDATE follow_ups
                 SET reminder_overdue_sent_at = NOW()
                 WHERE id = ?",
                [(int)$row['id']]
            );

            echo "Overdue reminder sent for follow-up #{$row['id']}\n";
        }


    } catch (Exception $e) {

        echo "Failed for follow-up #{$row['id']}: ";
        echo $mail->ErrorInfo;
        echo "\n";
    }
}
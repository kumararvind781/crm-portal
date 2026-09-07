<?php

require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$mailConfig = require __DIR__ . '/../includes/mail_config.php';


/*
|--------------------------------------------------------------------------
| LOAD CRM SETTINGS
|--------------------------------------------------------------------------
*/

$settingsRows = fetch_all("
    SELECT
        setting_key,
        setting_value
    FROM crm_settings
    WHERE setting_key IN (
        'email_reminders',
        'reminder_1_day',
        'reminder_2_hours',
        'reminder_overdue',
        'reminder_to_email',
        'reminder_cc_email',
        'timezone'
    )
");

$settings = [];

foreach ($settingsRows as $setting) {
    $settings[$setting['setting_key']] =
        $setting['setting_value'];
}


/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/

$timezone =
    $settings['timezone']
    ?? 'Asia/Kolkata';

date_default_timezone_set($timezone);


/*
|--------------------------------------------------------------------------
| EMAIL SETTINGS FROM PORTAL
|--------------------------------------------------------------------------
*/

$emailReminders =
    $settings['email_reminders']
    ?? '1';

$reminder1Day =
    $settings['reminder_1_day']
    ?? '1';

$reminder2Hours =
    $settings['reminder_2_hours']
    ?? '1';

$reminderOverdue =
    $settings['reminder_overdue']
    ?? '1';


$toEmail =
    trim(
        $settings['reminder_to_email']
        ?? ''
    );

$ccEmail =
    trim(
        $settings['reminder_cc_email']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| CHECK EMAIL REMINDER MASTER SWITCH
|--------------------------------------------------------------------------
*/

if ($emailReminders !== '1') {

    echo "Email reminders are disabled.\n";

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE RECIPIENT
|--------------------------------------------------------------------------
*/

if (
    $toEmail === '' ||
    !filter_var(
        $toEmail,
        FILTER_VALIDATE_EMAIL
    )
) {

    echo "Invalid or missing To Email in CRM Settings.\n";

    exit;
}


/*
|--------------------------------------------------------------------------
| FIND REMINDERS
|--------------------------------------------------------------------------
|
| Pending / Hold:
|
|   1 Day Before
|   2 Hours Before
|
| Overdue:
|
|   Once after follow-up time has passed
|
| Completed:
|
|   No reminder
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

        CONCAT(
            c.first_name,
            ' ',
            c.last_name
        ) AS client_name,

        co.company_name

    FROM follow_ups f

    INNER JOIN clients c
        ON c.id = f.client_id

    LEFT JOIN companies co
        ON co.id = c.company_id

    WHERE

        /*
        |--------------------------------------------------------------------------
        | Pending / Hold
        |--------------------------------------------------------------------------
        */

        (
            f.status IN ('Pending', 'Hold')

            AND

            (

                /*
                | 1 DAY BEFORE
                */

                (
                    f.followup_date <= DATE_ADD(
                        NOW(),
                        INTERVAL 24 HOUR
                    )

                    AND

                    f.followup_date > DATE_ADD(
                        NOW(),
                        INTERVAL 19 HOUR
                    )

                    AND

                    f.reminder_1_sent_at IS NULL
                )

                OR

                /*
                | 2 HOURS BEFORE
                */

                (
                    f.followup_date <= DATE_ADD(
                        NOW(),
                        INTERVAL 2 HOUR
                    )

                    AND

                    f.followup_date > DATE_ADD(
                        NOW(),
                        INTERVAL 1 HOUR
                    )

                    AND

                    f.reminder_2_sent_at IS NULL
                )

                OR

                /*
                | OVERDUE
                */

                (
                    f.followup_date <= NOW()

                    AND

                    f.reminder_overdue_sent_at IS NULL
                )

            )
        )

        OR

        /*
        |--------------------------------------------------------------------------
        | Already marked Overdue
        |--------------------------------------------------------------------------
        */

        (
            f.status = 'Overdue'

            AND

            f.reminder_overdue_sent_at IS NULL
        )

    ORDER BY
        f.followup_date ASC
");


/*
|--------------------------------------------------------------------------
| PROCESS FOLLOW-UPS
|--------------------------------------------------------------------------
*/

foreach ($followups as $row) {


    $now = time();

    $followupTime =
        strtotime(
            $row['followup_date']
        );


    /*
    |--------------------------------------------------------------------------
    | Decide Reminder Type
    |--------------------------------------------------------------------------
    */

    $reminderType = null;


    /*
    |--------------------------------------------------------------------------
    | 1 DAY BEFORE
    |--------------------------------------------------------------------------
    */

    if (
        $reminder1Day === '1'

        &&

        in_array(
            $row['status'],
            ['Pending', 'Hold'],
            true
        )

        &&

        $followupTime > $now

        &&

        $followupTime <= (
            $now + 24 * 60 * 60
        )

        &&

        $followupTime > (
            $now + 19 * 60 * 60
        )

        &&

        empty(
            $row['reminder_1_sent_at']
        )
    ) {

        $reminderType = '1_day';
    }


    /*
    |--------------------------------------------------------------------------
    | 2 HOURS BEFORE
    |--------------------------------------------------------------------------
    */

    elseif (
        $reminder2Hours === '1'

        &&

        in_array(
            $row['status'],
            ['Pending', 'Hold'],
            true
        )

        &&

        $followupTime > $now

        &&

        $followupTime <= (
            $now + 2 * 60 * 60
        )

        &&

        $followupTime > (
            $now + 1 * 60 * 60
        )

        &&

        empty(
            $row['reminder_2_sent_at']
        )
    ) {

        $reminderType = '2_hours';
    }


    /*
    |--------------------------------------------------------------------------
    | OVERDUE
    |--------------------------------------------------------------------------
    */

    elseif (
        $reminderOverdue === '1'

        &&

        $followupTime <= $now

        &&

        empty(
            $row['reminder_overdue_sent_at']
        )
    ) {

        $reminderType = 'overdue';
    }


    /*
    |--------------------------------------------------------------------------
    | Nothing to Send
    |--------------------------------------------------------------------------
    */

    if (!$reminderType) {
        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    $mail = new PHPMailer(true);


    try {


        /*
        |--------------------------------------------------------------------------
        | SMTP
        |--------------------------------------------------------------------------
        */

        $mail->isSMTP();

        $mail->Host =
            $mailConfig['host'];

        $mail->SMTPAuth = true;

        $mail->Username =
            $mailConfig['username'];

        $mail->Password =
            $mailConfig['password'];

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            $mailConfig['port'];


        /*
        |--------------------------------------------------------------------------
        | SENDER
        |--------------------------------------------------------------------------
        */

        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );


        /*
        |--------------------------------------------------------------------------
        | TO / CC FROM CRM SETTINGS
        |--------------------------------------------------------------------------
        */

        $mail->addAddress(
            $toEmail
        );


        if (
            $ccEmail !== '' &&
            filter_var(
                $ccEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $mail->addCC(
                $ccEmail
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $clientName =
            $row['client_name']
            ?: 'Client';

        $companyName =
            $row['company_name']
            ?: 'No Company';


        $followupDate =
            date(
                'd M Y h:i A',
                $followupTime
            );


        /*
        |--------------------------------------------------------------------------
        | SUBJECT / MESSAGE
        |--------------------------------------------------------------------------
        */

        if ($reminderType === '1_day') {

            $subject =
                "CRM Follow-up Reminder - 1 Day - {$clientName}";

            $messageTitle =
                "Follow-up Reminder - 1 Day Before";

            $messageText =
                "This follow-up is scheduled for tomorrow.";

        }


        elseif ($reminderType === '2_hours') {

            $subject =
                "CRM Follow-up Reminder - 2 Hours - {$clientName}";

            $messageTitle =
                "Follow-up Reminder - 2 Hours Before";

            $messageText =
                "This follow-up is scheduled in approximately 2 hours.";

        }


        else {

            $subject =
                "CRM Follow-up OVERDUE - {$clientName}";

            $messageTitle =
                "Follow-up OVERDUE";

            $messageText =
                "This follow-up time has already passed.";

        }


        /*
        |--------------------------------------------------------------------------
        | HTML EMAIL
        |--------------------------------------------------------------------------
        */

        $mail->isHTML(true);

        $mail->Subject =
            $subject;


        $mail->Body = "

            <div style='
                font-family:Arial,sans-serif;
                font-size:14px;
                line-height:1.6;
            '>

                <h2>
                    " .
                    htmlspecialchars(
                        $messageTitle
                    )
                    .
                "
                </h2>


                <p>
                    " .
                    htmlspecialchars(
                        $messageText
                    )
                    .
                "
                </p>


                <table
                    cellpadding='6'
                    cellspacing='0'
                    border='0'
                >


                    <tr>

                        <td>
                            <strong>Client</strong>
                        </td>

                        <td>
                            " .
                            htmlspecialchars(
                                $clientName
                            )
                            .
                        "
                        </td>

                    </tr>


                    <tr>

                        <td>
                            <strong>Company</strong>
                        </td>

                        <td>
                            " .
                            htmlspecialchars(
                                $companyName
                            )
                            .
                        "
                        </td>

                    </tr>


                    <tr>

                        <td>
                            <strong>
                                Follow-up Date & Time
                            </strong>
                        </td>

                        <td>
                            " .
                            htmlspecialchars(
                                $followupDate
                            )
                            .
                        "
                        </td>

                    </tr>


                    <tr>

                        <td>
                            <strong>Status</strong>
                        </td>

                        <td>
                            " .
                            htmlspecialchars(
                                $row['status']
                            )
                            .
                        "
                        </td>

                    </tr>


                    <tr>

                        <td>
                            <strong>Platform</strong>
                        </td>

                        <td>
                            " .
                            htmlspecialchars(
                                $row['platform'] ?? '-'
                            )
                            .
                        "
                        </td>

                    </tr>


                    <tr>

                        <td>
                            <strong>Notes</strong>
                        </td>

                        <td>
                            " .
                            nl2br(
                                htmlspecialchars(
                                    $row['notes'] ?? '-'
                                )
                            )
                            .
                        "
                        </td>

                    </tr>


                </table>


                <p>
                    Please check the CRM for complete
                    follow-up details.
                </p>


            </div>
        ";


        /*
        |--------------------------------------------------------------------------
        | PLAIN TEXT
        |--------------------------------------------------------------------------
        */

        $mail->AltBody =
            "{$messageTitle}\n\n" .
            "{$messageText}\n\n" .
            "Client: {$clientName}\n" .
            "Company: {$companyName}\n" .
            "Follow-up: {$followupDate}\n" .
            "Status: {$row['status']}\n" .
            "Platform: " .
            ($row['platform'] ?? '-') .
            "\n" .
            "Notes: " .
            ($row['notes'] ?? '-');


        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL
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
                [
                    (int)$row['id']
                ]
            );


            echo
                "1-day reminder sent for follow-up #" .
                $row['id'] .
                " to {$toEmail}\n";
        }


        elseif ($reminderType === '2_hours') {

            execute_query(
                "UPDATE follow_ups
                 SET reminder_2_sent_at = NOW()
                 WHERE id = ?",
                [
                    (int)$row['id']
                ]
            );


            echo
                "2-hour reminder sent for follow-up #" .
                $row['id'] .
                " to {$toEmail}\n";
        }


        elseif ($reminderType === 'overdue') {

            execute_query(
                "UPDATE follow_ups
                 SET reminder_overdue_sent_at = NOW()
                 WHERE id = ?",
                [
                    (int)$row['id']
                ]
            );


            echo
                "Overdue reminder sent for follow-up #" .
                $row['id'] .
                " to {$toEmail}\n";
        }


    }


    catch (Exception $e) {

        echo
            "Failed for follow-up #" .
            $row['id'] .
            ": " .
            $mail->ErrorInfo .
            "\n";
    }

}
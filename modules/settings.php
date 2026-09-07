<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Settings';
$pageDescription = 'CRM and follow-up reminder settings.';

function setting_value(string $key, string $default = ''): string
{
    $row = fetch_one(
        "SELECT setting_value FROM crm_settings WHERE setting_key = ? LIMIT 1",
        [$key]
    );

    return $row ? (string)$row['setting_value'] : $default;
}

function save_setting(string $key, string $value): void
{
    execute_query(
        "INSERT INTO crm_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $timezone            = trim($_POST['timezone'] ?? 'Asia/Kolkata');
        $defaultClientStatus = trim($_POST['default_client_status'] ?? 'Active');

        $emailReminders       = isset($_POST['email_reminders']) ? '1' : '0';
        $reminder1Day         = isset($_POST['reminder_1_day']) ? '1' : '0';
        $reminderSameDay      = isset($_POST['reminder_same_day']) ? '1' : '0';
        $reminderOverdue      = isset($_POST['reminder_overdue']) ? '1' : '0';

        $fromEmail = trim($_POST['reminder_from_email'] ?? '');
        $fromName  = trim($_POST['reminder_from_name'] ?? '');
        $toEmail   = trim($_POST['reminder_to_email'] ?? '');
        $ccEmail   = trim($_POST['reminder_cc_email'] ?? '');

        $sendTime = trim($_POST['reminder_send_time'] ?? '09:00');

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $sendTime)) {
            $sendTime = '09:00';
        }

        if ($timezone === '') {
            $timezone = 'Asia/Kolkata';
        }

        if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid From Email address.');
        }

        if ($toEmail !== '' && !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid To Email address.');
        }

        if ($ccEmail !== '' && !filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid CC Email address.');
        }

        save_setting('timezone', $timezone);
        save_setting('default_client_status', $defaultClientStatus);

        save_setting('email_reminders', $emailReminders);
        save_setting('reminder_1_day', $reminder1Day);
        save_setting('reminder_same_day', $reminderSameDay);
        save_setting('reminder_overdue', $reminderOverdue);

        save_setting('reminder_from_email', $fromEmail);
        save_setting('reminder_from_name', $fromName);
        save_setting('reminder_to_email', $toEmail);
        save_setting('reminder_cc_email', $ccEmail);
        save_setting('reminder_send_time', $sendTime);

        $message = 'Settings saved successfully.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$timezone            = setting_value('timezone', 'Asia/Kolkata');
$defaultClientStatus = setting_value('default_client_status', 'Active');

$emailReminders  = setting_value('email_reminders', '1') === '1';
$reminder1Day    = setting_value('reminder_1_day', '1') === '1';
$reminderSameDay = setting_value('reminder_same_day', '1') === '1';
$reminderOverdue = setting_value('reminder_overdue', '1') === '1';

$fromEmail = setting_value('reminder_from_email', 'arvindunire@gmail.com');
$fromName  = setting_value('reminder_from_name', 'CRM Follow-up Reminder');
$toEmail   = setting_value('reminder_to_email', 'support@unire.co.in');
$ccEmail   = setting_value('reminder_cc_email', 'arvind.sharma@unire.co.in');

$sendTime = setting_value('reminder_send_time', '09:00');

if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $sendTime)) {
    $sendTime = '09:00';
}

[$cronHour, $cronMinute] = array_map('intval', explode(':', $sendTime));

$cronSchedule = sprintf('%d %d * * *', $cronMinute, $cronHour);

$cronCommand = $cronSchedule .
    ' /usr/bin/php /home/vinaykalra/public_html/cron/followup_reminder.php' .
    ' >> /home/vinaykalra/public_html/cron/followup_reminder.log 2>&1';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<section class="panel">

    <div class="panel-header">
        <div>
            <h3>Settings</h3>
            <p class="muted">Manage CRM and follow-up email reminders.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= esc($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= esc($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <div class="settings-grid">

            <div class="settings-card">
                <h4>General Settings</h4>

                <div class="form-group">
                    <label>Timezone</label>
                    <select name="timezone" class="form-select">
                        <option value="Asia/Kolkata" <?= $timezone === 'Asia/Kolkata' ? 'selected' : '' ?>>
                            Asia/Kolkata (India)
                        </option>
                        <option value="UTC" <?= $timezone === 'UTC' ? 'selected' : '' ?>>
                            UTC
                        </option>
                        <option value="Asia/Dubai" <?= $timezone === 'Asia/Dubai' ? 'selected' : '' ?>>
                            Asia/Dubai
                        </option>
                        <option value="Asia/Singapore" <?= $timezone === 'Asia/Singapore' ? 'selected' : '' ?>>
                            Asia/Singapore
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Default Client Status</label>
                    <select name="default_client_status" class="form-select">
                        <option value="Active" <?= $defaultClientStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $defaultClientStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="Qualified" <?= $defaultClientStatus === 'Qualified' ? 'selected' : '' ?>>Qualified</option>
                    </select>
                </div>
            </div>

            <div class="settings-card">
                <h4>Email Reminders</h4>

                <label class="setting-row">
                    <span>
                        <strong>Enable Email Reminders</strong>
                        <small>Master switch for all follow-up reminders.</small>
                    </span>
                    <input type="checkbox" name="email_reminders" value="1"
                        <?= $emailReminders ? 'checked' : '' ?>>
                </label>

                <label class="setting-row">
                    <span>
                        <strong>1 Day Before</strong>
                        <small>Send reminder one day before the follow-up.</small>
                    </span>
                    <input type="checkbox" name="reminder_1_day" value="1"
                        <?= $reminder1Day ? 'checked' : '' ?>>
                </label>

                <label class="setting-row">
                    <span>
                        <strong>Same Day</strong>
                        <small>Send reminder on the follow-up date.</small>
                    </span>
                    <input type="checkbox" name="reminder_same_day" value="1"
                        <?= $reminderSameDay ? 'checked' : '' ?>>
                </label>

                <label class="setting-row">
                    <span>
                        <strong>Daily Overdue</strong>
                        <small>Send one email every day until completed.</small>
                    </span>
                    <input type="checkbox" name="reminder_overdue" value="1"
                        <?= $reminderOverdue ? 'checked' : '' ?>>
                </label>

                <div class="note-box">
                    Reminders apply to <strong>Pending, Hold and Overdue</strong>.
                    Completed follow-ups are not reminded.
                </div>
            </div>

            <div class="settings-card">
                <h4>Reminder Email</h4>

                <div class="form-group">
                    <label>From Email</label>
                    <input type="email"
                           name="reminder_from_email"
                           class="form-control"
                           value="<?= esc($fromEmail) ?>">
                    <small class="form-help">
                        Keep this the same as the Gmail account in mail_config.php.
                    </small>
                </div>

                <div class="form-group">
                    <label>From Name</label>
                    <input type="text"
                           name="reminder_from_name"
                           class="form-control"
                           value="<?= esc($fromName) ?>">
                </div>

                <div class="form-group">
                    <label>To Email</label>
                    <input type="email"
                           name="reminder_to_email"
                           class="form-control"
                           value="<?= esc($toEmail) ?>">
                </div>

                <div class="form-group">
                    <label>CC Email</label>
                    <input type="email"
                           name="reminder_cc_email"
                           class="form-control"
                           value="<?= esc($ccEmail) ?>">
                </div>
            </div>

            <div class="settings-card">
                <h4>Daily Cron</h4>

                <div class="form-group">
                    <label>Daily Reminder Send Time</label>
                    <input type="time"
                           name="reminder_send_time"
                           class="form-control"
                           value="<?= esc($sendTime) ?>">
                </div>

                <div class="form-group">
                    <label>Generated Cron Schedule</label>
                    <input type="text"
                           class="form-control"
                           value="<?= esc($cronSchedule) ?>"
                           readonly>
                </div>

                <div class="form-group">
                    <label>GoDaddy / cPanel Cron Command</label>
                    <textarea id="cronCommand"
                              class="form-control"
                              rows="3"
                              readonly><?= esc($cronCommand) ?></textarea>
                </div>

                <button type="button"
                        class="btn btn-secondary"
                        onclick="copyCronCommand()">
                    Copy Cron Command
                </button>

                <div class="note-box">
                    Cron only needs to run <strong>once per day</strong>.
                    The CRM will check tomorrow, today and overdue follow-ups
                    in that single run.
                </div>
            </div>

        </div>

        <div class="settings-actions">
            <button type="submit" class="btn btn-primary">
                Save Settings
            </button>
        </div>

    </form>

</section>

</main>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.settings-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    background: #fff;
}

.settings-card h4 {
    margin: 0 0 18px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
}

.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    opacity: .7;
}

.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.setting-row:last-of-type {
    border-bottom: 0;
}

.setting-row strong {
    display: block;
}

.setting-row small {
    display: block;
    margin-top: 3px;
    opacity: .65;
}

.setting-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
}

.note-box {
    margin-top: 15px;
    padding: 11px 13px;
    border-radius: 7px;
    background: #f6f7f9;
    font-size: 13px;
    line-height: 1.5;
}

.settings-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 900px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function copyCronCommand() {
    const field = document.getElementById('cronCommand');

    navigator.clipboard.writeText(field.value).then(function () {
        alert('Cron command copied.');
    }).catch(function () {
        field.select();
        document.execCommand('copy');
        alert('Cron command copied.');
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Reports';
$pageDescription = 'CRM activity, client engagement and follow-up performance.';

/*
|--------------------------------------------------------------------------
| DATE RANGE
|--------------------------------------------------------------------------
*/

$last30Days = date('Y-m-d H:i:s', strtotime('-30 days'));
$todayStart = date('Y-m-d 00:00:00');
$todayEnd   = date('Y-m-d 23:59:59');

$next7Days = date('Y-m-d H:i:s', strtotime('+7 days'));


/*
|--------------------------------------------------------------------------
| TOP SUMMARY
|--------------------------------------------------------------------------
*/

/* Unique clients contacted in last 30 days */
$clientsContacted = fetch_one("
    SELECT COUNT(DISTINCT contact_id) AS total
    FROM communications
    WHERE communication_date >= ?
", [$last30Days]);

$clientsContacted = (int)($clientsContacted['total'] ?? 0);


/* Total communications */
$totalCommunications30 = fetch_one("
    SELECT COUNT(*) AS total
    FROM communications
    WHERE communication_date >= ?
", [$last30Days]);

$totalCommunications30 = (int)($totalCommunications30['total'] ?? 0);


/* Pending follow-ups */
$pendingFollowups = fetch_one("
    SELECT COUNT(*) AS total
    FROM follow_ups
    WHERE status = 'Pending'
", []);

$pendingFollowups = (int)($pendingFollowups['total'] ?? 0);


/* Overdue follow-ups */
$overdueFollowups = fetch_one("
    SELECT COUNT(*) AS total
    FROM follow_ups
    WHERE
        followup_date < NOW()
        AND status NOT IN ('Completed')
", []);

$overdueFollowups = (int)($overdueFollowups['total'] ?? 0);


/* Follow-ups today */
$todayFollowups = fetch_one("
    SELECT COUNT(*) AS total
    FROM follow_ups
    WHERE
        followup_date BETWEEN ? AND ?
        AND status NOT IN ('Completed')
", [$todayStart, $todayEnd]);

$todayFollowups = (int)($todayFollowups['total'] ?? 0);


/* Next 7 days */
$next7Followups = fetch_one("
    SELECT COUNT(*) AS total
    FROM follow_ups
    WHERE
        followup_date > NOW()
        AND followup_date <= ?
        AND status NOT IN ('Completed')
", [$next7Days]);

$next7Followups = (int)($next7Followups['total'] ?? 0);


/* New clients last 30 days */
$newClients30 = fetch_one("
    SELECT COUNT(*) AS total
    FROM clients
    WHERE created_at >= ?
", [$last30Days]);

$newClients30 = (int)($newClients30['total'] ?? 0);


/* Meetings last 30 days */
$meetings30 = fetch_one("
    SELECT COUNT(*) AS total
    FROM meetings
    WHERE meeting_date >= ?
", [$last30Days]);

$meetings30 = (int)($meetings30['total'] ?? 0);


/*
|--------------------------------------------------------------------------
| COMMUNICATION BY TYPE
|--------------------------------------------------------------------------
*/

$communicationTypes = fetch_all("
    SELECT
        communication_type,
        COUNT(*) AS total
    FROM communications
    WHERE communication_date >= ?
    GROUP BY communication_type
    ORDER BY total DESC
", [$last30Days]);


/*
|--------------------------------------------------------------------------
| FOLLOW-UP STATUS
|--------------------------------------------------------------------------
*/

$followupStatus = fetch_all("
    SELECT
        status,
        COUNT(*) AS total
    FROM follow_ups
    GROUP BY status
    ORDER BY total DESC
");


/*
|--------------------------------------------------------------------------
| TOP CLIENTS CONTACTED
|--------------------------------------------------------------------------
*/

$topClients = fetch_all("
    SELECT
        c.id,
        CONCAT(
            COALESCE(c.first_name, ''),
            ' ',
            COALESCE(c.last_name, '')
        ) AS client_name,

        co.company_name,

        COUNT(cm.id) AS communication_count

    FROM communications cm

    INNER JOIN clients c
        ON c.id = cm.contact_id

    LEFT JOIN companies co
        ON co.id = c.company_id

    WHERE cm.communication_date >= ?

    GROUP BY
        c.id,
        c.first_name,
        c.last_name,
        co.company_name

    ORDER BY communication_count DESC

    LIMIT 10
", [$last30Days]);


/*
|--------------------------------------------------------------------------
| CLIENTS WITH NO FOLLOW-UP
|--------------------------------------------------------------------------
*/

$clientsWithoutFollowup = fetch_all("
    SELECT
        c.id,

        CONCAT(
            COALESCE(c.first_name, ''),
            ' ',
            COALESCE(c.last_name, '')
        ) AS client_name,

        co.company_name,

        c.email_id,
        c.phone_number

    FROM clients c

    LEFT JOIN companies co
        ON co.id = c.company_id

    LEFT JOIN follow_ups f
        ON f.client_id = c.id

    WHERE f.id IS NULL

    ORDER BY c.id DESC

    LIMIT 20
");


/*
|--------------------------------------------------------------------------
| UPCOMING FOLLOW-UPS
|--------------------------------------------------------------------------
*/

$upcomingFollowups = fetch_all("
    SELECT
        f.id,
        f.client_id,
        f.followup_date,
        f.status,
        f.platform,
        f.notes,

        CONCAT(
            COALESCE(c.first_name, ''),
            ' ',
            COALESCE(c.last_name, '')
        ) AS client_name,

        co.company_name,

        u.name AS assigned_name

    FROM follow_ups f

    INNER JOIN clients c
        ON c.id = f.client_id

    LEFT JOIN companies co
        ON co.id = c.company_id

    LEFT JOIN users u
        ON u.id = f.assigned_to

    WHERE
        f.followup_date >= NOW()
        AND f.status NOT IN ('Completed')

    ORDER BY f.followup_date ASC

    LIMIT 15
");


/*
|--------------------------------------------------------------------------
| RECENT COMMUNICATIONS
|--------------------------------------------------------------------------
*/

$recentCommunications = fetch_all("
    SELECT
        cm.id,
        cm.communication_date,
        cm.communication_type,
        cm.subject,
        cm.communication,

        CONCAT(
            COALESCE(c.first_name, ''),
            ' ',
            COALESCE(c.last_name, '')
        ) AS client_name,

        co.company_name,

        u.name AS created_name

    FROM communications cm

    INNER JOIN clients c
        ON c.id = cm.contact_id

    LEFT JOIN companies co
        ON co.id = cm.company_id

    LEFT JOIN users u
        ON u.id = cm.created_by

    ORDER BY cm.communication_date DESC

    LIMIT 15
");


/*
|--------------------------------------------------------------------------
| USER ACTIVITY
|--------------------------------------------------------------------------
*/

$userActivity = fetch_all("
    SELECT
        u.id,
        u.name,
        COUNT(cm.id) AS communication_count

    FROM users u

    LEFT JOIN communications cm
        ON cm.created_by = u.id
        AND cm.communication_date >= ?

    GROUP BY u.id, u.name

    ORDER BY communication_count DESC
", [$last30Days]);


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

?>

<main class="main-content">

<?php include __DIR__ . '/../includes/topbar.php'; ?>


<style>

.report-page {
    padding: 24px;
}

.report-header {
    margin-bottom: 24px;
}

.report-header h2 {
    margin-bottom: 5px;
}

.report-header p {
    color: #64748b;
    margin: 0;
}

.report-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.report-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 6px 20px rgba(15,23,42,.05);
}

.report-card .label {
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
}

.report-card .number {
    font-size: 30px;
    font-weight: 800;
    color: #0f172a;
}

.report-card .small-text {
    margin-top: 5px;
    font-size: 12px;
    color: #94a3b8;
}

.report-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.report-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.report-panel {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 6px 20px rgba(15,23,42,.05);
    overflow: hidden;
}

.report-panel-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-panel-header h3 {
    margin: 0;
    font-size: 17px;
}

.report-panel-body {
    padding: 20px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th,
.report-table td {
    padding: 11px 10px;
    border-bottom: 1px solid #eef2f7;
    text-align: left;
    font-size: 13px;
}

.report-table th {
    color: #64748b;
    font-weight: 700;
}

.report-table tr:last-child td {
    border-bottom: 0;
}

.report-number {
    font-weight: 800;
    color: #0f172a;
}

.report-bar {
    width: 100%;
    height: 8px;
    background: #eef2f7;
    border-radius: 10px;
    overflow: hidden;
}

.report-bar span {
    display: block;
    height: 100%;
    background: #2563eb;
    border-radius: 10px;
}

.empty-report {
    text-align: center;
    padding: 25px;
    color: #94a3b8;
}

@media(max-width:1100px) {

    .report-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .report-grid,
    .report-grid-3 {
        grid-template-columns: 1fr;
    }

}

@media(max-width:600px) {

    .report-page {
        padding: 15px;
    }

    .report-cards {
        grid-template-columns: 1fr;
    }

}

</style>


<div class="report-page">


    <!-- HEADER -->

    <div class="report-header">

        <h2>CRM Reports</h2>

        <p>
            Client engagement, communication and follow-up performance
            for the last 30 days.
        </p>

    </div>


    <!-- TOP CARDS -->

    <section class="report-cards">


        <article class="report-card">

            <div class="label">
                CLIENTS CONTACTED
            </div>

            <div class="number">
                <?= number_format($clientsContacted) ?>
            </div>

            <div class="small-text">
                Unique clients in last 30 days
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                COMMUNICATIONS
            </div>

            <div class="number">
                <?= number_format($totalCommunications30) ?>
            </div>

            <div class="small-text">
                Last 30 days
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                PENDING FOLLOW-UPS
            </div>

            <div class="number">
                <?= number_format($pendingFollowups) ?>
            </div>

            <div class="small-text">
                Follow-ups waiting for action
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                OVERDUE
            </div>

            <div class="number">
                <?= number_format($overdueFollowups) ?>
            </div>

            <div class="small-text">
                Follow-ups past their date
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                DUE TODAY
            </div>

            <div class="number">
                <?= number_format($todayFollowups) ?>
            </div>

            <div class="small-text">
                Follow-ups scheduled today
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                NEXT 7 DAYS
            </div>

            <div class="number">
                <?= number_format($next7Followups) ?>
            </div>

            <div class="small-text">
                Upcoming follow-ups
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                NEW CLIENTS
            </div>

            <div class="number">
                <?= number_format($newClients30) ?>
            </div>

            <div class="small-text">
                Added in last 30 days
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                MEETINGS
            </div>

            <div class="number">
                <?= number_format($meetings30) ?>
            </div>

            <div class="small-text">
                Last 30 days
            </div>

        </article>


    </section>



    <!-- COMMUNICATION + FOLLOWUP -->

    <section class="report-grid">


        <!-- COMMUNICATION TYPES -->

        <article class="report-panel">

            <div class="report-panel-header">

                <h3>
                    Communication Activity
                </h3>

                <span class="small-text">
                    Last 30 Days
                </span>

            </div>

            <div class="report-panel-body">

                <?php if (empty($communicationTypes)): ?>

                    <div class="empty-report">
                        No communication activity.
                    </div>

                <?php else: ?>

                    <?php

                    $maxCommunication =
                        max(array_column($communicationTypes, 'total'));

                    ?>

                    <table class="report-table">

                        <thead>

                            <tr>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Activity</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($communicationTypes as $row): ?>

                            <?php

                            $percentage = $maxCommunication > 0
                                ? ($row['total'] / $maxCommunication) * 100
                                : 0;

                            ?>

                            <tr>

                                <td>
                                    <?= esc($row['communication_type'] ?? '-') ?>
                                </td>

                                <td class="report-number">
                                    <?= number_format($row['total']) ?>
                                </td>

                                <td style="width:45%;">

                                    <div class="report-bar">

                                        <span
                                            style="width:<?= $percentage ?>%;"
                                        ></span>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </article>



        <!-- FOLLOWUP STATUS -->

        <article class="report-panel">

            <div class="report-panel-header">

                <h3>
                    Follow-up Status
                </h3>

                <span class="small-text">
                    All Follow-ups
                </span>

            </div>

            <div class="report-panel-body">

                <?php if (empty($followupStatus)): ?>

                    <div class="empty-report">
                        No follow-ups found.
                    </div>

                <?php else: ?>

                    <table class="report-table">

                        <thead>

                            <tr>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($followupStatus as $row): ?>

                            <tr>

                                <td>
                                    <?= esc($row['status']) ?>
                                </td>

                                <td class="report-number">
                                    <?= number_format($row['total']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </article>


    </section>



    <!-- TOP CLIENTS + TEAM -->

    <section class="report-grid">


        <!-- TOP CLIENTS -->

        <article class="report-panel">

            <div class="report-panel-header">

                <h3>
                    Most Contacted Clients
                </h3>

                <span class="small-text">
                    Last 30 Days
                </span>

            </div>

            <div class="report-panel-body">

                <?php if (empty($topClients)): ?>

                    <div class="empty-report">
                        No communication found.
                    </div>

                <?php else: ?>

                    <table class="report-table">

                        <thead>

                            <tr>
                                <th>Client</th>
                                <th>Company</th>
                                <th>Contacts</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($topClients as $row): ?>

                            <tr>

                                <td>

                                    <a
                                        href="<?= BASE_URL ?>modules/client_view.php?id=<?= (int)$row['id'] ?>"
                                    >
                                        <strong>
                                            <?= esc(trim($row['client_name'])) ?>
                                        </strong>
                                    </a>

                                </td>

                                <td>
                                    <?= esc($row['company_name'] ?? '-') ?>
                                </td>

                                <td class="report-number">
                                    <?= number_format($row['communication_count']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </article>



        <!-- TEAM ACTIVITY -->

        <article class="report-panel">

            <div class="report-panel-header">

                <h3>
                    Team Communication Activity
                </h3>

                <span class="small-text">
                    Last 30 Days
                </span>

            </div>

            <div class="report-panel-body">

                <?php if (empty($userActivity)): ?>

                    <div class="empty-report">
                        No activity found.
                    </div>

                <?php else: ?>

                    <table class="report-table">

                        <thead>

                            <tr>
                                <th>User</th>
                                <th>Communications</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($userActivity as $row): ?>

                            <tr>

                                <td>
                                    <?= esc($row['name']) ?>
                                </td>

                                <td class="report-number">
                                    <?= number_format($row['communication_count']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </article>


    </section>



    <!-- UPCOMING FOLLOWUPS -->

    <section class="report-panel" style="margin-bottom:20px;">

        <div class="report-panel-header">

            <h3>
                Upcoming Follow-ups
            </h3>

            <span class="small-text">
                Next 7 Days
            </span>

        </div>

        <div class="report-panel-body">

            <?php if (empty($upcomingFollowups)): ?>

                <div class="empty-report">
                    No upcoming follow-ups.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table class="report-table">

                        <thead>

                            <tr>

                                <th>Client</th>
                                <th>Company</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Platform</th>
                                <th>Assigned</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($upcomingFollowups as $row): ?>

                            <tr>

                                <td>

                                    <a
                                        href="<?= BASE_URL ?>modules/client_view.php?id=<?= (int)$row['client_id'] ?>"
                                    >
                                        <strong>
                                            <?= esc(trim($row['client_name'])) ?>
                                        </strong>
                                    </a>

                                </td>

                                <td>
                                    <?= esc($row['company_name'] ?? '-') ?>
                                </td>

                                <td>

                                    <?= !empty($row['followup_date'])
                                        ? date(
                                            'd M Y h:i A',
                                            strtotime($row['followup_date'])
                                        )
                                        : '-'
                                    ?>

                                </td>

                                <td>

                                    <span class="badge <?= status_badge_class($row['status']) ?>">
                                        <?= esc($row['status']) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= esc($row['platform'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row['assigned_name'] ?? '-') ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </section>



    <!-- RECENT COMMUNICATIONS -->

    <section class="report-panel" style="margin-bottom:20px;">

        <div class="report-panel-header">

            <h3>
                Recent Communications
            </h3>

            <span class="small-text">
                Latest 15
            </span>

        </div>

        <div class="report-panel-body">

            <?php if (empty($recentCommunications)): ?>

                <div class="empty-report">
                    No communications found.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table class="report-table">

                        <thead>

                            <tr>

                                <th>Date</th>
                                <th>Client</th>
                                <th>Company</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Created By</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($recentCommunications as $row): ?>

                            <tr>

                                <td>

                                    <?= !empty($row['communication_date'])
                                        ? date(
                                            'd M Y h:i A',
                                            strtotime($row['communication_date'])
                                        )
                                        : '-'
                                    ?>

                                </td>

                                <td>

                                    <a
                                        href="<?= BASE_URL ?>modules/client_view.php?id=<?= (int)$row['contact_id'] ?>"
                                    >
                                        <?= esc(trim($row['client_name'])) ?>
                                    </a>

                                </td>

                                <td>
                                    <?= esc($row['company_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row['communication_type'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row['subject'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row['created_name'] ?? '-') ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </section>



    <!-- CLIENTS WITHOUT FOLLOWUP -->

    <section class="report-panel">

        <div class="report-panel-header">

            <h3>
                Clients Without Follow-up
            </h3>

            <span class="small-text">
                Needs Attention
            </span>

        </div>

        <div class="report-panel-body">

            <?php if (empty($clientsWithoutFollowup)): ?>

                <div class="empty-report">
                    Great! All clients have at least one follow-up.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table class="report-table">

                        <thead>

                            <tr>

                                <th>Client</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($clientsWithoutFollowup as $row): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= esc(trim($row['client_name'])) ?>
                                    </strong>

                                </td>

                                <td>
                                    <?= esc($row['company_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row['email_id'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row['phone_number'] ?? '-') ?>
                                </td>

                                <td>

                                    <a
                                        class="table-action"
                                        href="<?= BASE_URL ?>modules/client_view.php?id=<?= (int)$row['id'] ?>"
                                    >
                                        View Client
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </section>


</div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
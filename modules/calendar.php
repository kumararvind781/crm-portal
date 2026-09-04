<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Calendar';
$pageDescription = 'Review follow-up activity in date order.';
$followups = fetch_all("
    SELECT
        f.followup_date,
        f.status,
        f.notes,
        c.name AS client_name,
        c.company,

        CASE
            WHEN f.status = 'Pending' AND f.followup_date < NOW()
                THEN 'Overdue'
            WHEN f.status IS NULL OR TRIM(f.status) = ''
                THEN 'Pending'
            ELSE f.status
        END AS display_status

    FROM follow_ups f

    INNER JOIN clients c
        ON c.id = f.client_id

    ORDER BY
        CASE
            WHEN f.status = 'Overdue'
                OR (f.status = 'Pending' AND f.followup_date < NOW())
                THEN 1
            WHEN f.status = 'Pending'
                THEN 2
            WHEN f.status = 'Hold'
                THEN 3
            WHEN f.status = 'Completed'
                THEN 4
            ELSE 5
        END ASC,

        f.followup_date DESC
");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
    <section class="panel">
        <div class="panel-header">
            <h3>Upcoming Schedule</h3>
        </div>
        <div class="timeline"><?php foreach ($followups as $row): ?>
                <div class="timeline-item">
                    <div class="timeline-date"><?= date('d M Y', strtotime($row['followup_date'])) ?></div>
                    <div class="timeline-body"><strong><?= esc($row['client_name']) ?></strong>
                        <p><?= esc($row['company']) ?> · <?= date('h:i A', strtotime($row['followup_date'])) ?></p><span
                            class="badge <?= status_badge_class($row['display_status']) ?>">
                            <?= esc($row['display_status']) ?>
                        </span><small><?= esc($row['notes']) ?></small>
                    </div>
                </div><?php endforeach; ?>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
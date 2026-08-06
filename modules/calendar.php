<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Calendar';
$pageDescription = 'Review follow-up activity in date order.';
$followups = fetch_all('SELECT f.followup_date, f.status, f.notes, c.name AS client_name, c.company FROM follow_ups f INNER JOIN clients c ON c.id = f.client_id ORDER BY f.followup_date ASC');
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<section class="panel"><div class="panel-header"><h3>Upcoming Schedule</h3></div><div class="timeline"><?php foreach($followups as $row): ?><div class="timeline-item"><div class="timeline-date"><?= date('d M Y', strtotime($row['followup_date'])) ?></div><div class="timeline-body"><strong><?= esc($row['client_name']) ?></strong><p><?= esc($row['company']) ?> · <?= date('h:i A', strtotime($row['followup_date'])) ?></p><span class="badge <?= status_badge_class($row['status']) ?>"><?= esc($row['status']) ?></span><small><?= esc($row['notes']) ?></small></div></div><?php endforeach; ?></div></section></main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

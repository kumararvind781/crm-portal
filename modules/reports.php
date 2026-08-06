<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Reports';
$pageDescription = 'View CRM totals and operational status metrics.';
$stats = get_dashboard_stats();
$clientStatus = fetch_all("SELECT status, COUNT(*) total FROM clients GROUP BY status ORDER BY total DESC");
$userRole = fetch_all("SELECT role, COUNT(*) total FROM users GROUP BY role ORDER BY total DESC");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<section class="stats-grid"><article class="stat-card"><div><span>Total Clients</span><h3><?= $stats['clients'] ?></h3></div><div class="stat-icon"><i class="fa-solid fa-users"></i></div></article><article class="stat-card"><div><span>Total Cards</span><h3><?= $stats['cards'] ?></h3></div><div class="stat-icon"><i class="fa-regular fa-image"></i></div></article><article class="stat-card"><div><span>Total Follow-ups</span><h3><?= $stats['pending'] + $stats['completed'] + $stats['overdue'] ?></h3></div><div class="stat-icon"><i class="fa-regular fa-bell"></i></div></article><article class="stat-card"><div><span>Active Users</span><h3><?= $stats['users'] ?></h3></div><div class="stat-icon"><i class="fa-regular fa-user"></i></div></article></section>
<section class="panel-grid two-col"><article class="panel"><div class="panel-header"><h3>Client Status Report</h3></div><div class="table-wrap"><table><thead><tr><th>Status</th><th>Total</th></tr></thead><tbody><?php foreach($clientStatus as $row): ?><tr><td><?= esc($row['status']) ?></td><td><?= esc($row['total']) ?></td></tr><?php endforeach; ?></tbody></table></div></article><article class="panel"><div class="panel-header"><h3>User Roles Report</h3></div><div class="table-wrap"><table><thead><tr><th>Role</th><th>Total</th></tr></thead><tbody><?php foreach($userRole as $row): ?><tr><td><?= esc($row['role']) ?></td><td><?= esc($row['total']) ?></td></tr><?php endforeach; ?></tbody></table></div></article></section></main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

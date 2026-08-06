<?php
require_once __DIR__ . '/includes/header.php';
$stats = get_dashboard_stats();
$growth = monthly_client_growth();
$recentClients = recent_clients(6);
$followups = upcoming_followups(5);
include __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <section class="stats-grid">
        <article class="stat-card"><div><span>Total Clients</span><h3><?= $stats['clients'] ?></h3></div><div class="stat-icon"><i class="fa-solid fa-users"></i></div></article>
        <article class="stat-card"><div><span>Cards Uploaded</span><h3><?= $stats['cards'] ?></h3></div><div class="stat-icon"><i class="fa-regular fa-image"></i></div></article>
        <article class="stat-card"><div><span>Pending Follow-ups</span><h3><?= $stats['pending'] ?></h3></div><div class="stat-icon"><i class="fa-regular fa-bell"></i></div></article>
        <article class="stat-card"><div><span>Active Users</span><h3><?= $stats['users'] ?></h3></div><div class="stat-icon"><i class="fa-regular fa-user"></i></div></article>
    </section>

    <section class="panel-grid two-col">
        <article class="panel large-panel">
            <div class="panel-header"><h3>Client Growth</h3></div>
            <canvas id="growthChart" height="130"></canvas>
        </article>
        <article class="panel">
            <div class="panel-header"><h3>Follow-up Status</h3><span class="pill">Live Snapshot</span></div>
            <canvas id="statusChart" height="220"></canvas>
        </article>
    </section>

    <section class="panel-grid two-col bottom-panels">
        <article class="panel">
            <div class="panel-header"><h3>Recent Clients</h3><a class="btn-link" href="modules/clients.php">View All</a></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Company</th><th>Phone</th><th>Status</th><th>Assigned</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentClients as $client): ?>
                        <tr>
                            <td><?= esc($client['name']) ?></td>
                            <td><?= esc($client['company']) ?></td>
                            <td><?= esc($client['phone']) ?></td>
                            <td><span class="badge <?= status_badge_class($client['status']) ?>"><?= esc($client['status']) ?></span></td>
                            <td><?= esc($client['assigned_name'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header"><h3>Upcoming Follow-ups</h3></div>
            <div class="follow-list">
                <?php foreach ($followups as $item): ?>
                    <div class="follow-item">
                        <strong><?= esc($item['client_name']) ?></strong>
                        <p><?= date('d M, h:i A', strtotime($item['followup_date'])) ?> · <?= esc($item['notes']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<script>
const growthLabels = <?= json_encode(array_column($growth, 'month_name')) ?>;
const growthData = <?= json_encode(array_map('intval', array_column($growth, 'total'))) ?>;
const statusData = <?= json_encode([(int)$stats['pending'], (int)$stats['completed'], (int)$stats['overdue']]) ?>;
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

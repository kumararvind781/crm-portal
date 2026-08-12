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

    <style>
        .stat-card {
            min-height: 120px;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .stat-card .stat-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            width: 100%;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }

        .stat-card:hover {
            cursor: pointer;
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.07);
            transition: all 0.15s ease;
        }

        .chart-box {
            position: relative;
            width: 100%;
            height: 360px;
        }

        .large-panel .chart-box {
            height: 380px;
        }

        .chart-box-sm {
            height: 320px;
        }

        .chart-box canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
        .panel {
    transition: transform 0.2s ease;
}

.panel:hover {
    transform: translateX(-4px);
}
    </style>

    <!-- Top stats -->
    <section class="stats-grid">
        <article class="stat-card">
            <a href="<?= BASE_URL ?>modules/client_list.php" class="stat-link">
                <div class="stat-content">
                    <span>Total Clients</span>
                    <h3><?= (int) ($stats['clients'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </a>
        </article>

        <article class="stat-card">
            <a href="<?= BASE_URL ?>modules/company_list.php" class="stat-link">
                <div class="stat-content">
                    <span>Total Companies</span>
                    <h3><?= (int) ($stats['companies'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
            </a>
        </article>

        <article class="stat-card">
            <a href="<?= BASE_URL ?>modules/followup_list.php?status=pending" class="stat-link">
                <div class="stat-content">
                    <span>Open Follow-ups</span>
                    <?php
                    $openFollowups = (int) ($stats['pending'] ?? 0) + (int) ($stats['overdue'] ?? 0);
                    ?>
                    <h3><?= $openFollowups ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-regular fa-bell"></i>
                </div>
            </a>
        </article>

        <article class="stat-card">
            <a href="<?= BASE_URL ?>modules/user_list.php?status=active" class="stat-link">
                <div class="stat-content">
                    <span>Active Users</span>
                    <h3><?= (int) ($stats['users'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-regular fa-user"></i>
                </div>
            </a>
        </article>
    </section>

    <!-- Charts -->
    <section class="panel-grid two-col">
        <article class="panel large-panel">
            <div class="panel-header">
                <h3>Client Growth</h3>
            </div>
            <div class="chart-box">
                <canvas id="growthChart"></canvas>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h3>Follow-up Status</h3>
                <span class="pill">Live Snapshot</span>
            </div>
            <div class="chart-box chart-box-sm">
                <canvas id="statusChart"></canvas>
            </div>
        </article>
    </section>

    <!-- Tables -->
    <section class="panel-grid two-col bottom-panels">
        <article class="panel">
            <div class="panel-header">
                <h3>Recent Clients</h3>
                <a class="btn-link" href="modules/clients.php">View All</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentClients as $client): ?>
                            <tr>
                                <td><?= esc($client['name']) ?></td>
                                <td><?= esc($client['company'] ?? '-') ?></td>
                                <td><?= esc($client['company_phone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($client['status']) ?>">
                                        <?= esc($client['status']) ?>
                                    </span>
                                </td>
                                <td><?= esc($client['assigned_name'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h3>Upcoming Follow-ups</h3>
            </div>
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

<!-- Chart data from PHP -->
<script>
    const growthLabels = <?= json_encode(array_column($growth, 'month_name')) ?>;
    const growthData = <?= json_encode(array_map('intval', array_column($growth, 'total'))) ?>;

    const statusData = <?= json_encode([
        (int) ($stats['pending'] ?? 0),
        (int) ($stats['completed'] ?? 0),
        (int) ($stats['overdue'] ?? 0),
    ]) ?>;
</script>

<!-- Chart.js config -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const growthCtx = document.getElementById('growthChart');
        if (growthCtx) {
            new Chart(growthCtx, {
                type: 'bar',
                data: {
                    labels: growthLabels,
                    datasets: [{
                        label: 'Clients',
                        data: growthData,
                        backgroundColor: '#1f8f5f',
                        borderRadius: 8,
                        barThickness: 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,0.06)' }
                        }
                    }
                }
            });
        }

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Completed', 'Overdue'],
                    datasets: [{
                        data: statusData,
                        backgroundColor: ['#f7b500', '#1f8f5f', '#e63946'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 18,
                                boxHeight: 10,
                                padding: 14
                            }
                        }
                    }
                }
            });
        }
    });
</script>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
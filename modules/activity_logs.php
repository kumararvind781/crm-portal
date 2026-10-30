<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

require_login();

// Only Admin can view activity logs
if (!is_admin()) {
    http_response_code(403);
    exit('Access denied. Admin only.');
}

$pageTitle = 'Activity Logs';
$pageDescription = 'Monitor user activities across the CRM.';

// Filters
$userId = (int) ($_GET['user_id'] ?? 0);
$module = trim($_GET['module'] ?? '');
$event = trim($_GET['event'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');


// Export activity logs to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $exportRows = fetch_all("
        SELECT
            l.created_at,
            u.name AS user_name,
            l.event_type,
            l.module_name,
            l.action_name,
            l.record_type,
            l.record_id,
            l.description,
            l.ip_address
        FROM user_activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        ORDER BY l.id DESC
        LIMIT 10000
    ");

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=crm-activity-logs.csv');

    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'Date & Time',
        'User',
        'Activity',
        'Module',
        'Action',
        'Record Type',
        'Record ID',
        'Description',
        'IP Address'
    ]);

    foreach ($exportRows as $row) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
}


// Build query
$sql = "
    SELECT
        l.*,
        u.name AS user_name
    FROM user_activity_logs l
    LEFT JOIN users u ON u.id = l.user_id
    WHERE 1=1
";

$params = [];

if ($userId > 0) {
    $sql .= " AND l.user_id = ?";
    $params[] = $userId;
}

if ($module !== '') {
    $sql .= " AND l.module_name = ?";
    $params[] = $module;
}

if ($event !== '') {
    $sql .= " AND l.event_type = ?";
    $params[] = $event;
}

if ($fromDate !== '') {
    $sql .= " AND l.created_at >= ?";
    $params[] = $fromDate . ' 00:00:00';
}

if ($toDate !== '') {
    $sql .= " AND l.created_at <= ?";
    $params[] = $toDate . ' 23:59:59';
}

$sql .= " ORDER BY l.id DESC LIMIT 500";

$logs = fetch_all($sql, $params);

// Dropdown data
$users = fetch_all(
    "SELECT id, name FROM users ORDER BY name"
);

$modules = fetch_all(
    "SELECT DISTINCT module_name
     FROM user_activity_logs
     WHERE module_name IS NOT NULL
       AND module_name <> ''
     ORDER BY module_name"
);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>CRM Activity Logs</h2>
                <p>View user activities and changes across the CRM.</p>
            </div>

            <a href="<?= BASE_URL ?>modules/activity_logs.php?export=csv" class="btn btn-outline">
                <i class="fa fa-download"></i> Export CSV
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="form-grid mb-4">

            <div>
                <label>User</label>
                <select name="user_id">
                    <option value="">All Users</option>

                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>" <?= $userId === (int) $user['id'] ? 'selected' : '' ?>>
                            <?= esc($user['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label>Module</label>
                <select name="module">
                    <option value="">All Modules</option>

                    <?php foreach ($modules as $item): ?>
                        <option value="<?= esc($item['module_name']) ?>" <?= $module === $item['module_name'] ? 'selected' : '' ?>>
                            <?= esc($item['module_name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label>Activity</label>
                <select name="event">
                    <option value="">All Activities</option>

                    <?php foreach (
                        ['LOGIN', 'LOGOUT', 'PAGE_VIEW', 'CREATE', 'UPDATE', 'DELETE']
                        as $type
                    ): ?>
                        <option value="<?= esc($type) ?>" <?= $event === $type ? 'selected' : '' ?>>
                            <?= esc($type) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label>From Date</label>
                <input type="date" name="from_date" value="<?= esc($fromDate) ?>">
            </div>

            <div>
                <label>To Date</label>
                <input type="date" name="to_date" value="<?= esc($toDate) ?>">
            </div>

            <div class="d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> Filter
                </button>

                <a href="<?= BASE_URL ?>modules/activity_logs.php" class="btn btn-outline">
                    Reset
                </a>
            </div>

        </form>

        <!-- Activity Table -->
        <div class="table-responsive">

            <table class="crm-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Activity</th>
                        <th>Record ID</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($logs)): ?>

                        <tr>
                            <td colspan="8" class="text-center">
                                No activity records found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($logs as $index => $log): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= esc(date(
                                        'd M Y, h:i A',
                                        strtotime($log['created_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <?= esc($log['user_name'] ?? 'Unknown') ?>
                                </td>

                                <td>
                                    <?= esc($log['module_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <span class="badge">
                                        <?= esc($log['event_type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= esc($log['record_id'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($log['description'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($log['ip_address'] ?? '-') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="mt-3 text-muted">
            Showing latest <?= count($logs) ?> records (maximum 500).
        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
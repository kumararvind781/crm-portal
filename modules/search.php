<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Search';
$pageDescription = 'Search clients by name, company, phone, or email.';
$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $results = fetch_all('SELECT c.*, u.name AS assigned_name FROM clients c LEFT JOIN users u ON u.id = c.assigned_to WHERE c.name LIKE ? OR c.company LIKE ? OR c.phone LIKE ? OR c.email LIKE ? ORDER BY c.id DESC', [$like, $like, $like, $like]);
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<section class="panel"><div class="panel-header"><h3>Search Results</h3></div><?php if($q === ''): ?><p class="placeholder-text">Type a client name, company, email, or phone number in the search box.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Name</th><th>Company</th><th>Phone</th><th>Email</th><th>Status</th><th>Assigned</th></tr></thead><tbody><?php foreach($results as $row): ?><tr><td><?= esc($row['name']) ?></td><td><?= esc($row['company']) ?></td><td><?= esc($row['phone']) ?></td><td><?= esc($row['email']) ?></td><td><span class="badge <?= status_badge_class($row['status']) ?>"><?= esc($row['status']) ?></span></td><td><?= esc($row['assigned_name'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section></main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

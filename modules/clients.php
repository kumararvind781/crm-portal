<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Clients';
$pageDescription = 'View basic client list and open full client details when needed.';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $rows = fetch_all("
        SELECT
            c.*,
            co.company_name,
            u.name AS assigned_name
        FROM clients c
        LEFT JOIN companies co ON co.id = c.company_id
        LEFT JOIN users u ON u.id = c.assigned_to
        ORDER BY c.id DESC
    ");

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=clients-report.csv');

    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'First Name',
        'Last Name',
        'Company',
        'Email',
        'Mobile',
        'Status',
        'Assigned'
    ]);

    foreach ($rows as $row) {

        $fullName  = trim($row['name'] ?? '');
        $firstName = trim($row['first_name'] ?? '');
        $lastName  = trim($row['last_name'] ?? '');

        if ($firstName == '' && $fullName != '') {
            $parts = preg_split('/\s+/', $fullName);
            $firstName = $parts[0] ?? '';
            $lastName = implode(' ', array_slice($parts,1));
        }

        fputcsv($out,[
            $firstName,
            $lastName,
            $row['company_name'] ?? '',
            $row['email_id'] ?? '',
            $row['phone_number'] ?? '',
            $row['status'] ?? '',
            $row['assigned_name'] ?? ''
        ]);
    }

    fclose($out);
    exit;
}

if(isset($_GET['delete']) && is_admin()){

    $clientFiles = fetch_one(
        "SELECT photo, visiting_card FROM clients WHERE id=?",
        [(int)$_GET['delete']]
    );

    delete_file_if_exists($clientFiles['photo'] ?? null);
    delete_file_if_exists($clientFiles['visiting_card'] ?? null);

    execute_query(
        "DELETE FROM clients WHERE id=?",
        [(int)$_GET['delete']]
    );

    redirect_path('modules/clients.php');
}

$clients = fetch_all("
SELECT
    c.*,
    co.company_name,
    u.name AS assigned_name
FROM clients c
LEFT JOIN companies co
ON co.id=c.company_id
LEFT JOIN users u
ON u.id=c.assigned_to
ORDER BY c.id DESC
");

include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>

<main class="main-content">

<?php include __DIR__.'/../includes/topbar.php'; ?>

<section class="panel full-list-panel">

<div class="panel-header">

<h3>All Clients</h3>

<div class="header-actions">

<a class="btn-link"
href="<?=BASE_URL?>modules/clients.php?export=csv">
Export CSV
</a>

<a class="btn btn-primary"
href="<?=BASE_URL?>modules/client_create.php">
Add Client
</a>

</div>

</div>

<div class="table-wrap">

<table>

<thead>

<tr>
<th>Photo</th>
<th>First Name</th>
<th>Last Name</th>
<th>Company</th>
<th>Email</th>
<th>Mobile</th>
<th>Status</th>
<th>Assigned</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php foreach($clients as $row): ?>

<?php

$fullName=trim($row['name'] ?? '');

$firstName=trim($row['first_name'] ?? '');

$lastName=trim($row['last_name'] ?? '');

if($firstName=='' && $fullName!=''){

$parts=preg_split('/\s+/',$fullName);

$firstName=$parts[0] ?? '';

$lastName=implode(' ',array_slice($parts,1));

}

$id=(int)$row['id'];

?>

<tr>

<td>

<?php if(!empty($row['photo'])): ?>

<img
class="table-thumb"
src="<?=BASE_URL.esc($row['photo'])?>">

<?php else: ?>

N/A

<?php endif; ?>

</td>

<td><?=esc($firstName)?></td>

<td><?=esc($lastName)?></td>

<td><?=esc($row['company_name'] ?? '-')?></td>

<td><?=esc($row['email_id'] ?? '-')?></td>

<td><?=esc($row['phone_number'] ?? '-')?></td>

<td>

<span class="badge <?=status_badge_class($row['status'])?>">
<?=esc($row['status'])?>
</span>

</td>

<td><?=esc($row['assigned_name'] ?? '-')?></td>

<td>

<a class="table-action"
href="<?=BASE_URL?>modules/client_view.php?id=<?=$id?>">
More
</a>

<a class="table-action"
href="<?=BASE_URL?>modules/client_create.php?edit=<?=$id?>">
Edit
</a>

<?php if(is_admin()): ?>

<a
class="table-action delete"
href="<?=BASE_URL?>modules/clients.php?delete=<?=$id?>"
onclick="return confirm('Delete this client?')">
Delete
</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</section>

</main>

<?php include __DIR__.'/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$pageTitle = 'Interactions';
$pageDescription = 'View basic Interaction list and open full Interaction details when needed.';
$showClientPhoto = can_view_field('client_photo');
$showClientName = can_view_field('client_name');
$showClientStatus = can_view_field('client_status');
$showClientAssigned = can_view_field('client_assigned');
$showClientCompany = can_view_field('client_company');
$showClientEmail = can_view_field('client_personal_email');
$showClientMobile = can_view_field('client_mobile');


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
        ...($showClientName ? ['First Name', 'Last Name'] : []),
        ...($showClientCompany ? ['Company'] : []),
        ...($showClientEmail ? ['Email'] : []),
        ...($showClientMobile ? ['Mobile'] : []),
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
            ...($showClientName ? [$firstName, $lastName] : []),
            ...($showClientCompany ? [$row['company_name'] ?? ''] : []),
            ...($showClientEmail ? [$row['email_id'] ?? ''] : []),
            ...($showClientMobile ? [$row['phone_number'] ?? ''] : []),
            ...($showClientStatus ? [$row['status'] ?? ''] : []),
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

<h3>All Interactions</h3>

<div class="header-actions">

<a class="btn-link"
href="<?=BASE_URL?>modules/clients.php?export=csv">
Export CSV
</a>

<a class="btn btn-primary"
href="<?=BASE_URL?>modules/client_create.php">
Add Interaction
</a>

</div>

</div>

<div class="table-wrap">

<table>

<thead>

<tr>
<?php if ($showClientPhoto): ?><th>Photo</th><?php endif; ?>
<?php if ($showClientName): ?><th>First Name</th><th>Last Name</th><?php endif; ?>
<?php if ($showClientCompany): ?><th>Company</th><?php endif; ?>
<?php if ($showClientEmail): ?><th>Email</th><?php endif; ?>
<?php if ($showClientMobile): ?><th>Mobile</th><?php endif; ?>
<?php if ($showClientStatus): ?><th>Status</th><?php endif; ?>
<?php if ($showClientAssigned): ?><th>Assigned</th><?php endif; ?>
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

<?php if ($showClientPhoto): ?>
<td>
<?php if(!empty($row['photo'])): ?>
<img class="table-thumb" src="<?=BASE_URL.esc($row['photo'])?>">
<?php else: ?>N/A<?php endif; ?>
</td>
<?php endif; ?>

<?php if ($showClientName): ?><td><?=esc($firstName)?></td><td><?=esc($lastName)?></td><?php endif; ?>

<?php if ($showClientCompany): ?><td><?=esc($row['company_name'] ?? '-')?></td><?php endif; ?>
<?php if ($showClientEmail): ?><td><?=esc($row['email_id'] ?? '-')?></td><?php endif; ?>
<?php if ($showClientMobile): ?><td><?=esc($row['phone_number'] ?? '-')?></td><?php endif; ?>

<?php if ($showClientStatus): ?>
<td><span class="badge bg-secondary"><?= esc($row['status'] ?? '-') ?></span></td>
<?php endif; ?>

<?php if ($showClientAssigned): ?><td><?=esc($row['assigned_name'] ?? '-')?></td><?php endif; ?>

<td>

<a class="table-action"
href="<?=BASE_URL?>modules/client_view.php?id=<?=$id?>">
More
</a>

<?php if (($_SESSION['user']['role'] ?? '') !== 'Trainee'): ?>
<a class="table-action" href="<?=BASE_URL?>modules/client_create.php?edit=<?=$id?>">Edit</a>
<?php endif; ?>

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
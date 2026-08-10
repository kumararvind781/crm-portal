<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = "Companies";
$pageDescription = "Manage company master records.";

$search = trim($_GET['search'] ?? '');

if ($search != '') {
    $companies = fetch_all(
        "SELECT
            c.*,
            (SELECT COUNT(*) FROM contacts ct WHERE ct.company_id = c.id) AS total_contacts,
            (SELECT COUNT(*) FROM meetings m WHERE m.company_id = c.id) AS total_meetings,
            (SELECT COUNT(*) FROM followups f
                INNER JOIN communications cm ON cm.id=f.communication_id
                WHERE cm.company_id=c.id
                AND f.status='Pending') AS pending_followups
        FROM companies c
        WHERE company_name LIKE ?
           OR city LIKE ?
           OR industry LIKE ?
        ORDER BY company_name ASC",
        [
            "%$search%",
            "%$search%",
            "%$search%"
        ]
    );
} else {

    $companies = fetch_all(
        "SELECT
            c.*,
            (SELECT COUNT(*) FROM contacts ct WHERE ct.company_id = c.id) AS total_contacts,
            (SELECT COUNT(*) FROM meetings m WHERE m.company_id = c.id) AS total_meetings,
            (SELECT COUNT(*) FROM followups f
                INNER JOIN communications cm ON cm.id=f.communication_id
                WHERE cm.company_id=c.id
                AND f.status='Pending') AS pending_followups
        FROM companies c
        ORDER BY company_name ASC"
    );

}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>Company Master</h2>

                <p>Manage all companies in one place.</p>

            </div>

            <div>

                <a href="<?= BASE_URL ?>modules/company_create.php" class="btn btn-primary">

                    <i class="fa fa-plus"></i>

                    Add Company

                </a>

            </div>

        </div>

        <form method="GET" style="margin:25px 0;">

            <div class="search-box">

                <input type="text" name="search" value="<?= esc($search) ?>"
                    placeholder="Search Company, City, Industry">

                <button class="btn btn-primary">

                    <i class="fa fa-search"></i>

                    Search

                </button>

                <?php if ($search != ''): ?>

                    <a href="<?= BASE_URL ?>modules/company_list.php" class="btn btn-outline">

                        Reset

                    </a>

                <?php endif; ?>

            </div>

        </form>

        <div class="table-responsive">

            <table class="crm-table">

                <thead>

                    <tr>

                        <th width="60">#</th>

                        <th>Company</th>

                        <th>City</th>

                        <th>Industry</th>

                        <th align="center">Contacts</th>

                        <th align="center">Meetings</th>

                        <th align="center">Pending</th>

                        <th width="180">Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($companies) == 0): ?>

                        <tr>

                            <td colspan="8" align="center">

                                No Company Found

                            </td>

                        </tr>

                    <?php endif; ?>

                    <?php

                    $i = 1;

                    foreach ($companies as $row):

                        ?>

                        <tr>

                            <td><?= $i++ ?></td>

                            <td>

                                <strong>

                                    <?= esc($row['company_name']) ?>

                                </strong>

                                <br>

                                <small>

                                    <?= esc($row['website']) ?>

                                </small>

                            </td>

                            <td>

                                <?= esc($row['city']) ?>

                            </td>

                            <td>

                                <?= esc($row['industry']) ?>

                            </td>

                            <td align="center">

                                <span class="badge success">

                                    <?= $row['total_contacts'] ?>

                                </span>

                            </td>

                            <td align="center">

                                <span class="badge info">

                                    <?= $row['total_meetings'] ?>

                                </span>

                            </td>

                            <td align="center">

                                <span class="badge warning">

                                    <?= $row['pending_followups'] ?>

                                </span>

                            </td>

                            <td>

                                <a class="btn btn-sm btn-outline"
                                    href="<?= BASE_URL ?>modules/company_view.php?id=<?= $row['id'] ?>">

                                    View

                                </a>

                                <!-- <a class="btn btn-sm btn-primary"
                                    href="<?= BASE_URL ?>modules/company_edit.php?id=<?= $row['id'] ?>">

                                    Edit

                                </a> -->

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
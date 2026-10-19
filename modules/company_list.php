<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = "Companies";
$pageDescription = "Manage company master records.";

$search = trim($_GET['search'] ?? '');

/*
|--------------------------------------------------------------------------
| Company List Query
| Contacts count matches company_view.php logic
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.*,

        /* Current + historical clients */
        (
            SELECT COUNT(*)
            FROM clients cl
            WHERE cl.company_id = c.id
               OR cl.id IN (
                   SELECT coc.client_id
                   FROM client_old_companies coc
                   WHERE coc.company_id = c.id
               )
        ) AS total_contacts,

        /* Total meetings */
        (
            SELECT COUNT(*)
            FROM meetings m
            WHERE m.company_id = c.id
        ) AS total_meetings,

        /* Pending follow-ups */
       (
    SELECT COUNT(*)
    FROM follow_ups f
    INNER JOIN clients cl
        ON cl.id = f.client_id
    WHERE (
        cl.company_id = c.id
        OR cl.id IN (
            SELECT coc.client_id
            FROM client_old_companies coc
            WHERE coc.company_id = c.id
        )
    )
    AND f.status = 'Pending'
) AS pending_followups

    FROM companies c
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE c.company_name LIKE ?
           OR c.city LIKE ?
           OR c.industry LIKE ?
    ";

    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY c.company_name ASC";

$companies = fetch_all($sql, $params);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="panel">

        <!-- Page Header -->
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

        <!-- Search -->
        <form method="GET" style="margin:25px 0;">
            <div class="search-box">

                <input type="text" name="search" value="<?= esc($search) ?>"
                    placeholder="Search Company, City, Industry">

                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i>
                    Search
                </button>

                <?php if ($search !== ''): ?>
                    <a href="<?= BASE_URL ?>modules/company_list.php" class="btn btn-outline">
                        Reset
                    </a>
                <?php endif; ?>

            </div>
        </form>

        <!-- Company Table -->
        <div class="table-responsive">
            <table class="crm-table">

                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>Company</th>
                        <th>City</th>
                        <th>Industry</th>
                        <th align="center">Contacts</th>
                        <th align="center">Pending</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="7" align="center">
                                No Company Found
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php $i = 1; ?>

                        <?php foreach ($companies as $row): ?>
                            <tr>

                                <td><?= $i++ ?></td>

                                <td>
                                    <strong>
                                        <?= esc($row['company_name'] ?? '') ?>
                                    </strong>
                                    <br>
                                    <small>
                                        <?= esc($row['website'] ?? '') ?>
                                    </small>
                                </td>

                                <td>
                                    <?= esc($row['city'] ?? '') ?>
                                </td>

                                <td>
                                    <?= esc($row['industry'] ?? '') ?>
                                </td>

                                <!-- Contacts -->
                                <td align="center">
                                    <span class="badge success">
                                        <?= (int) ($row['total_contacts'] ?? 0) ?>
                                    </span>
                                </td>

                                <!-- Pending Follow-ups -->
                                <td align="center">
                                    <span class="badge warning">
                                        <?= (int) ($row['pending_followups'] ?? 0) ?>
                                    </span>
                                </td>

                                <!-- Action -->
                                <td>
                                    <a class="btn btn-sm btn-outline"
                                        href="<?= BASE_URL ?>modules/company_view.php?id=<?= (int) $row['id'] ?>">
                                        View
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = "Companies";
$pageDescription = "Manage company master records.";

$search = trim($_GET['search'] ?? '');

$showCompanyName = can_view_field('company_name');
$showCompanyLocation = can_view_field('company_location');
$showIndustry = can_view_field('company_industry');
$showWebsite = can_view_field('company_website');
$showContacts = can_view_field('company_contacts');
$showPending = can_view_field('company_followups');
$hasAnyCompanyVisibility = $showCompanyName || $showCompanyLocation || $showIndustry
    || $showWebsite || $showContacts || $showPending;

// Build search predicates only from fields this account may view.
// This avoids leaking hidden values through search-result existence.
$whereParts = [];
$params = [];
if ($search !== '') {
    $term = '%' . $search . '%';
    if ($showCompanyName) { $whereParts[] = 'c.company_name LIKE ?'; $params[] = $term; }
    if ($showCompanyLocation) { $whereParts[] = 'c.city LIKE ?'; $params[] = $term; }
    if ($showIndustry) { $whereParts[] = 'c.industry LIKE ?'; $params[] = $term; }
}

$sql = "
    SELECT
        c.id,
        c.company_name,
        c.city,
        c.industry,
        c.website,
        " . ($showContacts ? "(
            SELECT COUNT(*) FROM clients cl
            WHERE cl.company_id = c.id
               OR cl.id IN (
                    SELECT coc.client_id FROM client_old_companies coc
                    WHERE coc.company_id = c.id
               )
        )" : "0") . " AS total_contacts,
        " . ($showPending ? "(
            SELECT COUNT(*) FROM follow_ups f
            INNER JOIN clients cl ON cl.id = f.client_id
            WHERE (cl.company_id = c.id OR cl.id IN (
                SELECT coc.client_id FROM client_old_companies coc
                WHERE coc.company_id = c.id
            )) AND f.status = 'Pending'
        )" : "0") . " AS pending_followups
    FROM companies c
";
if (!$hasAnyCompanyVisibility) {
    $sql .= " WHERE 1 = 0";
} elseif ($search !== '') {
    if ($whereParts) {
        $sql .= " WHERE (" . implode(' OR ', $whereParts) . ")";
    } else {
        // No searchable fields are enabled for this Trainee.
        $sql .= " WHERE 1 = 0";
    }
}
$sql .= " ORDER BY " . ($showCompanyName ? "c.company_name" : "c.id") . " ASC";
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

            <?php if (($_SESSION['user']['role'] ?? '') !== 'Trainee'): ?>
            <div>
                <a href="<?= BASE_URL ?>modules/company_create.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i>
                    Add Company
                </a>
            </div>
            <?php endif; ?>
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
                        <?php if ($showCompanyName): ?><th>Company</th><?php endif; ?>
                        <?php if ($showCompanyLocation): ?><th>City</th><?php endif; ?>
                        <?php if ($showIndustry): ?><th>Industry</th><?php endif; ?>
                        <?php if ($showContacts): ?><th align="center">Contacts</th><?php endif; ?>
                        <?php if ($showPending): ?><th align="center">Pending</th><?php endif; ?>
                        <th width="180">Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="<?= 2 + (int)$showCompanyName + (int)$showCompanyLocation + (int)$showIndustry + (int)$showContacts + (int)$showPending ?>" align="center">
                                No Company Found
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php $i = 1; ?>

                        <?php foreach ($companies as $row): ?>
                            <tr>

                                <td><?= $i++ ?></td>

                                <?php if ($showCompanyName): ?>
                                <td>
                                    <strong><?= esc($row['company_name'] ?? '') ?></strong>
                                    <?php if ($showWebsite && !empty($row['website'])): ?><br><small><?= esc($row['website']) ?></small><?php endif; ?>
                                </td>
                                <?php endif; ?>

                                <?php if ($showCompanyLocation): ?><td><?= esc($row['city'] ?? '') ?></td><?php endif; ?>
                                <?php if ($showIndustry): ?><td><?= esc($row['industry'] ?? '') ?></td><?php endif; ?>

                                <?php if ($showContacts): ?>
                                <td align="center"><span class="badge success"><?= (int) ($row['total_contacts'] ?? 0) ?></span></td>
                                <?php endif; ?>

                                <?php if ($showPending): ?>
                                <td align="center"><span class="badge warning"><?= (int) ($row['pending_followups'] ?? 0) ?></span></td>
                                <?php endif; ?>

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
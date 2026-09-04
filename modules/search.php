<?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Search';
$pageDescription = 'Search clients and companies.';

$q = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {

    $like = '%' . $q . '%';

    $results = fetch_all("
        SELECT
            c.*,
            co.company_name,
            co.industry AS company_industry,
            co.company_email,
            co.company_phone,
            co.website AS company_website,
            co.city AS company_city,
            co.state AS company_state,
            co.country AS company_country,
            co.pincode AS company_pincode,
            u.name AS assigned_name

        FROM clients c

        LEFT JOIN companies co
            ON co.id = c.company_id

        LEFT JOIN users u
            ON u.id = c.assigned_to

        WHERE
            c.name LIKE ?
            OR c.first_name LIKE ?
            OR c.last_name LIKE ?
            OR c.designation LIKE ?
            OR c.person_address LIKE ?
            OR c.person_city LIKE ?
            OR c.person_country LIKE ?
            OR c.email_id LIKE ?
            OR c.business_email LIKE ?
            OR c.phone_number LIKE ?
            OR c.business_phone LIKE ?
            OR c.whatsapp_number LIKE ?
            OR c.linkedin_profile_url LIKE ?
            OR c.events_met_at LIKE ?
            OR c.referred_by_name LIKE ?
            OR c.referred_by_company LIKE ?
            OR c.referred_by_number LIKE ?
            OR c.referred_by_email LIKE ?
            OR c.status LIKE ?
            OR c.notes LIKE ?

            OR co.company_name LIKE ?
            OR co.industry LIKE ?
            OR co.company_email LIKE ?
            OR co.company_phone LIKE ?
            OR co.website LIKE ?
            OR co.city LIKE ?
            OR co.state LIKE ?
            OR co.country LIKE ?
            OR co.pincode LIKE ?
            OR co.lead_source LIKE ?
            OR co.prospect LIKE ?
            OR co.status LIKE ?

        ORDER BY c.id DESC
    ", array_fill(0, 32, $like));
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="panel">

        <div class="panel-header">
            <h3>Search Results</h3>

            <?php if ($q !== '' && !empty($results)): ?>

                <a
                    href="<?= BASE_URL ?>modules/search_export.php?q=<?= urlencode($q) ?>"
                    class="btn btn-primary"
                >
                    <i class="fa fa-file-excel"></i>
                    Export Excel
                </a>

            <?php endif; ?>
        </div>

        <?php if ($q === ''): ?>

            <p class="placeholder-text">
                Search by client name, company, location, phone, email,
                designation, industry, website, city, country, status,
                notes, or any other client/company detail.
            </p>

        <?php elseif (empty($results)): ?>

            <p class="placeholder-text">
                No clients or companies found for:
                <strong><?= esc($q) ?></strong>
            </p>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Designation</th>
                            <th>Email</th>
                            <th>Business Email</th>
                            <th>Phone</th>
                            <th>Business Phone</th>
                            <th>City</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($results as $row): ?>

                        <tr>

                            <td>
                                <strong><?= esc($row['name'] ?? '-') ?></strong>
                            </td>

                            <td>
                                <?= esc($row['company_name'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['designation'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['email_id'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['business_email'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['phone_number'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['business_phone'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['person_city'] ?? '-') ?>
                            </td>

                            <td>
                                <?= esc($row['person_country'] ?? '-') ?>
                            </td>

                            <td>
                                <span class="badge <?= status_badge_class($row['status'] ?? '') ?>">
                                    <?= esc($row['status'] ?? '-') ?>
                                </span>
                            </td>

                            <td>
                                <?= esc($row['assigned_name'] ?? '-') ?>
                            </td>

                            <td>
                                <a
                                    class="table-action"
                                    href="<?= BASE_URL ?>modules/client_view.php?id=<?= (int)$row['id'] ?>"
                                >
                                    View
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
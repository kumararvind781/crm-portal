<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Search';
$pageDescription = 'Search Interactions and companies.';

$q = trim($_GET['q'] ?? '');
$results = [];

$visible = [
    'client_name' => can_view_field('client_name'),
    'client_designation' => can_view_field('client_designation'),
    'client_company' => can_view_field('client_company'),
    'client_personal_email' => can_view_field('client_personal_email'),
    'client_business_email' => can_view_field('client_business_email'),
    'client_mobile' => can_view_field('client_mobile'),
    'client_office_phone' => can_view_field('client_office_phone'),
    'client_location' => can_view_field('client_location'),
    'client_person_address' => can_view_field('client_person_address'),
    'client_status' => can_view_field('client_status'),
    'client_assigned' => can_view_field('client_assigned'),
    'company_industry' => can_view_field('company_industry'),
    'company_email' => can_view_field('company_email'),
    'company_phone' => can_view_field('company_phone'),
    'company_website' => can_view_field('company_website'),
    'company_location' => can_view_field('company_location'),
    'company_sales' => can_view_field('company_sales'),
];

if ($q !== '') {
    $like = '%' . $q . '%';
    $searchable = [];

    $clientSearchMap = [
        'client_name' => ['c.name', 'c.first_name', 'c.last_name'],
        'client_designation' => ['c.designation'],
        'client_company' => ['co.company_name'],
        'client_personal_email' => ['c.email_id'],
        'client_business_email' => ['c.business_email'],
        'client_mobile' => ['c.phone_number', 'c.whatsapp_number'],
        'client_office_phone' => ['c.business_phone'],
        'client_person_address' => ['c.person_address'],
        'client_location' => ['c.person_city', 'c.person_country'],
        'client_status' => ['c.status'],
    ];
    $companySearchMap = [
        'company_industry' => ['co.industry'],
        'company_email' => ['co.company_email'],
        'company_phone' => ['co.company_phone'],
        'company_website' => ['co.website'],
        'company_location' => ['co.city', 'co.state', 'co.country', 'co.pincode'],
        'company_sales' => ['co.lead_source', 'co.prospect', 'co.status'],
    ];

    foreach ([$clientSearchMap, $companySearchMap] as $map) {
        foreach ($map as $permission => $columns) {
            if (can_view_field($permission)) {
                foreach ($columns as $column) {
                    $searchable[] = $column . ' LIKE ?';
                }
            }
        }
    }

    if ($searchable) {
        $results = fetch_all("
            SELECT
                c.id,
                c.name,
                c.designation,
                c.email_id,
                c.business_email,
                c.phone_number,
                c.business_phone,
                c.person_city,
                c.person_country,
                c.status,
                co.company_name,
                co.industry AS company_industry,
                co.company_email,
                co.company_phone,
                co.website AS company_website,
                co.city AS company_city,
                co.state AS company_state,
                co.country AS company_country,
                co.pincode AS company_pincode,
                co.lead_source AS company_lead_source,
                co.prospect AS company_prospect,
                co.status AS company_status,
                u.name AS assigned_name
            FROM clients c
            LEFT JOIN companies co ON co.id = c.company_id
            LEFT JOIN users u ON u.id = c.assigned_to
            WHERE (" . implode(' OR ', $searchable) . ")
            ORDER BY c.id DESC
        ", array_fill(0, count($searchable), $like));
    }
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
                <a href="<?= BASE_URL ?>modules/search_export.php?q=<?= urlencode($q) ?>" class="btn btn-primary">
                    <i class="fa fa-file-excel"></i> Export Excel
                </a>
            <?php endif; ?>
        </div>

        <?php if ($q === ''): ?>
            <p class="placeholder-text">Search Interactions and company information permitted for your account.</p>
        <?php elseif (empty($results)): ?>
            <p class="placeholder-text">No permitted records found for: <strong><?= esc($q) ?></strong></p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <?php if ($visible['client_name']): ?><th>Name</th><?php endif; ?>
                            <?php if ($visible['client_company']): ?><th>Company</th><?php endif; ?>
                            <?php if ($visible['client_designation']): ?><th>Designation</th><?php endif; ?>
                            <?php if ($visible['client_personal_email']): ?><th>Email</th><?php endif; ?>
                            <?php if ($visible['client_business_email']): ?><th>Business Email</th><?php endif; ?>
                            <?php if ($visible['client_mobile']): ?><th>Mobile / WhatsApp</th><?php endif; ?>
                            <?php if ($visible['client_office_phone']): ?><th>Office Phone</th><?php endif; ?>
                            <?php if ($visible['client_location']): ?><th>City</th><th>Country</th><?php endif; ?>
                            <?php if ($visible['client_status']): ?><th>Status</th><?php endif; ?>
                            <?php if ($visible['client_assigned']): ?><th>Assigned</th><?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <?php if ($visible['client_name']): ?><td><strong><?= esc($row['name'] ?? '-') ?></strong></td><?php endif; ?>
                                <?php if ($visible['client_company']): ?><td><?= esc($row['company_name'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_designation']): ?><td><?= esc($row['designation'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_personal_email']): ?><td><?= esc($row['email_id'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_business_email']): ?><td><?= esc($row['business_email'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_mobile']): ?><td><?= esc($row['phone_number'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_office_phone']): ?><td><?= esc($row['business_phone'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_location']): ?><td><?= esc($row['person_city'] ?? '-') ?></td><td><?= esc($row['person_country'] ?? '-') ?></td><?php endif; ?>
                                <?php if ($visible['client_status']): ?><td><span class="badge <?= status_badge_class($row['status'] ?? '') ?>"><?= esc($row['status'] ?? '-') ?></span></td><?php endif; ?>
                                <?php if ($visible['client_assigned']): ?><td><?= esc($row['assigned_name'] ?? '-') ?></td><?php endif; ?>
                                <td><a class="table-action" href="<?= BASE_URL ?>modules/client_view.php?id=<?= (int)$row['id'] ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    http_response_code(400);
    exit('No search value.');
}

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
            foreach ($columns as $column) $searchable[] = $column . ' LIKE ?';
        }
    }
}
if (!$searchable) {
    http_response_code(403);
    exit('No search fields are enabled for this account.');
}

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

$columns = [];
$add = static function (string $permission, string $heading, string $key) use (&$columns): void {
    if (can_view_field($permission)) $columns[] = [$heading, $key];
};
$add('client_name', 'Client Name', 'name');
$add('client_name', 'First Name', 'first_name');
$add('client_name', 'Last Name', 'last_name');
$add('client_designation', 'Designation', 'designation');
$add('client_company', 'Company', 'company_name');
$add('company_industry', 'Company Industry', 'company_industry');
$add('client_personal_email', 'Email', 'email_id');
$add('client_business_email', 'Business Email', 'business_email');
$add('client_mobile', 'Mobile', 'phone_number');
$add('client_mobile', 'WhatsApp', 'whatsapp_number');
$add('client_office_phone', 'Business Phone', 'business_phone');
$add('client_person_address', 'Person Address', 'person_address');
$add('client_location', 'Person City', 'person_city');
$add('client_location', 'Person Country', 'person_country');
$add('company_email', 'Company Email', 'company_email');
$add('company_phone', 'Company Phone', 'company_phone');
$add('company_website', 'Company Website', 'company_website');
$add('company_location', 'Company City', 'company_city');
$add('company_location', 'Company State', 'company_state');
$add('company_location', 'Company Country', 'company_country');
$add('company_location', 'Company Pincode', 'company_pincode');
$add('client_linkedin', 'LinkedIn', 'linkedin_profile_url');
$add('client_events', 'Events Met At', 'events_met_at');
$add('client_referral', 'Referred By', 'referred_by_name');
$add('client_referral', 'Referred Company', 'referred_by_company');
$add('client_referral', 'Referred Number', 'referred_by_number');
$add('client_referral', 'Referred Email', 'referred_by_email');
$add('client_status', 'Client Status', 'status');
$add('company_sales', 'Company Lead Source', 'company_lead_source');
$add('company_sales', 'Company Prospect', 'company_prospect');
$add('company_sales', 'Company Status', 'company_status');
$add('client_assigned', 'Assigned To', 'assigned_name');
$add('client_notes', 'Notes', 'notes');

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="client_company_search.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
echo "<table border='1'><tr>";
foreach ($columns as [$heading, $key]) {
    echo '<th>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</th>';
}
echo "</tr>";

foreach ($results as $row) {
    echo "<tr>";
    foreach ($columns as [$heading, $key]) {
        $value = $row[$key] ?? '';
        echo '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo "</tr>";
}
echo "</table>";
exit;

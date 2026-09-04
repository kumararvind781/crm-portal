    <?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    exit('No search value.');
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


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="client_company_search.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "<table border='1'>";

echo "<tr>";
echo "<th>Client Name</th>";
echo "<th>First Name</th>";
echo "<th>Last Name</th>";
echo "<th>Designation</th>";
echo "<th>Company</th>";
echo "<th>Company Industry</th>";
echo "<th>Email</th>";
echo "<th>Business Email</th>";
echo "<th>Phone</th>";
echo "<th>Business Phone</th>";
echo "<th>WhatsApp</th>";
echo "<th>Person Address</th>";
echo "<th>Person City</th>";
echo "<th>Person Country</th>";
echo "<th>Company Email</th>";
echo "<th>Company Phone</th>";
echo "<th>Company Website</th>";
echo "<th>Company City</th>";
echo "<th>Company State</th>";
echo "<th>Company Country</th>";
echo "<th>Company Pincode</th>";
echo "<th>LinkedIn</th>";
echo "<th>Events Met At</th>";
echo "<th>Referred By</th>";
echo "<th>Referred Company</th>";
echo "<th>Referred Number</th>";
echo "<th>Referred Email</th>";
echo "<th>Client Status</th>";
echo "<th>Company Status</th>";
echo "<th>Assigned To</th>";
echo "<th>Notes</th>";
echo "</tr>";

foreach ($results as $row) {

    echo "<tr>";

    $values = [
        $row['name'] ?? '',
        $row['first_name'] ?? '',
        $row['last_name'] ?? '',
        $row['designation'] ?? '',
        $row['company_name'] ?? '',
        $row['company_industry'] ?? '',
        $row['email_id'] ?? '',
        $row['business_email'] ?? '',
        $row['phone_number'] ?? '',
        $row['business_phone'] ?? '',
        $row['whatsapp_number'] ?? '',
        $row['person_address'] ?? '',
        $row['person_city'] ?? '',
        $row['person_country'] ?? '',
        $row['company_email'] ?? '',
        $row['company_phone'] ?? '',
        $row['company_website'] ?? '',
        $row['company_city'] ?? '',
        $row['company_state'] ?? '',
        $row['company_country'] ?? '',
        $row['company_pincode'] ?? '',
        $row['linkedin_profile_url'] ?? '',
        $row['events_met_at'] ?? '',
        $row['referred_by_name'] ?? '',
        $row['referred_by_company'] ?? '',
        $row['referred_by_number'] ?? '',
        $row['referred_by_email'] ?? '',
        $row['status'] ?? '',
        $row['company_status'] ?? '',
        $row['assigned_name'] ?? '',
        $row['notes'] ?? ''
    ];

    foreach ($values as $value) {
        echo '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
    }

    echo "</tr>";
}

echo "</table>";
exit;
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json');

$keyword = trim($_GET['keyword'] ?? '');

if ($keyword == '') {
    echo json_encode([]);
    exit;
}

$companies = fetch_all(

"SELECT

id,
company_name,
city,
industry,
company_phone,
company_email

FROM companies

WHERE

company_name LIKE ?
OR city LIKE ?
OR industry LIKE ?

ORDER BY company_name

LIMIT 20",

[
"%{$keyword}%",
"%{$keyword}%",
"%{$keyword}%"
]

);

$result = [];

foreach ($companies as $row) {

    $contacts = fetch_one(
        "SELECT COUNT(*) total
         FROM contacts
         WHERE company_id=?",
        [$row['id']]
    );

    $meetings = fetch_one(
        "SELECT COUNT(*) total
         FROM meetings
         WHERE company_id=?",
        [$row['id']]
    );

    $communications = fetch_one(
        "SELECT COUNT(*) total
         FROM communications
         WHERE company_id=?",
        [$row['id']]
    );

    $result[] = [

        'id' => $row['id'],

        'company_name' => $row['company_name'],

        'city' => $row['city'],

        'industry' => $row['industry'],

        'phone' => $row['company_phone'],

        'email' => $row['company_email'],

        'contacts' => $contacts['total'],

        'meetings' => $meetings['total'],

        'communications' => $communications['total']

    ];
}

echo json_encode($result);
exit;
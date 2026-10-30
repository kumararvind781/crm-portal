
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_path('modules/company_list.php');
    exit;
}

$id            = (int) ($_POST['id'] ?? 0);
$company_name  = trim($_POST['company_name'] ?? '');
$industry      = trim($_POST['industry'] ?? '');
$address       = trim($_POST['address'] ?? '');
$city          = trim($_POST['city'] ?? '');
$state         = trim($_POST['state'] ?? '');
$country       = trim($_POST['country'] ?? '');
$pincode       = trim($_POST['pincode'] ?? '');
$website       = trim($_POST['website'] ?? '');
$linkedin      = trim($_POST['linkedin'] ?? '');
$company_email = trim($_POST['company_email'] ?? '');
$company_phone = trim($_POST['company_phone'] ?? '');
$prospect      = trim($_POST['prospect'] ?? '');
$lead_source   = trim($_POST['lead_source'] ?? '');
$assigned_to   = (int) ($_POST['assigned_to'] ?? 0);
$status        = trim($_POST['status'] ?? 'Active');
$remarks       = trim($_POST['remarks'] ?? '');

$systems = array_values(array_unique(array_filter(array_map(
    'trim',
    explode(',', (string) ($_POST['systems_used'] ?? ''))
))));

$other = trim($_POST['other_system'] ?? '');

if ($other !== '') {
    execute_query(
        'INSERT IGNORE INTO master_systems (system_name) VALUES (?)',
        [$other]
    );

    if (!in_array($other, $systems, true)) {
        $systems[] = $other;
    }
}

$systems_used = implode(',', $systems);

$back = $id > 0
    ? 'modules/company_edit.php?id=' . $id
    : 'modules/company_create.php';

if ($company_name === '') {
    $_SESSION['error'] = 'Company Name is required.';
    redirect_path($back);
    exit;
}

$duplicate = $id > 0
    ? fetch_one(
        'SELECT id FROM companies WHERE company_name=? AND id<>?',
        [$company_name, $id]
    )
    : fetch_one(
        'SELECT id FROM companies WHERE company_name=?',
        [$company_name]
    );

if ($duplicate) {
    $_SESSION['error'] = 'Company already exists.';
    redirect_path($back);
    exit;
}

// Prepare company data
$data = [
    $company_name,
    $industry,
    $address,
    $city,
    $state,
    $country,
    $pincode,
    $website,
    $company_email,
    $company_phone,
    $linkedin,
    $systems_used,
    $prospect,
    $lead_source,
    $assigned_to,
    $status,
    $remarks
];

// Fields to compare for UPDATE audit
$fieldNames = [
    'company_name',
    'industry',
    'address',
    'city',
    'state',
    'country',
    'pincode',
    'website',
    'company_email',
    'company_phone',
    'linkedin',
    'systems_used',
    'prospect',
    'lead_source',
    'assigned_to',
    'status',
    'remarks'
];

if ($id === 0) {

    // CREATE COMPANY
    $saved = execute_query(
        'INSERT INTO companies
        (
            company_name, industry, address, city, state,
            country, pincode, website, company_email,
            company_phone, linkedin, systems_used,
            prospect, lead_source, assigned_to,
            status, remarks, created_by
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($data, [$_SESSION['user']['id']])
    );

    if ($saved) {
        global $pdo;

        $newCompanyId = (int) $pdo->lastInsertId();

        audit_log(
            'CREATE',
            'Companies',
            'Company created',
            'company',
            (string) $newCompanyId,
            'New company created: ' . $company_name
        );

        $_SESSION['success'] = 'Company added successfully.';
    } else {
        $_SESSION['error'] = 'Unable to add company.';
    }

} else {

    // Get OLD company details before UPDATE
    $oldCompany = fetch_one(
        'SELECT * FROM companies WHERE id = ?',
        [$id]
    );

    if (!$oldCompany) {
        $_SESSION['error'] = 'Company not found.';
        redirect_path('modules/company_list.php');
        exit;
    }

    // UPDATE COMPANY
    $updated = execute_query(
        'UPDATE companies SET
            company_name=?,
            industry=?,
            address=?,
            city=?,
            state=?,
            country=?,
            pincode=?,
            website=?,
            company_email=?,
            company_phone=?,
            linkedin=?,
            systems_used=?,
            prospect=?,
            lead_source=?,
            assigned_to=?,
            status=?,
            remarks=?
         WHERE id=?',
        array_merge($data, [$id])
    );

    if ($updated) {

        $changedFields = [];

        foreach ($fieldNames as $index => $field) {
            if (
                (string) ($oldCompany[$field] ?? '') !==
                (string) ($data[$index] ?? '')
            ) {
                $changedFields[] = $field;
            }
        }

        audit_log(
            'UPDATE',
            'Companies',
            'Company updated',
            'company',
            (string) $id,
            'Company: ' . $company_name .
            ' | Changed fields: ' .
            (empty($changedFields)
                ? 'No field changes'
                : implode(', ', $changedFields))
        );

        $_SESSION['success'] = 'Company updated successfully.';

    } else {
        $_SESSION['error'] = 'Unable to update company.';
    }
}

redirect_path('modules/company_list.php');
exit;

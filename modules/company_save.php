<?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

// echo "<pre>";
// print_r($_SESSION['user']);
// exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_path('modules/company_list.php');
    exit;
}




/* ===========================
   Collect Form Data
=========================== */

$id = (int) ($_POST['id'] ?? 0);

$company_name = trim($_POST['company_name'] ?? '');
$industry = trim($_POST['industry'] ?? '');
$company_address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$country = trim($_POST['country'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');

$website = trim($_POST['website'] ?? '');
$linkedin_url = trim($_POST['linkedin'] ?? '');

$company_email = trim($_POST['company_email'] ?? '');
$company_phone = trim($_POST['company_phone'] ?? '');

$systems_used = trim($_POST['systems_used'] ?? '');

$prospect_level = trim($_POST['prospect'] ?? '');
$lead_source = trim($_POST['lead_source'] ?? '');

$assigned_to = (int) ($_POST['assigned_to'] ?? 0);

$status = trim($_POST['status'] ?? 'Active');

$remarks = trim($_POST['remarks'] ?? '');


/* ===========================
   Validation
=========================== */

if ($company_name == '') {

    $_SESSION['error'] = "Company Name is required.";

    redirect_path('modules/company_create.php');

    exit;
}


/* ===========================
   Duplicate Check
=========================== */

if ($id == 0) {

    $duplicate = fetch_one(
        "SELECT id
         FROM companies
         WHERE company_name=?",
        [$company_name]
    );

} else {

    $duplicate = fetch_one(
        "SELECT id
         FROM companies
         WHERE company_name=?
         AND id<>?",
        [$company_name, $id]
    );

}

if ($duplicate) {

    $_SESSION['error'] = "Company already exists.";

    if ($id > 0) {
        redirect_path("modules/company_edit.php?id=" . $id);
    } else {
        redirect_path("modules/company_create.php");
    }

    exit;
}

/* ===========================
   INSERT
=========================== */

if ($id == 0) {

    execute_query(

        "INSERT INTO companies (
    company_name,
    industry,
    address,
    city,
    state,
    country,
    website,
    linkedin,
    systems_used,
    prospect,
    remarks,
    created_by
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",

        [
            $company_name,
            $industry,
            $address,
            $city,
            $state,
            $country,
            $website,
            $linkedin,
            $systems_used,
            $prospect,
            $remarks,
            $_SESSION['user']['id']
        ]

    );

    $_SESSION['success'] = "Company added successfully.";

}

/* ===========================
   UPDATE
=========================== */ else {

    execute_query(

        "UPDATE companies SET
            company_name=?,
            industry=?,
            address=?,
            city=?,
            state=?,
            country=?,
            website=?,
            linkedin=?,
            systems_used=?,
            prospect=?,
            remarks=?
        WHERE id=?",

        [
            $company_name,
            $industry,
            $address,
            $city,
            $state,
            $country,
            $website,
            $linkedin,
            $systems_used,
            $prospect,
            $remarks,
            $id
        ]

    );

    $_SESSION['success'] = "Company updated successfully.";

}

/* ===========================
   Redirect
=========================== */

redirect_path('modules/company_list.php');
exit;
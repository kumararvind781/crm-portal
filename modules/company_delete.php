<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid Company.";
    redirect_path('modules/company_list.php');
    exit;
}

/* Check Company */

$company = fetch_one(
    "SELECT id,company_name FROM companies WHERE id=?",
    [$id]
);

if (!$company) {
    $_SESSION['error'] = "Company not found.";
    redirect_path('modules/company_list.php');
    exit;
}

/* Check Contacts */

$contact = fetch_one(
    "SELECT COUNT(*) total
     FROM contacts
     WHERE company_id=?",
    [$id]
);

if ($contact['total'] > 0) {

    $_SESSION['error'] =
    "Cannot delete company. Contacts are attached.";

    redirect_path('modules/company_view.php?id='.$id);
    exit;
}

/* Check Meetings */

$meeting = fetch_one(
    "SELECT COUNT(*) total
     FROM meetings
     WHERE company_id=?",
    [$id]
);

if ($meeting['total'] > 0) {

    $_SESSION['error'] =
    "Cannot delete company. Meetings are attached.";

    redirect_path('modules/company_view.php?id='.$id);
    exit;
}

/* Check Communications */

$communication = fetch_one(
    "SELECT COUNT(*) total
     FROM communications
     WHERE company_id=?",
    [$id]
);

if ($communication['total'] > 0) {

    $_SESSION['error'] =
    "Cannot delete company. Communication history exists.";

    redirect_path('modules/company_view.php?id='.$id);
    exit;
}

/* Delete Company */

execute_query(
    "DELETE FROM companies WHERE id=?",
    [$id]
);

$_SESSION['success'] =
"Company deleted successfully.";

redirect_path('modules/company_list.php');
exit;
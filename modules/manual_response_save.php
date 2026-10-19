<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$client_id = (int) ($_POST['client_id'] ?? 0);
$communication_by = trim($_POST['communication_by'] ?? '');
$response = trim($_POST['response'] ?? '');

$client = fetch_one(
    "SELECT company_id FROM clients WHERE id = ?",
    [$client_id]
);

$company_id = !empty($client['company_id'])
    ? (int) $client['company_id']
    : null;

$redirect = BASE_URL .
    "modules/client_view.php?id=" . $client_id .
    "#manual-response";

if (!$client || $client_id <= 0) {
    $_SESSION['error'] = "Invalid client selected.";
    header("Location: " . BASE_URL . "modules/clientlist.php");
    exit;
}

/*
 * Validate response and PDF.
 * PDF-only uploads are also allowed.
 */
if ($communication_by === '') {
    $_SESSION['error'] = "Please select who is responding.";
    header("Location: " . $redirect);
    exit;
}

$filePath = null;

$attachmentName = null;

if (
    isset($_FILES['attachment']) &&
    $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE
) {

    $file = $_FILES['attachment'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "PDF upload failed. Please try again.";
        header("Location: " . $redirect);
        exit;
    }

    // Maximum file size: 10 MB
    if ($file['size'] > 10 * 1024 * 1024) {
        $_SESSION['error'] = "PDF size must not exceed 10 MB.";
        header("Location: " . $redirect);
        exit;
    }

    // Verify actual file type, not only extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (
        strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf' ||
        $mime !== 'application/pdf'
    ) {
        $_SESSION['error'] = "Only valid PDF files are allowed.";
        header("Location: " . $redirect);
        exit;
    }

    // Use the existing project upload helper
    $uploadDir = __DIR__ . '/../uploads/manual_responses/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $_SESSION['error'] = "Unable to create upload directory.";
            header("Location: " . $redirect);
            exit;
        }
    }

    $fileName = bin2hex(random_bytes(16)) . '.pdf';

    $attachmentName = basename($file['name']);
$attachmentName = preg_replace(
    '/[^A-Za-z0-9._ -]/',
    '_',
    $attachmentName
);
    $destination = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $_SESSION['error'] = "Unable to save the uploaded PDF.";
        header("Location: " . $redirect);
        exit;
    }

    $filePath = 'uploads/manual_responses/' . $fileName;

    if (empty($filePath)) {
        $_SESSION['error'] = "Unable to save the PDF.";
        header("Location: " . $redirect);
        exit;
    }
}

// Require either a response message or a PDF.
if ($response === '' && empty($filePath)) {
    $_SESSION['error'] = "Please enter a response or select a PDF.";
    header("Location: " . $redirect);
    exit;
}

$user_id = (int) ($_SESSION['user']['id'] ?? 0);

execute_query("
    INSERT INTO client_manual_responses
    (
        client_id,
        company_id,
        communication_by,
        response,
        attachment,
        attachment_name,
        created_by
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
", [
    $client_id,
    $company_id,
    $communication_by,
    $response,
    $filePath,
    $attachmentName,
    $user_id
]);

$_SESSION['success'] = "Manual response saved successfully.";

header("Location: " . $redirect);
exit;

<?php
session_start();

require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit;
}

$client_id = (int)($_POST['client_id'] ?? 0);
$communication_by = trim($_POST['communication_by'] ?? '');
$response = trim($_POST['response'] ?? '');

if ($client_id <= 0 || $communication_by == '' || $response == '') {
    $_SESSION['error'] = "Please fill all required fields.";
    header("Location: ".BASE_URL."modules/client_view.php?id=".$client_id."#manual-response");
exit;
}

/* Logged in user id */
$user_id = $_SESSION['user']['id'] ?? 0;

execute_query("
INSERT INTO client_manual_responses
(
    client_id,
    communication_by,
    response,
    created_by
)
VALUES
(
    ?, ?, ?, ?
)
", [
    $client_id,
    $communication_by,
    $response,
    $user_id
]);

$_SESSION['success'] = "Manual response added successfully.";

header("Location: ".BASE_URL."modules/client_view.php?id=".$client_id."#manual-response");
exit;
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Profile';
$pageDescription = 'Your current account summary and access role.';
$user = $_SESSION['user'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<section class="panel"><div class="panel-header"><h3>My Profile</h3></div><div class="profile-box"><div class="profile-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div><div><h4><?= esc($user['name']) ?></h4><p><?= esc($user['email']) ?></p><span class="badge success"><?= esc($user['role']) ?></span></div></div></section></main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

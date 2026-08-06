<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Settings';
$pageDescription = 'Application defaults and account-level CRM preferences.';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
<section class="panel"><div class="panel-header"><h3>General Settings</h3></div><div class="settings-grid"><div class="setting-box"><strong>Timezone</strong><p>Asia/Kolkata</p></div><div class="setting-box"><strong>Default Status</strong><p>Active</p></div><div class="setting-box"><strong>Reminder Mode</strong><p>Manual dashboard follow-ups</p></div></div></section></main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

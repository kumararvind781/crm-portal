<?php
require_once __DIR__ . '/../includes/db.php';

$newPassword = '123';

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ?");
$stmt->execute([$hash]);

echo "<h2>✅ All passwords updated successfully.</h2>";
echo "<h3>New Password : 123</h3>";
echo "<textarea rows='3' cols='100'>$hash</textarea>";
?>
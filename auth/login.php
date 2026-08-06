<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in())
    redirect_path('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = fetch_one(
        "SELECT * FROM users
     WHERE email = ?
        OR name = ?
     LIMIT 1",
        [$login, $login]
    );
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        redirect_path('index.php');
    }
    $error = 'Invalid email or password';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRM Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>

<body class="auth-body">
    <div class="login-card">
        <h1>CRM Portal</h1>
        <p>Login to manage clients, follow-ups and uploads.</p><?php if ($error): ?>
            <div class="alert error"><?= esc($error) ?></div><?php endif; ?>
        <form method="post" class="form-grid">
            <input type="text" name="login" placeholder="Email or Username" required>
            <div><label>Password</label><input type="password" name="password" value="password" required></div><button
                type="submit" class="btn btn-primary full">Login</button>
        </form><small>Demo: admin@crm.com / password</small>
    </div>
</body>

</html>
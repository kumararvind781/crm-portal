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

<style>

body.auth-body{
    margin:0;
    font-family:Inter,sans-serif;
    background:#eef6f1;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.login-box{
    width:460px;
    background:#fff;
    border-radius:22px;
    padding:40px;
    box-shadow:0 15px 45px rgba(0,0,0,.12);
}

.logo{
    text-align:center;
    margin-bottom:20px;
}

.logo img{
    width:220px;
}

h1{
    text-align:center;
    margin:10px 0;
    font-size:42px;
    font-weight:800;
    color:#1b2b34;
}

.subtitle{
    text-align:center;
    color:#666;
    margin-bottom:30px;
}

.mb-3,
.mb-4{
    margin-bottom:20px;
}

label{
    display:block;
    font-weight:600;
    margin-bottom:8px;
}

.form-control{
    width:100%;
    height:55px;
    border:1px solid #d9d9d9;
    border-radius:14px;
    padding:0 18px;
    font-size:16px;
    box-sizing:border-box;
}

.form-control:focus{
    outline:none;
    border-color:#218c5a;
}

.btn-login{
    width:100%;
    height:56px;
    border:none;
    border-radius:14px;
    background:#218c5a;
    color:#fff;
    font-size:18px;
    font-weight:700;
    cursor:pointer;
}

.btn-login:hover{
    background:#1b754b;
}

.footer{
    margin-top:25px;
    text-align:center;
    color:#666;
    font-size:14px;
}

.alert.error{
    background:#ffe9e9;
    color:#b50000;
    padding:12px;
    border-radius:10px;
    margin-bottom:20px;
}

</style>
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

<div class="login-box">

    <div class="logo">
        <img src="<?= BASE_URL ?>uploads/Logo/Unire-Business-Solutions-Pvt-Ltd.png" alt="UNIRE">
    </div>

    <h1>CRM Portal</h1>
    <p class="subtitle">Login to manage clients, follow-ups and uploads.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="post">

        <div class="mb-3">
            <label>Email or Username</label>
            <input type="text" name="login" class="form-control" required>
        </div>

        <div class="mb-4">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn-login" type="submit">
            Login
        </button>

    </form>

    <div class="footer">
        © <?= date('Y') ?> Unire Business Solutions Pvt. Ltd.
    </div>

</div>

</body>

</html>
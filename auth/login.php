
<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect_path('index.php');
}

$error = '';
$resetSuccess = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = fetch_one(
        "SELECT * FROM users
         WHERE email = ? OR name = ?
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

    <style>
        body.auth-body {
            margin: 0;
            font-family: Inter, sans-serif;
            background: #eef6f1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-box {
            width: 460px;
            max-width: calc(100% - 30px);
            box-sizing: border-box;
            background: #fff;
            border-radius: 22px;
            padding: 40px;
            box-shadow: 0 15px 45px rgba(0,0,0,.12);
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 220px;
            max-width: 100%;
        }

        h1 {
            text-align: center;
            margin: 10px 0;
            font-size: 42px;
            font-weight: 800;
            color: #1b2b34;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .mb-3, .mb-4 {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            height: 55px;
            border: 1px solid #d9d9d9;
            border-radius: 14px;
            padding: 0 18px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #218c5a;
        }

        .btn-login {
            width: 100%;
            height: 56px;
            border: none;
            border-radius: 14px;
            background: #218c5a;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #1b754b;
        }

        .reset-wrapper {
            position: relative;
        }

        .btn-reset {
            display: block;
            width: 100%;
            height: 48px;
            margin-top: 12px;
            border: 1px solid #218c5a;
            border-radius: 12px;
            background: #fff;
            color: #218c5a;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
        }

        .btn-reset:hover {
            background: #eef6f1;
        }

        .reset-tooltip {
            display: none;
            margin-top: 8px;
            padding: 10px;
            border-radius: 8px;
            background: #1b2b34;
            color: #fff;
            font-size: 13px;
            text-align: center;
        }

        .reset-wrapper:hover .reset-tooltip,
        .reset-wrapper:focus-within .reset-tooltip {
            display: block;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert.error {
            background: #ffe9e9;
            color: #b50000;
        }

        .alert.success {
            background: #e5f8eb;
            color: #176b36;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 500px) {
            .login-box {
                padding: 25px 20px;
            }

            h1 {
                font-size: 34px;
            }
        }
    </style>
</head>

<body class="auth-body">

<div class="login-box">

    <div class="logo">
        <img src="<?= BASE_URL ?>uploads/Logo/Unire-Business-Solutions-Pvt-Ltd.png"
             alt="UNIRE">
    </div>

    <h1>CRM Portal</h1>
    <p class="subtitle">
        Login to manage clients, follow-ups and uploads.
    </p>

    <?php if ($resetSuccess !== ''): ?>
        <div class="alert success">
            <?= htmlspecialchars($resetSuccess, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="login">Email or Username</label>
            <input type="text" id="login" name="login"
                   class="form-control" autocomplete="username" required>
        </div>

        <div class="mb-4">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   class="form-control" autocomplete="current-password" required>
        </div>

        <button class="btn-login" type="submit">Login</button>
    </form>

    <div class="reset-wrapper">
        <a class="btn-reset"
           href="<?= BASE_URL ?>modules/reset_password.php"
           style="text-decoration:none; line-height:48px; box-sizing:border-box;">
            Reset Password
        </a>

        <div class="reset-tooltip">
            Go to reset_password.php to reset the password.
        </div>
    </div>

    <div class="footer">
        © <?= date('Y') ?> Unire Business Solutions Pvt. Ltd.
    </div>

</div>

</body>
</html>

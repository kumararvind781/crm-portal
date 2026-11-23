<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail_config.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/otp_mailer.php';

// Session cookie configuration.
// HTTPS is automatically detected, so local WAMP HTTP testing works too.
if (session_status() !== PHP_SESSION_ACTIVE) {

    $isHttps = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Already logged in
if (is_logged_in()) {
    redirect_path('index.php');
    exit;
}

$error = '';

$resetSuccess = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_success']);

// CSRF token
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

// OTP step status
$otpStep = !empty($_SESSION['pending_login']);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['login_csrf'], $csrf)
    ) {
        http_response_code(403);
        exit('Invalid request. Please reload the page.');
    }



    /*
|--------------------------------------------------------------------------
| STEP 1: USERNAME AND PASSWORD
|--------------------------------------------------------------------------
*/

    if (isset($_POST['submit_login'])) {

        // Do not allow another password submission during OTP verification.
        if (!empty($_SESSION['pending_login'])) {

            $otpStep = true;
            $error = 'Please complete the OTP verification first.';

        } else {

            $login = trim((string) ($_POST['login'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($login === '' || $password === '') {

                $error = 'Please enter username and password.';

            } else {

                $user = fetch_one(
                    "SELECT * FROM users
                 WHERE email = ? OR name = ?
                 LIMIT 1",
                    [$login, $login]
                );

                if (
                    !$user ||
                    !password_verify($password, $user['password'])
                ) {

                    $error = 'Invalid email or password';

                } else {

                    // Check individual user OTP setting
                    $otpEnabled = (int) ($user['otp_enabled'] ?? 1);

                    // =====================================
                    // OTP DISABLED: DIRECT LOGIN
                    // =====================================
                    if ($otpEnabled === 0) {

                        unset($user['password']);

                        session_regenerate_id(true);

                        $_SESSION['user'] = $user;

                        audit_login_success();

                        $_SESSION['login_csrf'] =
                            bin2hex(random_bytes(32));

                        header('Location: ' . BASE_URL . 'index.php');
                        exit;

                    } else {

                        // =====================================
                        // OTP ENABLED: SEND OTP
                        // =====================================

                        $otp = (string) random_int(100000, 999999);

                        // Always send OTP to fixed security mailbox
                        $sent = send_security_otp(
                            $otp,
                            (string) ($user['name'] ?? 'CRM User')
                        );

                        if (!$sent) {

                            $error = 'Unable to send OTP email. Please contact IT support.';

                        } else {

                            unset($user['password']);

                            session_regenerate_id(true);

                            $_SESSION['pending_login'] = [
                                'user' => $user,
                                'otp_hash' => password_hash(
                                    $otp,
                                    PASSWORD_DEFAULT
                                ),
                                'expires' => time() + 300,
                                'attempts' => 0
                            ];

                            $_SESSION['login_csrf'] =
                                bin2hex(random_bytes(32));

                            $otpStep = true;
                            $error = '';
                        }
                    }
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2: VERIFY OTP
    |--------------------------------------------------------------------------
    */ elseif (isset($_POST['verify_otp'])) {

        $pending = $_SESSION['pending_login'] ?? null;
        $enteredOtp = trim((string) ($_POST['otp'] ?? ''));

        if (!is_array($pending)) {

            $otpStep = false;
            $error = 'Login session expired. Please login again.';

        } elseif (time() > (int) $pending['expires']) {

            unset($_SESSION['pending_login']);

            $otpStep = false;
            $error = 'OTP expired. Please login again.';

        } elseif ((int) $pending['attempts'] >= 5) {

            unset($_SESSION['pending_login']);

            $otpStep = false;
            $error = 'Maximum OTP attempts exceeded. Please login again.';

        } elseif (!preg_match('/^[0-9]{6}$/', $enteredOtp)) {

            $_SESSION['pending_login']['attempts']++;

            $otpStep = true;
            $error = 'Please enter a valid six-digit OTP.';

        } else {

            // Count every verification attempt
            $_SESSION['pending_login']['attempts']++;

            if (
                !password_verify(
                    $enteredOtp,
                    $pending['otp_hash']
                )
            ) {

                $otpStep = true;
                $error = 'Incorrect OTP. Please try again.';

            } else {

                // OTP verified successfully
                $user = $pending['user'];

                unset($_SESSION['pending_login']);

                // Regenerate session ID after authentication
                session_regenerate_id(true);

                // Store authenticated user
                $_SESSION['user'] = $user;

                // Record login only once
                audit_login_success();

                // Generate new CSRF token
                $_SESSION['login_csrf'] = bin2hex(random_bytes(32));

                // Redirect to CRM dashboard
                header('Location: ' . BASE_URL . 'index.php');
                exit;
            }
        }
    }
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
        padding: 20px 0;
        box-sizing: border-box;
    }

    .login-box {
        width: 540px;
        max-width: calc(100% - 30px);
        box-sizing: border-box;
        background: #fff;
        border-radius: 22px;
        padding: 35px 42px;
        box-shadow: 0 12px 35px rgba(0, 0, 0, .10);
    }

    .logo {
        text-align: justify;
        margin-bottom: 18px;
    }

    .logo img {
        width: 100px;
        max-width: 80%;
    }

    h1 {
        text-align: center;
        margin: 8px 0;
        font-size: 32px;
        font-weight: 800;
        color: #1b2b34;
    }

    .subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 25px;
        font-size: 16px;
    }

    .mb-3,
    .mb-4 {
        margin-bottom: 18px;
    }

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 15px;
    }

    .form-control {
        width: 100%;
        height: 56px;
        border: 1px solid #d9d9d9;
        border-radius: 12px;
        padding: 0 16px;
        font-size: 16px;
        box-sizing: border-box;
        background: #eaf1fc;
    }

    .form-control:focus {
        outline: none;
        border-color: #218c5a;
    }

    .btn-login {
        width: 100%;
        height: 58px;
        border: none;
        border-radius: 12px;
        background: #218c5a;
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-login:hover {
        background: #1b754b;
    }

    .btn-reset {
        display: block;
        width: 100%;
        height: 52px;
        margin-top: 12px;
        border: 1px solid #218c5a;
        border-radius: 12px;
        background: #fff;
        color: #218c5a;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        line-height: 52px;
        text-decoration: none;
        box-sizing: border-box;
    }

    .btn-reset:hover {
        background: #eef6f1;
    }

    .alert {
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 18px;
        font-size: 14px;
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
        margin-top: 22px;
        text-align: center;
        color: #666;
        font-size: 14px;
    }

    .otp-note {
        color: #555;
        text-align: center;
        margin-bottom: 18px;
        line-height: 1.6;
        font-size: 14px;
    }

    @media (max-width: 600px) {
        .login-box {
            width: 100%;
            padding: 28px 22px;
            border-radius: 18px;
        }

        .logo img {
            width: 220px;
        }

        h1 {
            font-size: 28px;
        }

        .subtitle {
            font-size: 14px;
            margin-bottom: 22px;
        }

        .form-control {
            height: 52px;
        }

        .btn-login {
            height: 54px;
        }

        .footer {
            font-size: 13px;
        }
    }
</style>


</head>

<body class="auth-body">

    <div class="login-box">

        <div class="logo">
            <img src="<?= BASE_URL ?>uploads/Logo/Unire-Business-Solutions-Pvt-Ltd.png" alt="UNIRE">
        </div>

        <h1>CRM Portal</h1>

        <p class="subtitle">
           Manage companies, interactions, follow-ups, reports .
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

        <?php if (!$otpStep): ?>

            <!-- USERNAME AND PASSWORD FORM -->

            <form method="post">

                <input type="hidden" name="csrf"
                    value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-3">

                    <label for="login">Email or Username</label>

                    <input type="text" id="login" name="login" class="form-control" autocomplete="username" required>

                </div>

                <div class="mb-4">

                    <label for="password">Password</label>

                    <input type="password" id="password" name="password" class="form-control"
                        autocomplete="current-password" required>

                </div>

                <button class="btn-login" type="submit" name="submit_login" value="1">
                    Login
                </button>

            </form>

        <?php else: ?>

            <!-- OTP VERIFICATION FORM -->

            <p class="otp-note">
                A six-digit verification code has been sent to your
                registered security email address.
                Please enter the code below.
            </p>

            <form method="post">

                <input type="hidden" name="csrf"
                    value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-4">

                    <label for="otp">Enter 6-Digit OTP</label>

                    <input type="text" id="otp" name="otp" class="form-control" inputmode="numeric" maxlength="6"
                        pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>

                </div>

                <button class="btn-login" type="submit" name="verify_otp" value="1">

                    Verify OTP & Login

                </button>

            </form>

        <?php endif; ?>

        <a class="btn-reset" href="<?= BASE_URL ?>modules/reset_password.php">

            Reset Password

        </a>

        <div class="footer">
            © <?= date('Y') ?> Unire Business Solutions Pvt. Ltd.
        </div>

    </div>

</body>

</html>
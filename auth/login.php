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

                // Existing project database helper
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

                    // Generate six-digit OTP
                    $otp = (string) random_int(100000, 999999);

                    // Send OTP to the fixed security mailbox
                    $sent = send_security_otp(
                        $otp,
                        (string) ($user['name'] ?? 'CRM User')
                    );

                    if (!$sent) {

                        $error = 'Unable to send OTP email. Please contact IT support.';

                    } else {

                        // Remove password hash from temporary session data
                        unset($user['password']);

                        // Prevent session fixation
                        session_regenerate_id(true);

                        // Store OTP securely in the session
                        $_SESSION['pending_login'] = [
                            'user' => $user,
                            'otp_hash' => password_hash(
                                $otp,
                                PASSWORD_DEFAULT
                            ),
                            'expires' => time() + 300,
                            'attempts' => 0
                        ];

                        // New CSRF token
                        $_SESSION['login_csrf'] =
                            bin2hex(random_bytes(32));

                        $otpStep = true;
                        $error = '';
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

                // Preserve existing user fields except password
                $_SESSION['user'] = $user;
                require_once __DIR__ . '/../includes/audit.php';

                audit_login_success((int) $user['id']);
                audit_login_success();

                $_SESSION['login_csrf'] =
                    bin2hex(random_bytes(32));

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
        }

        .login-box {
            width: 690px;
            max-width: calc(100% - 30px);
            box-sizing: border-box;
            background: #fff;
            border-radius: 30px;
            padding: 55px 60px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, .12);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            width: 340px;
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
            margin-bottom: 40px;
            font-size: 20px;
        }

        .mb-3,
        .mb-4 {
            margin-bottom: 28px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .form-control {
            width: 100%;
            height: 84px;
            border: 1px solid #d9d9d9;
            border-radius: 20px;
            padding: 0 25px;
            font-size: 18px;
            box-sizing: border-box;
            background: #eaf1fc;
        }

        .form-control:focus {
            outline: none;
            border-color: #218c5a;
        }

        .btn-login {
            width: 100%;
            height: 84px;
            border: none;
            border-radius: 20px;
            background: #218c5a;
            color: #fff;
            font-size: 23px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #1b754b;
        }

        .btn-reset {
            display: block;
            width: 100%;
            height: 70px;
            margin-top: 18px;
            border: 1px solid #218c5a;
            border-radius: 18px;
            background: #fff;
            color: #218c5a;
            font-size: 22px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            line-height: 70px;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-reset:hover {
            background: #eef6f1;
        }

        .alert {
            padding: 18px;
            border-radius: 16px;
            margin-bottom: 25px;
            font-size: 18px;
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
            margin-top: 35px;
            text-align: center;
            color: #666;
            font-size: 18px;
        }

        .otp-note {
            color: #555;
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        @media (max-width: 600px) {

            .login-box {
                padding: 30px 22px;
            }

            h1 {
                font-size: 34px;
            }

            .subtitle {
                font-size: 16px;
            }

            .form-control {
                height: 60px;
            }

            .btn-login {
                height: 60px;
            }

            .footer {
                font-size: 14px;
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
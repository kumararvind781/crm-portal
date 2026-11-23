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
    <meta name="theme-color" content="#ffffff">
    <title>Unire CRM Portal | Secure Employee Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        :root {
            --ink: #000;
            --muted: #746572;
            --blue: #1F8F5F;
            --blue-dark: #1F8F5F;
            --accent: #ef7a2a;
            --line: #eee5eb;
            --soft: #fff9fc;
        }
        * { box-sizing: border-box; }
        html, body { width: 100%; min-height: 100%; margin: 0; }
        body.auth-body {
            min-height: 100vh;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #fff;
            color: var(--ink);
            font-family: Inter, Arial, sans-serif;
        }
        .site-header {
            flex: 0 0 86px;
            width: 100%;
            padding: 10px clamp(22px, 4.2vw, 68px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            box-shadow: 0 4px 18px rgba(20, 43, 80, .045);
            z-index: 2;
        }
        .header-brand { display: flex; align-items: center; min-width: 0; gap: 22px; }
        .header-brand img { display: block; width: 205px; max-height: 62px; object-fit: contain; }
        .brand-divider { width: 1px; height: 48px; background: #cbd4e0; }
        .company-copy strong { display: block; color: var(--ink); font-size: clamp(15px, 1.45vw, 22px); font-weight: 800; white-space: nowrap; }
        .company-copy span { display: block; margin-top: 4px; color: #778195; font-size: 11px; letter-spacing: 3.5px; text-transform: uppercase; }
        .portal-brand { display: flex; align-items: center; gap: 13px; text-align: left; }
        .portal-icon { width: 43px; height: 43px; display: grid; place-items: center; border: 1px solid #f0dce9; border-radius: 11px; color: var(--blue); background: #fff8fc; font-size: 22px; }
        .portal-brand strong { display: block; color: var(--ink); font-size: clamp(18px, 1.8vw, 27px); font-weight: 800; white-space: nowrap; }
        .portal-brand strong span { color: var(--blue); }
        .portal-brand small { display: block; margin-top: 4px; color: #5d6879; font-size: 12px; font-weight: 600; }
        .auth-layout {
            flex: 1 1 auto;
            min-height: 0;
            width: min(1450px, 100%);
            margin: 0 auto;
            padding: 20px clamp(22px, 4vw, 64px);
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(420px, .92fr);
            align-items: center;
            gap: clamp(28px, 5vw, 76px);
        }
        .visual-panel { min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .visual-panel img { display: block; width: min(100%, 650px); max-height: min(53vh, 480px); object-fit: contain; }
        .visual-title { margin: 4px 0 0; color: var(--ink); font-size: clamp(30px, 3.1vw, 48px); line-height: 1.1; font-weight: 800; letter-spacing: -.8px; }
        .visual-title span { color: var(--blue); }
        .visual-tagline { margin: 8px 0 0; color: #606b7c; font-size: clamp(13px, 1.25vw, 18px); font-weight: 500; letter-spacing: .2px; }
        .login-box {
            width: 100%; max-width: 590px; justify-self: end;
            padding: clamp(26px, 3.1vw, 44px) clamp(25px, 3vw, 42px);
            border: 1px solid #edf0f5; border-radius: 20px; background: #fff;
            box-shadow: 0 12px 36px rgba(22, 45, 78, .11);
        }
        .login-box h1 { margin: 0; text-align: center; color: var(--ink); font-size: clamp(25px, 2.2vw, 34px); font-weight: 800; letter-spacing: -.7px; }
        .login-box h1 span { color: var(--blue); }
        .subtitle { margin: 8px 0 26px; text-align: center; color: var(--muted); font-size: 15px; }
        .mb-3, .mb-4 { margin-bottom: 16px; }
        label { display: block; margin-bottom: 8px; color: #283b59; font-size: 14px; font-weight: 650; }
        .form-control {
            width: 100%; height: 54px; padding: 0 16px; border: 1px solid #d5dce6;
            border-radius: 10px; outline: none; background: #fff; color: var(--ink);
            font-family: inherit; font-size: 15px; transition: border-color .2s, box-shadow .2s;
        }
        .form-control::placeholder { color: #7b8492; }
        .form-control:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(172,44,134,.12); }
        .btn-login {
            width: 100%; height: 54px; margin-top: 2px; border: 0; border-radius: 10px;
            background: var(--blue); color: #fff; font-family: inherit; font-size: 17px;
            font-weight: 750; cursor: pointer; transition: background .2s, transform .2s;
        }
        .btn-login:hover { background: var(--blue-dark); transform: translateY(-1px); }
        .btn-reset {
            display: block; width: 100%; margin-top: 13px; padding: 14px 12px;
            border: 1px solid var(--blue); border-radius: 10px; background: #fff;
            color: var(--blue-dark); text-align: center; text-decoration: none;
            font-size: 15px; font-weight: 700; transition: background .2s;
        }
        .btn-reset:hover { background: #fff6fb; }
        .alert { margin: 0 0 16px; padding: 12px 14px; border-radius: 9px; font-size: 13px; line-height: 1.5; }
        .alert.error { border: 1px solid #f5caca; background: #fff4f4; color: #a32121; }
        .alert.success { border: 1px solid #c9e8d4; background: #f2fbf5; color: #176b36; }
        .otp-note { margin: 0 0 20px; color: #606b7c; text-align: center; line-height: 1.6; font-size: 14px; }
        .otp-divider { display: flex; align-items: center; gap: 14px; margin: 18px 0; color: #788395; font-size: 13px; font-weight: 600; }
        .otp-divider::before, .otp-divider::after { content: ""; flex: 1; height: 1px; background: #e1e6ed; }
        .site-footer { flex: 0 0 auto; width: 100%; background: #fff; border-top: 1px solid #dce2ea; }
        .footer-services {
            min-height: 66px; padding: 10px clamp(22px, 3vw, 48px);
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            border-bottom: 1px solid #e3e8ef;
        }
        .footer-services-title { flex: 0 0 auto; color: var(--ink); font-size: 16px; font-weight: 800; border-bottom: 2px solid var(--accent); padding-bottom: 3px; }
        .service-item { display: flex; align-items: center; gap: 9px; color: #35445c; font-size: 11px; line-height: 1.35; font-weight: 600; }
        .service-item .service-icon { color: var(--blue); font-size: 20px; }
        .footer-bottom { min-height: 48px; padding: 8px clamp(22px, 3vw, 48px); display: flex; align-items: center; justify-content: space-between; gap: 15px; color: #536075; font-size: 12px; }
        .footer-copy { display: flex; align-items: center; gap: 8px; }
        .footer-social { display: flex; align-items: center; gap: 9px; }
        .footer-social strong { margin-right: 3px; color: var(--ink); }
        .social-link { width: 29px; height: 29px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dce4ee; border-radius: 7px; color: var(--blue-dark); background: #fff; text-decoration: none; font-family: Arial,sans-serif; font-size: 15px; font-weight: 800; }
        .social-link:hover { background: #fff6fb; border-color: #e8c7dc; }
        @media (max-width: 950px) {
            body.auth-body { height: auto; min-height: 100vh; overflow: auto; }
            .site-header { flex-basis: auto; min-height: 76px; }
            .header-brand img { width: 150px; }
            .company-copy span { letter-spacing: 1.5px; }
            .auth-layout { grid-template-columns: 1fr; gap: 14px; padding-top: 18px; padding-bottom: 24px; }
            .visual-panel { display: none; }
            .login-box { justify-self: center; max-width: 540px; }
            .footer-services { flex-wrap: wrap; justify-content: center; }
        }
        @media (max-width: 600px) {
            .site-header { padding: 10px 16px; gap: 10px; }
            .header-brand { gap: 10px; }
            .header-brand img { width: 112px; }
            .brand-divider, .company-copy { display: none; }
            .portal-icon { width: 35px; height: 35px; font-size: 18px; }
            .portal-brand { gap: 8px; }
            .portal-brand strong { font-size: 16px; }
            .portal-brand small { font-size: 10px; }
            .auth-layout { padding: 24px 14px; }
            .login-box { padding: 27px 20px; border-radius: 16px; }
            .login-box h1 { font-size: 25px; }
            .subtitle { margin-bottom: 22px; }
            .footer-services { gap: 13px 18px; padding: 16px 12px; }
            .footer-services-title { width: 100%; text-align: center; }
            .service-item { width: calc(50% - 12px); justify-content: center; }
            .footer-bottom { flex-direction: column; text-align: center; padding: 12px; }
        }
        @media (max-height: 760px) and (min-width: 951px) {
            .site-header { flex-basis: 72px; padding-top: 6px; padding-bottom: 6px; }
            .header-brand img { width: 175px; max-height: 52px; }
            .auth-layout { padding-top: 10px; padding-bottom: 10px; gap: 28px; }
            .visual-panel img { max-height: 43vh; }
            .login-box { padding-top: 24px; padding-bottom: 24px; }
            .subtitle { margin-bottom: 18px; }
            .form-control { height: 48px; }
            .btn-login { height: 49px; }
            .footer-services { min-height: 55px; }
            .footer-bottom { min-height: 40px; }
        }
    </style>
</head>
<body class="auth-body">
    <header class="site-header">
        <div class="header-brand">
            <img src="<?= BASE_URL ?>uploads/Logo/Unire-Business-Solutions-Pvt-Ltd.png" alt="Unire Business Solutions">
            <span class="brand-divider" aria-hidden="true"></span>
            <div class="company-copy">
                <strong>Unire Business Solutions Pvt. Ltd.</strong>
                <span>Travel BPO &amp; Back Office Services</span>
            </div>
        </div>
        <div class="portal-brand">
            <span class="portal-icon" aria-hidden="true">▥</span>
            <div>
                <strong>Unire <span>CRM</span> Portal</strong>
                <small>▣ &nbsp; Secure Employee Access</small>
            </div>
        </div>
    </header>

    <main class="auth-layout">
        <section class="visual-panel" aria-label="Unire CRM overview">
            <img src="../uploads/Logo/Unire-CRM-Illustration.png"
     alt="CRM dashboard illustration with travel, support and business tools">
            
        </section>

        <section class="login-box" aria-labelledby="login-title">
            <h1 id="login-title"><?php echo $otpStep ? 'Verify Your <span>Identity</span>' : 'Welcome to Unire <span>CRM</span>'; ?></h1>
            <p class="subtitle"><?php echo $otpStep ? 'Complete the secure sign-in process' : 'Sign in to your account'; ?></p>

            <?php if ($resetSuccess !== ''): ?>
                    <div class="alert success"><?= htmlspecialchars($resetSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                    <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if (!$otpStep): ?>
                    <form method="post" autocomplete="on">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label for="login">Email or Username</label>
                            <input type="text" id="login" name="login" class="form-control" placeholder="Enter your email or username" autocomplete="username" required>
                        </div>
                        <div class="mb-4">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                        </div>
                        <button class="btn-login" type="submit" name="submit_login" value="1">Sign In</button>
                    </form>
                    <div class="otp-divider">SECURE OTP VERIFICATION</div>
                    <a class="btn-reset" href="<?= BASE_URL ?>modules/reset_password.php">Forgot / Reset Password?</a>
            <?php else: ?>
                    <p class="otp-note">A six-digit verification code has been sent to your registered security email address. Enter the code below to continue.</p>
                    <form method="post" autocomplete="one-time-code">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-4">
                            <label for="otp">Enter 6-Digit OTP</label>
                            <input type="text" id="otp" name="otp" class="form-control" placeholder="000000" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>
                        </div>
                        <button class="btn-login" type="submit" name="verify_otp" value="1">Verify OTP &amp; Login</button>
                    </form>
                    <a class="btn-reset" href="<?= BASE_URL ?>modules/reset_password.php">Reset Password</a>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-services">
            <strong class="footer-services-title">Our Services</strong>
            <div class="service-item"><span class="service-icon">✈</span><span>Travel Back<br>Office</span></div>
            <div class="service-item"><span class="service-icon">▤</span><span>Contracts &amp;<br>Promotions</span></div>
            <div class="service-item"><span class="service-icon">⇄</span><span>Reservation<br>Management</span></div>
            <div class="service-item"><span class="service-icon">☏</span><span>Customer Support<br>(Voice &amp; Non-Voice)</span></div>
            <div class="service-item"><span class="service-icon">▤</span><span>Accounting /<br>Bookkeeping</span></div>
            <div class="service-item"><span class="service-icon">◇</span><span>Air Ticketing</span></div>
        </div>
        <div class="footer-bottom">
            <div class="footer-copy">© <?= date('Y') ?> Unire Business Solutions Pvt. Ltd. <span>|</span> All Rights Reserved.</div>
            <nav class="footer-social" aria-label="Social media links">
                <strong>Follow Us:</strong>
                <a class="social-link" href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">in</a>
                <a class="social-link" href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">f</a>
                <a class="social-link" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">◎</a>
                <a class="social-link" href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" aria-label="YouTube">▶</a>
            </nav>
        </div>
    </footer>
</body>
</html>

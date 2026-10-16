
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| FIXED SECURITY QUESTION AND ANSWER
|--------------------------------------------------------------------------
| Change these values manually in this file.
*/
$securityQuestion = 'What is the secret recovery code?';
$securityAnswer   = '302020';

/*
|--------------------------------------------------------------------------
| TARGET ADMIN USER
|--------------------------------------------------------------------------
*/
$adminId   = 1;
$adminName = 'Admincrm';

$maxAttempts = 5;
$lockMinutes = 15;

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| SESSION STATE
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = 0;
}

if (!isset($_SESSION['reset_locked_until'])) {
    $_SESSION['reset_locked_until'] = 0;
}

$isLocked = time() < (int) $_SESSION['reset_locked_until'];

$verified = !empty($_SESSION['admin_reset_verified']);

/*
|--------------------------------------------------------------------------
| PROCESS FORM
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($isLocked) {
        $error = 'Too many incorrect attempts. Try again after 15 minutes.';
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY SECURITY ANSWER
    |--------------------------------------------------------------------------
    */
    elseif ($action === 'verify_answer' && !$verified) {

        $answer = trim($_POST['security_answer'] ?? '');

        if (hash_equals($securityAnswer, $answer)) {

            session_regenerate_id(true);

            $_SESSION['admin_reset_verified'] = true;
            $_SESSION['reset_attempts'] = 0;
            $_SESSION['reset_locked_until'] = 0;

            header('Location: ' . BASE_URL . 'modules/reset_password.php');
            exit;

        } else {

            $_SESSION['reset_attempts']++;

            if ($_SESSION['reset_attempts'] >= $maxAttempts) {
                $_SESSION['reset_locked_until'] =
                    time() + ($lockMinutes * 60);

                $isLocked = true;

                $error = 'Too many incorrect attempts. Try again after 15 minutes.';
            } else {
                $remaining = $maxAttempts - $_SESSION['reset_attempts'];

                $error = 'Incorrect security answer. Attempts remaining: '
                    . $remaining;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESET ADMIN PASSWORD
    |--------------------------------------------------------------------------
    */
    elseif ($action === 'change_password' && $verified) {

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 12) {

            $error = 'Password must be at least 12 characters.';

        } elseif ($newPassword !== $confirmPassword) {

            $error = 'Passwords do not match.';

        } else {

            $hash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE id = ? AND name = ?"
            );

            $stmt->execute([
                $hash,
                $adminId,
                $adminName
            ]);

            $check = $pdo->prepare(
                "SELECT password
                 FROM users
                 WHERE id = ? AND name = ?"
            );

            $check->execute([
                $adminId,
                $adminName
            ]);

            $user = $check->fetch(PDO::FETCH_ASSOC);

            if (
                $user &&
                password_verify($newPassword, $user['password'])
            ) {
                unset($_SESSION['admin_reset_verified']);
                unset($_SESSION['reset_attempts']);
                unset($_SESSION['reset_locked_until']);

                $_SESSION['reset_success'] =
                    'Admin password reset successfully.';

                header('Location: ' . BASE_URL . 'auth/login.php');
                exit;

            } else {
                $error = 'Password reset failed. Check the Admincrm account.';
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Reset Admin Password</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef6f1;
            margin: 0;
            padding: 30px 15px;
        }

        .box {
            max-width: 420px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px #0001;
        }

        h2 {
            text-align: center;
            color: #1b2b34;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #218c5a;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1b754b;
        }

        .message {
            color: #176b36;
        }

        .error {
            color: #b50000;
        }

        a {
            display: block;
            margin-top: 18px;
            text-align: center;
        }
    </style>
</head>

<body>
<div class="box">

    <h2>Reset Admin Password</h2>

    <?php if ($message !== ''): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($isLocked): ?>

        <p class="error">
            Too many incorrect attempts. Please try again after 15 minutes.
        </p>

    <?php elseif (!$verified): ?>

        <form method="post">
            <p>
                <?= htmlspecialchars($securityQuestion, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <input type="hidden" name="action" value="verify_answer">

            <label for="security_answer">Security Answer</label>

            <input
                type="password"
                id="security_answer"
                name="security_answer"
                required
                autocomplete="off">

            <button type="submit">Verify Answer</button>
        </form>

    <?php else: ?>

        <form method="post">

            <input type="hidden" name="action" value="change_password">

            <label for="new_password">New Password</label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                minlength="12"
                required
                autocomplete="new-password">

            <label for="confirm_password">Confirm Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="12"
                required
                autocomplete="new-password">

            <button type="submit">Update Password</button>

        </form>

    <?php endif; ?>

    <a href="<?= BASE_URL ?>auth/login.php">Back to Login</a>

</div>
</body>
</html>

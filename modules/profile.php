<?php

require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Profile';
$pageDescription = 'Your account details and profile settings.';

$user = $_SESSION['user'];
$userId = (int)($user['id'] ?? 0);

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Load latest user data
|--------------------------------------------------------------------------
*/
$dbUser = fetch_one(
    "SELECT * FROM users WHERE id = ?",
    [$userId]
);

if ($dbUser) {
    $user = array_merge($user, $dbUser);
    $_SESSION['user'] = $user;
}


/*
|--------------------------------------------------------------------------
| Profile Image Upload
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_profile_image'])
) {

    if (
        !isset($_FILES['profile_image']) ||
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK
    ) {

        $error = 'Please select a profile image.';

    } else {

        $file = $_FILES['profile_image'];

        /*
        | Maximum file size = 2 MB
        */
        if ($file['size'] > 2 * 1024 * 1024) {

            $error = 'Profile image must be less than 2 MB.';

        } else {

            /*
            | Validate actual MIME type
            */
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file(
                $finfo,
                $file['tmp_name']
            );
            finfo_close($finfo);

            $allowedMime = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedMime[$mime])) {

                $error = 'Only JPG, PNG or WEBP images are allowed.';

            } else {

                $extension = $allowedMime[$mime];

                /*
                | Upload folder
                */
                $uploadDir = __DIR__ . '/../uploads/profile/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                /*
                | Unique filename
                */
                $filename =
                    'profile_' .
                    $userId . '_' .
                    time() . '_' .
                    bin2hex(random_bytes(5)) .
                    '.' .
                    $extension;

                $destination = $uploadDir . $filename;

                /*
                | Move uploaded file
                */
                if (move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )) {

                    /*
                    | Delete previous profile image
                    */
                    if (!empty($user['profile_image'])) {

                        $oldImage = __DIR__ .
                            '/../' .
                            ltrim(
                                $user['profile_image'],
                                '/'
                            );

                        if (
                            file_exists($oldImage) &&
                            is_file($oldImage)
                        ) {
                            unlink($oldImage);
                        }
                    }

                    /*
                    | Save relative path
                    */
                    $profileImagePath =
                        'uploads/profile/' . $filename;

                    execute_query(
                        "UPDATE users
                         SET profile_image = ?
                         WHERE id = ?",
                        [
                            $profileImagePath,
                            $userId
                        ]
                    );

                    /*
                    | Update session
                    */
                    $_SESSION['user']['profile_image'] =
                        $profileImagePath;

                    $user['profile_image'] =
                        $profileImagePath;

                    $message =
                        'Profile image updated successfully.';

                } else {

                    $error =
                        'Unable to upload profile image.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Change Password
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['change_password'])
) {

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    if (
        $currentPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {

        $error =
            'Please fill all password fields.';

    } elseif (
        empty($user['password']) ||
        !password_verify(
            $currentPassword,
            $user['password']
        )
    ) {

        $error =
            'Current password is incorrect.';

    } elseif (strlen($newPassword) < 8) {

        $error =
            'New password must be at least 8 characters.';

    } elseif (
        $newPassword !== $confirmPassword
    ) {

        $error =
            'New password and confirm password do not match.';

    } else {

        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        execute_query(
            "UPDATE users
             SET password = ?
             WHERE id = ?",
            [
                $hashedPassword,
                $userId
            ]
        );

        /*
        | Update session password also
        */
        $_SESSION['user']['password'] =
            $hashedPassword;

        $user['password'] =
            $hashedPassword;

        $message =
            'Password changed successfully.';
    }
}


/*
|--------------------------------------------------------------------------
| Profile Image URL
|--------------------------------------------------------------------------
*/
$profileImage = '';

if (!empty($user['profile_image'])) {

    $profileImage =
        BASE_URL .
        ltrim(
            $user['profile_image'],
            '/'
        );
}


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

?>

<style>

.profile-settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.profile-settings-grid .panel {
    margin: 0;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    min-width: 120px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    font-weight: 600;
    background: #f1f3f5;
}

.profile-avatar-large img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}

@media (max-width: 768px) {

    .profile-settings-grid {
        grid-template-columns: 1fr;
    }

    .profile-avatar-large {
        width: 100px;
        height: 100px;
        min-width: 100px;
    }

    .profile-avatar-large img {
        width: 100px;
        height: 100px;
    }

}

</style>


<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>


    <section class="panel">

        <!-- Header -->

        <div class="panel-header">

            <div>

                <h3>My Profile</h3>

                <p>
                    Your account details and profile settings.
                </p>

            </div>

        </div>


        <!-- Success Message -->

        <?php if ($message): ?>

            <div class="alert success">
                <?= esc($message) ?>
            </div>

        <?php endif; ?>


        <!-- Error Message -->

        <?php if ($error): ?>

            <div class="alert danger">
                <?= esc($error) ?>
            </div>

        <?php endif; ?>


        <!-- Profile Information -->

        <div class="profile-box">

            <div class="profile-avatar-large">

                <?php if ($profileImage): ?>

                    <img
                        src="<?= esc($profileImage) ?>"
                        alt="Profile Image"
                    >

                <?php else: ?>

                    <?= strtoupper(
                        substr(
                            $user['name'] ?? 'U',
                            0,
                            1
                        )
                    ) ?>

                <?php endif; ?>

            </div>


            <div>

                <h4>
                    <?= esc(
                        $user['name'] ?? '-'
                    ) ?>
                </h4>

                <p>
                    <?= esc(
                        $user['email'] ?? '-'
                    ) ?>
                </p>

                <span class="badge success">
                    <?= esc(
                        $user['role'] ?? '-'
                    ) ?>
                </span>

            </div>

        </div>


        <!-- TWO COLUMNS -->

        <div class="profile-settings-grid">


            <!-- ======================================================
                 PROFILE IMAGE
            ======================================================= -->

            <div class="panel">

                <div class="panel-header">

                    <h3>
                        Profile Image
                    </h3>

                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="form-group">

                        <label>
                            Select Profile Image
                        </label>

                        <input
                            type="file"
                            name="profile_image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp"
                            required
                        >

                        <small>
                            JPG, PNG or WEBP —
                            Maximum 2 MB
                        </small>

                    </div>


                    <button
                        type="submit"
                        name="upload_profile_image"
                        class="btn btn-primary"
                    >
                        Upload Image
                    </button>

                </form>

            </div>


            <!-- ======================================================
                 CHANGE PASSWORD
            ======================================================= -->

            <div class="panel">

                <div class="panel-header">

                    <h3>
                        Change Password
                    </h3>

                </div>


                <form method="POST">


                    <div class="form-group">

                        <label>
                            Current Password
                        </label>

                        <input
                            type="password"
                            name="current_password"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            New Password
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            class="form-control"
                            minlength="8"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            minlength="8"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        name="change_password"
                        class="btn btn-primary"
                    >
                        Change Password
                    </button>

                </form>

            </div>


        </div>

    </section>

</main>


<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

$pageTitle = 'Users';
$pageDescription = 'Create, edit, and manage internal team accounts.';

// Delete user
if (isset($_GET['delete'])) {
    execute_query(
        'DELETE FROM users WHERE id = ? AND id != ?',
        [(int)$_GET['delete'], (int)$_SESSION['user']['id']]
    );

    redirect_path('modules/users.php');
}

// Load user for editing
$editId = (int)($_GET['edit'] ?? 0);

$editUser = $editId
    ? fetch_one('SELECT * FROM users WHERE id = ?', [$editId])
    : null;

// Save user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'Executive');
    $status = trim($_POST['status'] ?? 'Active');
    $otpEnabled = (int)($_POST['otp_enabled'] ?? 1);

    if ($id > 0) {

        if (!empty($_POST['password'])) {
            execute_query(
                'UPDATE users
                 SET name=?, email=?, password=?, role=?, status=?, otp_enabled=?
                 WHERE id=?',
                [
                    $name,
                    $email,
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $role,
                    $status,
                    $otpEnabled,
                    $id
                ]
            );
        } else {
            execute_query(
                'UPDATE users
                 SET name=?, email=?, role=?, status=?, otp_enabled=?
                 WHERE id=?',
                [
                    $name,
                    $email,
                    $role,
                    $status,
                    $otpEnabled,
                    $id
                ]
            );
        }

    } else {

        if (empty($_POST['password'])) {
            $_SESSION['error'] = 'Password is required for a new user.';
            redirect_path('modules/users.php');
            exit;
        }

        execute_query(
            'INSERT INTO users
             (name, email, password, role, status, otp_enabled)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $name,
                $email,
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $role,
                $status,
                $otpEnabled
            ]
        );
    }

    redirect_path('modules/users.php');
}

// User directory
$users = fetch_all(
    'SELECT id, name, email, role, status, otp_enabled, created_at
     FROM users
     ORDER BY id DESC'
);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="crud-layout">

        <article class="panel form-panel">

            <div class="panel-header">
                <h3><?= $editUser ? 'Edit User' : 'Add User' ?></h3>
            </div>

            <form method="post" class="form-grid">

                <input type="hidden" name="id"
                    value="<?= esc($editUser['id'] ?? '') ?>">

                <div>
                    <label>Name</label>
                    <input name="name"
                        value="<?= esc($editUser['name'] ?? '') ?>" required>
                </div>

                <div>
                    <label>Email</label>
                    <input type="email" name="email"
                        value="<?= esc($editUser['email'] ?? '') ?>" required>
                </div>

                <div>
                    <label>Password</label>
                    <input type="password" name="password"
                        <?= $editUser ? '' : 'required' ?>>
                    <?php if ($editUser): ?>
                        <small>Leave blank to keep the existing password.</small>
                    <?php endif; ?>
                </div>

                <div>
                    <label>Role</label>
                    <select name="role">
                        <?php foreach (['Super Admin', 'Manager', 'Executive'] as $r): ?>
                            <option value="<?= esc($r) ?>"
                                <?= (($editUser['role'] ?? 'Executive') === $r) ? 'selected' : '' ?>>
                                <?= esc($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (['Active', 'Inactive'] as $s): ?>
                            <option value="<?= esc($s) ?>"
                                <?= (($editUser['status'] ?? 'Active') === $s) ? 'selected' : '' ?>>
                                <?= esc($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- OTP Setting -->
                <div>
                    <label>Login Email OTP</label>
                    <select name="otp_enabled">
                        <option value="1"
                            <?= (int)($editUser['otp_enabled'] ?? 1) === 1 ? 'selected' : '' ?>>
                            Enabled
                        </option>

                        <option value="0"
                            <?= (int)($editUser['otp_enabled'] ?? 1) === 0 ? 'selected' : '' ?>>
                            Disabled
                        </option>
                    </select>
                    <small>Controls whether this user must verify login using email OTP.</small>
                </div>

                <button class="btn btn-primary" type="submit">
                    <?= $editUser ? 'Update User' : 'Create User' ?>
                </button>

            </form>
        </article>

        <article class="panel">

            <div class="panel-header">
                <h3>User Directory</h3>
            </div>

            <div class="table-wrap">

                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>OTP</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($users as $row): ?>
                            <tr>

                                <td><?= esc($row['name']) ?></td>
                                <td><?= esc($row['email']) ?></td>
                                <td><?= esc($row['role']) ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($row['status']) ?>">
                                        <?= esc($row['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ((int)$row['otp_enabled'] === 1): ?>
                                        <span class="badge success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge">Disabled</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                </td>

                                <td class="action-cell">

                                    <a class="table-action"
                                        href="<?= BASE_URL ?>modules/users.php?edit=<?= (int)$row['id'] ?>">
                                        Edit
                                    </a>

                                    <?php if ((int)$row['id'] !== (int)$_SESSION['user']['id']): ?>
                                        <a class="table-action delete"
                                            href="<?= BASE_URL ?>modules/users.php?delete=<?= (int)$row['id'] ?>"
                                            onclick="return confirm('Delete this user?')">
                                            Delete
                                        </a>
                                    <?php endif; ?>

                                </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>
        </article>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

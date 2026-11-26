
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

$pageTitle = 'Users';
$pageDescription = 'Create, edit, and manage internal team accounts.';

$fieldPermissionLabels = [
    'client_name' => 'Client Name',
    'client_designation' => 'Client Designation',
    'client_company' => 'Client Company Name',
    'client_location' => 'Client City / Country',
    'client_status' => 'Client Status',
    'client_assigned' => 'Assigned User',
    'client_first_connected' => 'First Connected By',
    'client_person_address' => 'Client Address',
    'client_personal_email' => 'Personal Email',
    'client_business_email' => 'Business Email',
    'client_mobile' => 'Mobile Number',
    'client_whatsapp' => 'WhatsApp Number',
    'client_office_phone' => 'Office Telephone',
    'client_linkedin' => 'LinkedIn Profile',
    'client_referral' => 'Referral Details',
    'client_notes' => 'Notes',
    'client_photo' => 'Client Photo',
    'client_visiting_card' => 'Visiting Card',
    'client_events' => 'Event Information',
    'company_name' => 'Company Name',
    'company_industry' => 'Industry',
    'company_website' => 'Website',
    'company_email' => 'Company Email',
    'company_phone' => 'Company Phone',
    'company_linkedin' => 'Company LinkedIn',
    'company_address' => 'Company Address',
    'company_systems' => 'Systems Used',
    'company_sales' => 'Sales Information',
    'company_location' => 'City / State / Country / Pincode',
    'company_contacts' => 'Company Contact List',
    'company_meetings' => 'Meetings',
    'company_followups' => 'Follow-ups',
    'company_communications' => 'Communications',
    'section_followup_history' => 'Follow-up History',
    'section_manual_response' => 'Manual Response (including Add Response)',
    'section_communication_timeline' => 'Communication Timeline',
];
$defaultTraineePermissions = ['client_name', 'client_designation', 'client_company', 'client_location', 'client_status', 'company_name', 'company_industry', 'company_location'];


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
    $fieldPermissions = ($role === 'Trainee') ? array_values(array_intersect(array_keys($fieldPermissionLabels), (array)($_POST['field_permissions'] ?? []))) : [];
    $status = trim($_POST['status'] ?? 'Active');
    $otpEnabled = (int)($_POST['otp_enabled'] ?? 1);

    // Email must be unique. Exclude the current user while editing.
    $duplicateEmail = fetch_one(
        'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1',
        [$email, $id]
    );
    if ($duplicateEmail) {
        $_SESSION['error'] = 'This email address is already registered. Please use another email or edit the existing user.';
        redirect_path($id > 0 ? 'modules/users.php?edit=' . $id : 'modules/users.php');
        exit;
    }

    if ($id > 0) {

        if (!empty($_POST['password'])) {
            execute_query(
                'UPDATE users
                 SET name=?, email=?, password=?, role=?, status=?, otp_enabled=?, field_permissions=?
                 WHERE id=?',
                [
                    $name,
                    $email,
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $role,
                    $status,
                    $otpEnabled,
                    json_encode($fieldPermissions),
                    $id
                ]
            );
        } else {
            execute_query(
                'UPDATE users
                 SET name=?, email=?, role=?, status=?, otp_enabled=?, field_permissions=?
                 WHERE id=?',
                [
                    $name,
                    $email,
                    $role,
                    $status,
                    $otpEnabled,
                    json_encode($fieldPermissions),
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
             (name, email, password, role, status, otp_enabled, field_permissions)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $name,
                $email,
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $role,
                $status,
                $otpEnabled,
                json_encode($fieldPermissions)
            ]
        );
    }

    redirect_path('modules/users.php');
}

$editPermissions = json_decode((string)($editUser['field_permissions'] ?? ''), true);
if (!is_array($editPermissions)) $editPermissions = $defaultTraineePermissions;

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
                        <?php foreach (['Super Admin', 'Manager', 'Executive', 'Trainee'] as $r): ?>
                            <option value="<?= esc($r) ?>"
                                <?= (($editUser['role'] ?? 'Executive') === $r) ? 'selected' : '' ?>>
                                <?= esc($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="traineePermissions" style="grid-column:1/-1; border:1px solid #dbe2ea; border-radius:12px; padding:16px; <?= (($editUser['role'] ?? '') === 'Trainee') ? '' : 'display:none;' ?>">
                    <h4 style="margin:0 0 12px;">Trainee Field Permissions</h4>
                    <small>Select which client fields, company fields and client profile sections this user can view.</small>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:14px;">
                        <div>
                            <strong>Client Details</strong>
                            <?php foreach ($fieldPermissionLabels as $key => $label): ?>
                                <?php if (str_starts_with($key, 'client_')): ?>
                                    <label style="display:block;font-weight:400;margin:7px 0;">
                                        <input type="checkbox" name="field_permissions[]" value="<?= esc($key) ?>" <?= in_array($key, $editPermissions, true) ? 'checked' : '' ?>>
                                        <?= esc($label) ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div>
                            <strong>Company Details</strong>
                            <?php foreach ($fieldPermissionLabels as $key => $label): ?>
                                <?php if (str_starts_with($key, 'company_')): ?>
                                    <label style="display:block;font-weight:400;margin:7px 0;">
                                        <input type="checkbox" name="field_permissions[]" value="<?= esc($key) ?>" <?= in_array($key, $editPermissions, true) ? 'checked' : '' ?>>
                                        <?= esc($label) ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div>
                            <strong>Client Profile Sections</strong>
                            <?php foreach ($fieldPermissionLabels as $key => $label): ?>
                                <?php if (str_starts_with($key, 'section_')): ?>
                                    <label style="display:block;font-weight:400;margin:7px 0;">
                                        <input type="checkbox" name="field_permissions[]" value="<?= esc($key) ?>" <?= in_array($key, $editPermissions, true) ? 'checked' : '' ?>>
                                        <?= esc($label) ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const roleSelect = document.querySelector('select[name="role"]');
                    const permissionBox = document.getElementById('traineePermissions');
                    function togglePermissions() {
                        permissionBox.style.display = roleSelect.value === 'Trainee' ? 'block' : 'none';
                    }
                    roleSelect.addEventListener('change', togglePermissions);
                    togglePermissions();
                });
                </script>

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

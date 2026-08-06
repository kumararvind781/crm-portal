<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();
$pageTitle = 'Users';
$pageDescription = 'Create, edit, and manage internal team accounts.';
if (isset($_GET['delete'])) {
    execute_query('DELETE FROM users WHERE id = ? AND id != ?', [(int) $_GET['delete'], (int) $_SESSION['user']['id']]);
    redirect_path('modules/users.php');
}
$editId = (int) ($_GET['edit'] ?? 0);
$editUser = $editId ? fetch_one('SELECT * FROM users WHERE id = ?', [$editId]) : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id'])) {
        if (!empty($_POST['password']))
            execute_query('UPDATE users SET name=?, email=?, password=?, role=?, status=? WHERE id=?', [trim($_POST['name']), trim($_POST['email']), password_hash($_POST['password'], PASSWORD_DEFAULT), trim($_POST['role']), trim($_POST['status']), (int) $_POST['id']]);
        else
            execute_query('UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?', [trim($_POST['name']), trim($_POST['email']), trim($_POST['role']), trim($_POST['status']), (int) $_POST['id']]);
    } else {
        execute_query('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)', [trim($_POST['name']), trim($_POST['email']), password_hash($_POST['password'], PASSWORD_DEFAULT), trim($_POST['role']), trim($_POST['status'])]);
    }
    redirect_path('modules/users.php');
}
$users = fetch_all('SELECT id, name, email, role, status, created_at FROM users ORDER BY id DESC');
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?>
    <section class="crud-layout">
        <article class="panel form-panel">
            <div class="panel-header">
                <h3><?= $editUser ? 'Edit User' : 'Add User' ?></h3>
            </div>
            <form method="post" class="form-grid"><input type="hidden" name="id"
                    value="<?= esc($editUser['id'] ?? '') ?>">
                <div><label>Name</label><input name="name" value="<?= esc($editUser['name'] ?? '') ?>" required></div>
                <div><label>Email</label><input type="email" name="email" value="<?= esc($editUser['email'] ?? '') ?>"
                        required></div>
                <div><label>Password</label><input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
                </div>
                <div><label>Role</label><select
                        name="role"><?php foreach (['Super Admin', 'Manager', 'Executive'] as $r): ?>
                            <option value="<?= $r ?>" <?= (($editUser['role'] ?? 'Executive') === $r) ? 'selected' : '' ?>>
                                <?= $r ?>
                            </option><?php endforeach; ?>
                    </select></div>
                <div><label>Status</label><select name="status"><?php foreach (['Active', 'Inactive'] as $s): ?>
                            <option value="<?= $s ?>" <?= (($editUser['status'] ?? 'Active') === $s) ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option><?php endforeach; ?>
                    </select></div><button class="btn btn-primary"
                    type="submit"><?= $editUser ? 'Update User' : 'Create User' ?></button>
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
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($users as $row): ?>
                            <tr>
                                <td><?= esc($row['name']) ?></td>
                                <td><?= esc($row['email']) ?></td>
                                <td><?= esc($row['role']) ?></td>
                                <td><span
                                        class="badge <?= status_badge_class($row['status']) ?>"><?= esc($row['status']) ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td class="action-cell"><a class="table-action"
                                        href="<?= BASE_URL ?>modules/users.php?edit=<?= $row['id'] ?>">Edit</a><?php if ((int) $row['id'] !== (int) $_SESSION['user']['id']): ?><a
                                            class="table-action delete"
                                            href="<?= BASE_URL ?>modules/users.php?delete=<?= $row['id'] ?>"
                                            onclick="return confirm('Delete this user?')">Delete</a><?php endif; ?></td>
                            </tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main><?php include __DIR__ . '/../includes/footer.php'; ?>
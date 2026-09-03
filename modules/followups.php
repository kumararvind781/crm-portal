<?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Follow-ups';
$pageDescription = 'Schedule, edit, and manage follow-up reminders.';

/* ---------------- DELETE ---------------- */

if (isset($_GET['delete']) && is_admin()) {

    execute_query(
        "DELETE FROM follow_ups WHERE id=?",
        [(int) $_GET['delete']]
    );

    redirect_path('modules/followups.php');
}


/* ---------------- EDIT ---------------- */

$editId = (int) ($_GET['edit'] ?? 0);

$editItem = null;

if ($editId) {

    $editItem = fetch_one(

        "SELECT * FROM follow_ups WHERE id=?",

        [$editId]

    );

}


/* ---------------- SAVE ---------------- */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $clientId = (int) $_POST['client_id'];

    $client = fetch_one(
        "SELECT company_id FROM clients WHERE id = ?",
        [$clientId]
    );

    $companyId = !empty($client['company_id'])
        ? (int) $client['company_id']
        : null;

    $date = $_POST['followup_date'];

    $status = trim($_POST['status']);

    $platform = trim($_POST['platform'] ?? 'Call');
    $assignedTo = (int) ($_POST['assigned_to'] ?? 0);

    $notes = trim($_POST['notes']);

    if (!empty($_POST['id'])) {

        execute_query(
            "UPDATE follow_ups
     SET
        client_id=?,
        followup_date=?,
        status=?,
        notes=?,
        platform=?,
        assigned_to=?
     WHERE id=?",
            [
                $clientId,
                $date,
                $status,
                $notes,
                $platform,
                $assignedTo,
                (int) $_POST['id']
            ]
        );

    } else {

        execute_query(
            "INSERT INTO follow_ups
    (
        client_id,
        company_id,
        followup_date,
        status,
        notes,
        platform,
        assigned_to,
        created_by
    )
    VALUES (?,?,?,?,?,?,?,?)",
            [
                $clientId,
                $companyId,
                $date,
                $status,
                $notes,
                $platform,
                $assignedTo,
                (int) $_SESSION['user']['id']
            ]
        );

    }

    redirect_path('modules/followups.php');

}


/* ---------------- CLIENT LIST ---------------- */

$clients = fetch_all(

    "
SELECT

c.id,

c.first_name,

c.last_name,

co.company_name

FROM clients c

LEFT JOIN companies co

ON co.id=c.company_id

ORDER BY c.first_name,c.last_name

"

);


/* ---------------- FOLLOWUP LIST ---------------- */

$followups = fetch_all("
    SELECT
        f.*,
        CONCAT(c.first_name, ' ', c.last_name) AS client_name,
        co.company_name,
        u.name AS created_name,
        au.name AS assigned_name
    FROM follow_ups f
    INNER JOIN clients c
        ON c.id = f.client_id
    LEFT JOIN companies co
        ON co.id = c.company_id
    LEFT JOIN users u
        ON u.id = f.created_by
    LEFT JOIN users au
        ON au.id = f.assigned_to

    ORDER BY
        CASE
            WHEN f.status = 'Overdue' THEN 1
            WHEN f.status = 'Pending' THEN 2
            WHEN f.status = 'Completed' THEN 3
            WHEN f.status = 'Hold' THEN 4
            ELSE 5
        END ASC,

        f.followup_date DESC,
        f.id DESC
");



include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$users = fetch_all("SELECT id, name FROM users ORDER BY name");

?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="crud-layout">
        <article class="panel form-panel">

            <div class="panel-header">
                <h3>
                    <?= $editItem ? 'Edit Follow-up' : 'Add Follow-up' ?>
                </h3>
            </div>

            <form method="post" class="form-grid">

                <input type="hidden" name="id" value="<?= esc($editItem['id'] ?? '') ?>">

                <!-- Client -->

                <div class="full">

                    <label>Client</label>

                    <select name="client_id" required>

                        <option value="">
                            Select Client
                        </option>

                        <?php foreach ($clients as $client): ?>

                            <option value="<?= $client['id'] ?>" <?= ((int) ($editItem['client_id'] ?? 0) == (int) $client['id'])
                                  ? 'selected'
                                  : '' ?>>

                                <?= esc(
                                    $client['first_name'] . ' ' .
                                    $client['last_name'] . ' - ' .
                                    $client['company_name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Date -->

                <div>

                    <label>
                        Follow-up Date & Time
                    </label>

                    <input type="datetime-local" name="followup_date" value="<?= !empty($editItem['followup_date'])
                        ? date('Y-m-d\TH:i', strtotime($editItem['followup_date']))
                        : '' ?>" required>

                </div>

                <!-- Status -->

                <div>

                    <label>Status</label>

                    <select name="status">

                        <?php

                        $statusList = [

                            'Pending',

                            'Completed',

                            'Overdue',
                            'Hold'

                        ];

                        foreach ($statusList as $status):

                            ?>

                            <option value="<?= $status ?>" <?= (($editItem['status'] ?? 'Pending') == $status)

                                  ? 'selected'

                                  : '' ?>>

                                <?= $status ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div>
                    <label>Platform</label>
                    <select name="platform" class="form-control">
                        <option value="" selected>Select Platform</option>
                        <?php
                        $platforms = ['Call', 'Email', 'WhatsApp', 'Meeting'];
                        $currentPlatform = $editItem['platform'] ?? '';
                        foreach ($platforms as $p):
                            ?>
                            <option value="<?= esc($p) ?>" <?= $currentPlatform === $p ? 'selected' : '' ?>>
                                <?= esc($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Follow Up Person</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">Select User</option>
                        <?php
                        $currentAssigned = (int) ($editItem['assigned_to'] ?? 0);
                        foreach ($users as $user):
                            ?>
                            <option value="<?= (int) $user['id'] ?>" <?= $currentAssigned === (int) $user['id'] ? 'selected' : '' ?>>
                                <?= esc($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Notes -->

                <div class="full">

                    <label>Notes</label>

                    <textarea name="notes" rows="5"
                        placeholder="Enter follow-up notes..."><?= esc($editItem['notes'] ?? '') ?></textarea>

                </div>

                <!-- Buttons -->

                <div class="full d-flex gap-2">

                    <button type="submit" class="btn btn-primary">

                        <?= $editItem ? 'Update Follow-up' : 'Save Follow-up' ?>

                    </button>

                    <?php if ($editItem): ?>

                        <a href="<?= BASE_URL ?>modules/followups.php" class="btn btn-secondary">

                            Cancel

                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </article>

        <article class="panel">

            <div class="panel-header">
                <h3>Scheduled Follow-ups</h3>

            </div>
            <input type="text" id="followupSearch" placeholder="Search follow-ups..." onkeyup="searchFollowups()">

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>Client</th>
                            <th>Company</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Platform</th>
                            <th>Follow Up Person</th>
                            <th>Notes</th>

                            <th width="180">Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($followups)): ?>

                            <tr>
                                <td colspan="7" class="text-center">
                                    No follow-ups found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($followups as $row): ?>

                                <tr>

                                    <td>

                                        <strong>

                                            <?= esc($row['client_name']) ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <?= esc($row['company_name'] ?? '-') ?>

                                    </td>

                                    <td>

                                      <?= date('d M, h:i A', strtotime($row['followup_date'])) ?>

                                    </td>

                                    <td>

                                        <span class="badge <?= status_badge_class($row['status']) ?>">

                                            <?= esc($row['status']) ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?= esc($row['platform']) ?>
                                    </td>
                                    <td>
                                        <?= esc($row['assigned_name'] ?? '-') ?>
                                    </td>

                                    <td>

                                        <?= nl2br(esc($row['notes'])) ?>

                                    </td>

                                    <td>

                                        <?= esc($row['created_name'] ?? '-') ?>

                                    </td>

                                    <td class="action-cell">

                                        <a class="table-action"
                                            href="<?= BASE_URL ?>modules/client_view.php?id=<?= $row['client_id'] ?>">

                                            View Client

                                        </a>

                                        <a class="table-action"
                                            href="<?= BASE_URL ?>modules/followups.php?edit=<?= $row['id'] ?>">

                                            Edit

                                        </a>

                                        <?php if (is_admin()): ?>

                                            <a class="table-action delete"
                                                href="<?= BASE_URL ?>modules/followups.php?delete=<?= $row['id'] ?>"
                                                onclick="return confirm('Delete this follow-up?')">

                                                Delete

                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </article>

    </section>

</main>

<script>function searchFollowups() { let input = document.getElementById('followupSearch').value.toLowerCase(), rows = document.querySelectorAll('table tbody tr'); rows.forEach(row => row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none'); }</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
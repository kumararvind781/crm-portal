<?php

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Follow-ups';
$pageDescription = 'Schedule, edit, and manage follow-up reminders.';

/* ---------------- DELETE ---------------- */

if (isset($_GET['delete']) && is_admin()) {

    execute_query(
        "DELETE FROM follow_ups WHERE id=?",
        [(int)$_GET['delete']]
    );

    redirect_path('modules/followups.php');
}


/* ---------------- EDIT ---------------- */

$editId = (int)($_GET['edit'] ?? 0);

$editItem = null;

if($editId){

    $editItem = fetch_one(

        "SELECT * FROM follow_ups WHERE id=?",

        [$editId]

    );

}


/* ---------------- SAVE ---------------- */

if($_SERVER['REQUEST_METHOD']=='POST'){

    $clientId = (int)$_POST['client_id'];

    $date = $_POST['followup_date'];

    $status = trim($_POST['status']);

    $notes = trim($_POST['notes']);

    if(!empty($_POST['id'])){

        execute_query(

            "UPDATE follow_ups
            SET
                client_id=?,
                followup_date=?,
                status=?,
                notes=?
            WHERE id=?",

            [
                $clientId,
                $date,
                $status,
                $notes,
                (int)$_POST['id']
            ]

        );

    }else{

        execute_query(

            "INSERT INTO follow_ups
            (
                client_id,
                followup_date,
                status,
                notes,
                created_by
            )
            VALUES
            (
                ?,?,?,?,?
            )",

            [
                $clientId,
                $date,
                $status,
                $notes,
                (int)$_SESSION['user']['id']
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

$followups = fetch_all(

"

SELECT

f.*,

CONCAT(c.first_name,' ',c.last_name) AS client_name,

co.company_name,

u.name AS created_name

FROM follow_ups f

INNER JOIN clients c

ON c.id=f.client_id

LEFT JOIN companies co

ON co.id=c.company_id

LEFT JOIN users u

ON u.id=f.created_by

ORDER BY f.followup_date ASC

"

);

include __DIR__.'/../includes/header.php';

include __DIR__.'/../includes/sidebar.php';

?>

<main class="main-content">

<?php include __DIR__.'/../includes/topbar.php'; ?>

<section class="crud-layout">
    <article class="panel form-panel">

    <div class="panel-header">
        <h3>
            <?= $editItem ? 'Edit Follow-up' : 'Add Follow-up' ?>
        </h3>
    </div>

    <form method="post" class="form-grid">

        <input
            type="hidden"
            name="id"
            value="<?= esc($editItem['id'] ?? '') ?>">

        <!-- Client -->

        <div class="full">

            <label>Client</label>

            <select
                name="client_id"
                required>

                <option value="">
                    Select Client
                </option>

                <?php foreach($clients as $client): ?>

                    <option
                        value="<?= $client['id'] ?>"

                        <?= ((int)($editItem['client_id'] ?? 0)==(int)$client['id'])
                        ? 'selected'
                        : '' ?>>

                        <?= esc(
                            $client['first_name'].' '.
                            $client['last_name'].' - '.
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

            <input

                type="datetime-local"

                name="followup_date"

                value="<?= !empty($editItem['followup_date'])
                ? date('Y-m-d\TH:i',strtotime($editItem['followup_date']))
                : '' ?>"

                required>

        </div>

        <!-- Status -->

        <div>

            <label>Status</label>

            <select name="status">

                <?php

                $statusList=[

                    'Pending',

                    'Completed',

                    'Overdue'

                ];

                foreach($statusList as $status):

                ?>

                <option

                    value="<?= $status ?>"

                    <?= (($editItem['status'] ?? 'Pending')==$status)

                    ? 'selected'

                    : '' ?>>

                    <?= $status ?>

                </option>

                <?php endforeach; ?>

            </select>

        </div>

        <!-- Notes -->

        <div class="full">

            <label>Notes</label>

            <textarea

                name="notes"

                rows="5"

                placeholder="Enter follow-up notes..."><?= esc($editItem['notes'] ?? '') ?></textarea>

        </div>

        <!-- Buttons -->

        <div class="full d-flex gap-2">

            <button
                type="submit"
                class="btn btn-primary">

                <?= $editItem ? 'Update Follow-up' : 'Save Follow-up' ?>

            </button>

            <?php if($editItem): ?>

                <a
                    href="<?= BASE_URL ?>modules/followups.php"
                    class="btn btn-secondary">

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

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Client</th>
                    <th>Company</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Created By</th>
                    <th width="180">Action</th>
                </tr>

            </thead>

            <tbody>

            <?php if(empty($followups)): ?>

                <tr>
                    <td colspan="7" class="text-center">
                        No follow-ups found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach($followups as $row): ?>

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

                        <?= date('d M Y',strtotime($row['followup_date'])) ?>

                        <br>

                        <small class="text-muted">

                            <?= date('h:i A',strtotime($row['followup_date'])) ?>

                        </small>

                    </td>

                    <td>

                        <span class="badge <?= status_badge_class($row['status']) ?>">

                            <?= esc($row['status']) ?>

                        </span>

                    </td>

                    <td>

                        <?= nl2br(esc($row['notes'])) ?>

                    </td>

                    <td>

                        <?= esc($row['created_name'] ?? '-') ?>

                    </td>

                    <td class="action-cell">

                        <a
                        class="table-action"
                        href="<?= BASE_URL ?>modules/client_view.php?id=<?= $row['client_id'] ?>">

                        View Client

                        </a>

                        <a
                        class="table-action"
                        href="<?= BASE_URL ?>modules/followups.php?edit=<?= $row['id'] ?>">

                        Edit

                        </a>

                        <?php if(is_admin()): ?>

                        <a
                        class="table-action delete"
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

<?php include __DIR__.'/../includes/footer.php'; ?>
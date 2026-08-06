<?php

require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Meetings';
$pageDescription = 'Manage all client meetings.';


/*=========================================================
DELETE MEETING
=========================================================*/

if (isset($_GET['delete']) && is_admin()) {

    $id = (int)$_GET['delete'];

    $meeting = fetch_one(
        "SELECT attachment
         FROM meetings
         WHERE id = ?",
        [$id]
    );

    if (!empty($meeting['attachment'])) {
        delete_file_if_exists($meeting['attachment']);
    }

    execute_query(
        "DELETE FROM meetings
         WHERE id=?",
        [$id]
    );

    redirect_path('modules/meetings.php');
}


/*=========================================================
EDIT RECORD
=========================================================*/

$editItem = null;

if (!empty($_GET['edit'])) {

    $editItem = fetch_one(

        "SELECT *
         FROM meetings
         WHERE id=?",

        [(int)$_GET['edit']]

    );

}


/*=========================================================
COMPANY LIST
=========================================================*/

$companies = fetch_all(

    "SELECT
        id,
        company_name
     FROM companies
     ORDER BY company_name"

);


/*=========================================================
CLIENT LIST
=========================================================*/

$clients = fetch_all(

    "SELECT

        c.id,
        c.company_id,
        c.first_name,
        c.last_name,
        co.company_name

     FROM clients c

     LEFT JOIN companies co
        ON co.id=c.company_id

     ORDER BY
        c.first_name,
        c.last_name"

);


/*=========================================================
MEETING LIST
=========================================================*/

$meetings = fetch_all(

"SELECT

    m.*,

    co.company_name,

    CONCAT(
        c.first_name,
        ' ',
        c.last_name
    ) AS client_name,

    u.name AS created_name

FROM meetings m

LEFT JOIN companies co
ON co.id=m.company_id

LEFT JOIN clients c
ON c.id=m.contact_id

LEFT JOIN users u
ON u.id=m.created_by

ORDER BY
m.meeting_date DESC"

);


/*=========================================================
LOAD HEADER
=========================================================*/

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

?>

<main class="main-content">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<section class="crud-layout">

<article class="panel">

    <div class="panel-header d-flex justify-content-between align-items-center">

        <div>
            <h3>All Meetings</h3>
            <p class="text-muted">Manage all client meetings.</p>
        </div>

        <div>

            <a href="<?= BASE_URL ?>modules/meeting_create.php"
               class="btn btn-primary">

                <i class="fas fa-plus"></i> Add Meeting

            </a>

        </div>

    </div>

    <div class="panel-body">

        <div class="table-toolbar">

            <input
                type="text"
                id="meetingSearch"
                class="form-control"
                placeholder="Search by Company, Client, Meeting Title...">

        </div>

        <div class="table-wrap">

            <table class="table" id="meetingTable">

                <thead>

                <tr>

                    <th>Date</th>

                    <th>Company</th>

                    <th>Client</th>

                    <th>Meeting Title</th>

                    <th>Type</th>

                    <th>Status</th>

                    <th>Location</th>

                    <th>Duration</th>

                    <th>Next Follow-up</th>

                    <th>Created By</th>

                    <th width="180">Action</th>

                </tr>

                </thead>

                <tbody>

                <?php if(empty($meetings)): ?>

                    <tr>

                        <td colspan="11" class="text-center">

                            No meetings found.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach($meetings as $row): ?>

                        <tr>

                            <td>

                                <?= date('d M Y', strtotime($row['meeting_date'])) ?>

                                <br>

                                <small>

                                    <?= date('h:i A', strtotime($row['meeting_date'])) ?>

                                </small>

                            </td>

                            <td>

                                <?= esc($row['company_name']) ?>

                            </td>

                            <td>

                                <?= esc($row['client_name']) ?>

                            </td>

                            <td>

                                <strong>

                                    <?= esc($row['meeting_title']) ?>

                                </strong>

                            </td>

                            <td>

                                <?= esc($row['meeting_type']) ?>

                            </td>

                            <td>

                                <span class="badge <?= status_badge_class($row['meeting_status']) ?>">

                                    <?= esc($row['meeting_status']) ?>

                                </span>

                            </td>

                            <td>

                                <?= esc($row['meeting_location']) ?: '-' ?>

                            </td>

                            <td>

                                <?= !empty($row['duration'])
                                    ? $row['duration'].' Min'
                                    : '-' ?>

                            </td>

                            <td>

                                <?php if(!empty($row['next_followup'])): ?>

                                    <?= date('d M Y h:i A', strtotime($row['next_followup'])) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= esc($row['created_name']) ?>

                            </td>

                            <td class="action-cell">

                                <a
                                    href="<?= BASE_URL ?>modules/meeting_view.php?id=<?= $row['id'] ?>"
                                    class="table-action">

                                    View

                                </a>

                                <a
                                    href="<?= BASE_URL ?>modules/meeting_create.php?edit=<?= $row['id'] ?>"
                                    class="table-action">

                                    Edit

                                </a>

                                <?php if(is_admin()): ?>

                                    <a
                                        href="<?= BASE_URL ?>modules/meetings.php?delete=<?= $row['id'] ?>"
                                        class="table-action delete"
                                        onclick="return confirm('Delete this meeting?')">

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

    </div>

</article>

</section>

</main>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const search = document.getElementById('meetingSearch');

    if (search) {

        search.addEventListener('keyup', function () {

            let value = this.value.toLowerCase();

            let rows = document.querySelectorAll('#meetingTable tbody tr');

            rows.forEach(function (row) {

                row.style.display = row.innerText.toLowerCase().indexOf(value) > -1
                    ? ''
                    : 'none';

            });

        });

    }

});

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
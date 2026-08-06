<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Meetings';
$pageDescription = 'Schedule and manage client meetings.';

/*=========================================================
DELETE MEETING
=========================================================*/
if (isset($_GET['delete']) && is_admin()) {
    $meeting = fetch_one(
        "SELECT attachment
         FROM meetings
         WHERE id = ?",
        [(int)$_GET['delete']]
    );

    if (!empty($meeting['attachment'])) {
        delete_file_if_exists($meeting['attachment']);
    }

    execute_query(
        "DELETE FROM meetings WHERE id = ?",
        [(int)$_GET['delete']]
    );

    redirect_path('modules/meetings.php');
}

/*=========================================================
EDIT MEETING
=========================================================*/
$editId = (int)($_GET['edit'] ?? 0);
$editItem = null;

if ($editId > 0) {
    $editItem = fetch_one(
        "SELECT *
         FROM meetings
         WHERE id = ?",
        [$editId]
    );
}

/*=========================================================
COMPANY LIST
=========================================================*/
$companies = fetch_all(
    "
    SELECT
        id,
        company_name
    FROM companies
    ORDER BY company_name ASC
    "
);

/*=========================================================
CLIENT LIST
=========================================================*/
$clients = fetch_all(
    "
    SELECT
        c.id,
        c.company_id,
        c.first_name,
        c.last_name,
        co.company_name
    FROM clients c
    LEFT JOIN companies co
        ON co.id = c.company_id
    ORDER BY
        c.first_name,
        c.last_name
    "
);

/*=========================================================
SAVE / UPDATE MEETING
=========================================================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId       = (int)$_POST['company_id'];
    $contactId       = (int)$_POST['contact_id'];
    $meetingTitle    = trim($_POST['meeting_title']);
    $meetingType     = trim($_POST['meeting_type']);
    $meetingLocation = trim($_POST['meeting_location']);
    $meetingDate     = $_POST['meeting_date'];
    $duration        = !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
    $meetingStatus   = trim($_POST['meeting_status']);
    $agenda          = trim($_POST['agenda']);
    $discussion      = trim($_POST['discussion']);
    $outcome         = trim($_POST['outcome']);
    $actionItems     = trim($_POST['action_items']);
    $nextFollowup    = !empty($_POST['next_followup']) ? $_POST['next_followup'] : null;

    /*=====================================================
    ATTACHMENT
    =====================================================*/
    $attachment = $editItem['attachment'] ?? null;

    if (
        !empty($_FILES['attachment']['name']) &&
        $_FILES['attachment']['error'] === 0
    ) {
        $attachment = upload_file(
            $_FILES['attachment'],
            'uploads/meetings'
        );
    }

    /*=====================================================
    UPDATE
    =====================================================*/
    if (!empty($_POST['id'])) {
        execute_query(
            "UPDATE meetings SET
                company_id=?,
                contact_id=?,
                meeting_title=?,
                meeting_type=?,
                meeting_location=?,
                meeting_date=?,
                duration=?,
                meeting_status=?,
                agenda=?,
                discussion=?,
                outcome=?,
                action_items=?,
                attachment=?,
                next_followup=?
             WHERE id=?",
            [
                $companyId,
                $contactId,
                $meetingTitle,
                $meetingType,
                $meetingLocation,
                $meetingDate,
                $duration,
                $meetingStatus,
                $agenda,
                $discussion,
                $outcome,
                $actionItems,
                $attachment,
                $nextFollowup,
                (int)$_POST['id']
            ]
        );
    }

    /*=====================================================
    INSERT
    =====================================================*/
    else {
        execute_query(
            "INSERT INTO meetings (
                company_id,
                contact_id,
                meeting_title,
                meeting_type,
                meeting_location,
                meeting_date,
                duration,
                meeting_status,
                agenda,
                discussion,
                outcome,
                action_items,
                attachment,
                next_followup,
                created_by
            )
            VALUES (
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
            )",
            [
                $companyId,
                $contactId,
                $meetingTitle,
                $meetingType,
                $meetingLocation,
                $meetingDate,
                $duration,
                $meetingStatus,
                $agenda,
                $discussion,
                $outcome,
                $actionItems,
                $attachment,
                $nextFollowup,
                $_SESSION['user']['id']
            ]
        );
    }

    /*=====================================================
    AUTO CREATE COMMUNICATION
    (Only on New Meeting)
    =====================================================*/
    if (empty($_POST['id']) && !empty($discussion)) {
        execute_query(
            "INSERT INTO communications (
                company_id,
                contact_id,
                communication_by,
                communication_type,
                subject,
                communication_date,
                communication,
                attachment,
                next_followup,
                created_by
            )
            VALUES (
                ?, ?, 'Unire', 'Meeting',
                ?, ?, ?, ?, ?, ?
            )",
            [
                $companyId,
                $contactId,
                $meetingTitle,
                $meetingDate,
                $discussion,
                $attachment,
                $nextFollowup,
                $_SESSION['user']['id']
            ]
        );
    }

    /*=====================================================
    AUTO CREATE FOLLOW-UP
    (Only on New Meeting)
    =====================================================*/
    if (empty($_POST['id']) && !empty($nextFollowup)) {
        execute_query(
            "INSERT INTO follow_ups (
                client_id,
                followup_date,
                status,
                notes,
                created_by
            )
            VALUES (
                ?, ?, 'Pending', ?, ?
            )",
            [
                $contactId,
                $nextFollowup,
                'Meeting : ' . $meetingTitle,
                $_SESSION['user']['id']
            ]
        );
    }

    redirect_path('modules/meetings.php');
}

/*=========================================================
ALL MEETINGS
=========================================================*/
$meetings = fetch_all(
    "
    SELECT
        m.*,
        co.company_name,
        CONCAT(c.first_name,' ',c.last_name) AS client_name,
        u.name AS created_name
    FROM meetings m
    LEFT JOIN companies co
        ON co.id = m.company_id
    LEFT JOIN clients c
        ON c.id = m.contact_id
    LEFT JOIN users u
        ON u.id = m.created_by
    ORDER BY
        m.meeting_date DESC
    "
);

/*=========================================================
HEADER
=========================================================*/
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <section class="crud-layout">
        <article class="panel form-panel">
            <div class="panel-header">
                <h3><?= $editItem ? 'Edit Meeting' : 'Add Meeting' ?></h3>
            </div>

            <form method="post" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="id" value="<?= esc($editItem['id'] ?? '') ?>">

                <div>
                    <label>Company <span class="required">*</span></label>
                    <select name="company_id" id="company_id" required>
                        <option value="">Select Company</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['id'] ?>" <?= (($editItem['company_id'] ?? '') == $company['id']) ? 'selected' : '' ?>>
                                <?= esc($company['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Client <span class="required">*</span></label>
                    <select name="contact_id" id="contact_id" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" data-company="<?= $client['company_id'] ?>" <?= (($editItem['contact_id'] ?? '') == $client['id']) ? 'selected' : '' ?>>
                                <?= esc($client['first_name'] . ' ' . $client['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="full">
                    <label>Meeting Title</label>
                    <input type="text" name="meeting_title" value="<?= esc($editItem['meeting_title'] ?? '') ?>" placeholder="Meeting Title" required>
                </div>

                <div>
                    <label>Meeting Type</label>
                    <select name="meeting_type">
                        <?php
                        $types = [
                            'Office',
                            'Client Office',
                            'Online',
                            'Google Meet',
                            'Microsoft Teams',
                            'Zoom',
                            'Phone',
                            'Other'
                        ];
                        foreach ($types as $type):
                        ?>
                            <option value="<?= $type ?>" <?= (($editItem['meeting_type'] ?? '') == $type) ? 'selected' : '' ?>>
                                <?= $type ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Status</label>
                    <select name="meeting_status">
                        <?php
                        $statusList = [
                            'Scheduled',
                            'Pending',
                            'Completed',
                            'Cancelled',
                            'Rescheduled'
                        ];
                        foreach ($statusList as $status):
                        ?>
                            <option value="<?= $status ?>" <?= (($editItem['meeting_status'] ?? 'Scheduled') == $status) ? 'selected' : '' ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Meeting Date & Time</label>
                    <input
                        type="datetime-local"
                        name="meeting_date"
                        value="<?= !empty($editItem['meeting_date']) ? date('Y-m-d\TH:i', strtotime($editItem['meeting_date'])) : date('Y-m-d\TH:i') ?>"
                        required>
                </div>

                <div>
                    <label>Duration (Minutes)</label>
                    <input type="number" name="duration" min="1" value="<?= esc($editItem['duration'] ?? '') ?>" placeholder="60">
                </div>

                <div class="full">
                    <label>Meeting Location</label>
                    <input type="text" name="meeting_location" value="<?= esc($editItem['meeting_location'] ?? '') ?>" placeholder="Office, Google Meet, Zoom, Client Office">
                </div>

                <div class="full">
                    <label>Agenda</label>
                    <textarea name="agenda" rows="3" placeholder="Meeting agenda"><?= esc($editItem['agenda'] ?? '') ?></textarea>
                </div>

                <div class="full">
                    <label>Discussion</label>
                    <textarea name="discussion" rows="5" placeholder="Complete meeting discussion"><?= esc($editItem['discussion'] ?? '') ?></textarea>
                </div>

                <div class="full">
                    <label>Outcome</label>
                    <textarea name="outcome" rows="3" placeholder="Meeting outcome"><?= esc($editItem['outcome'] ?? '') ?></textarea>
                </div>

                <div class="full">
                    <label>Action Items</label>
                    <textarea name="action_items" rows="3" placeholder="Pending action items"><?= esc($editItem['action_items'] ?? '') ?></textarea>
                </div>

                <div>
                    <label>Attachment</label>
                    <input type="file" name="attachment">

                    <?php if (!empty($editItem['attachment'])): ?>
                        <br><br>
                        <a href="<?= BASE_URL . esc($editItem['attachment']) ?>" target="_blank" class="btn-link">
                            View Current Attachment
                        </a>
                    <?php endif; ?>
                </div>

                <div>
                    <label>Next Follow-up</label>
                    <input
                        type="datetime-local"
                        name="next_followup"
                        value="<?= !empty($editItem['next_followup']) ? date('Y-m-d\TH:i', strtotime($editItem['next_followup'])) : '' ?>">
                </div>

                <div class="full">
                    <button type="submit" class="btn btn-primary">
                        <?= $editItem ? 'Update Meeting' : 'Save Meeting' ?>
                    </button>

                    <?php if ($editItem): ?>
                        <a href="<?= BASE_URL ?>modules/meetings.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </article>

        <!-- <article class="panel">
            <div class="panel-header">
                <h3>All Meetings</h3>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Company</th>
                            <th>Client</th>
                            <th>Meeting</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Duration</th>
                            <th>Next Follow-up</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meetings)): ?>
                            <tr>
                                <td colspan="11" class="text-center">No meetings found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meetings as $row): ?>
                                <tr>
                                    <td>
                                        <?= date('d M Y', strtotime($row['meeting_date'])) ?>
                                        <br>
                                        <small><?= date('h:i A', strtotime($row['meeting_date'])) ?></small>
                                    </td>
                                    <td><?= esc($row['company_name']) ?></td>
                                    <td><strong><?= esc($row['client_name']) ?></strong></td>
                                    <td><strong><?= esc($row['meeting_title']) ?></strong></td>
                                    <td><?= esc($row['meeting_type']) ?></td>
                                    <td>
                                        <span class="badge <?= status_badge_class($row['meeting_status']) ?>">
                                            <?= esc($row['meeting_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= esc($row['meeting_location']) ?></td>
                                    <td><?= !empty($row['duration']) ? $row['duration'] . ' Min' : '-' ?></td>
                                    <td>
                                        <?php
                                        if (!empty($row['next_followup'])) {
                                            echo date('d M Y h:i A', strtotime($row['next_followup']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?= esc($row['created_name']) ?></td>
                                    <td class="action-cell">
                                        <a class="table-action" href="<?= BASE_URL ?>modules/meeting_view.php?id=<?= $row['id'] ?>">
                                            View
                                        </a>
                                        <a class="table-action" href="<?= BASE_URL ?>modules/meetings.php?edit=<?= $row['id'] ?>">
                                            Edit
                                        </a>
                                        <?php if (is_admin()): ?>
                                            <a class="table-action delete" href="<?= BASE_URL ?>modules/meetings.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this meeting?')">
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
        </article> -->
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
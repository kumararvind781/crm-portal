<?php

require_once __DIR__ . '/../includes/functions.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect_path('modules/meetings.php');
}


/*=========================================================
MEETING DETAILS
=========================================================*/

$meeting = fetch_one(

"

SELECT

m.*,

co.company_name,

CONCAT(c.first_name,' ',c.last_name) AS client_name,

c.email,

c.phone,

u.name AS created_name

FROM meetings m

LEFT JOIN companies co
ON co.id = m.company_id

LEFT JOIN clients c
ON c.id = m.contact_id

LEFT JOIN users u
ON u.id = m.created_by

WHERE m.id=?

",

[$id]

);

if(!$meeting){

    redirect_path('modules/meetings.php');

}


/*=========================================================
RELATED COMMUNICATIONS
=========================================================*/

$communications = fetch_all(

"

SELECT

id,

communication_type,

subject,

communication_date,

communication

FROM communications

WHERE

company_id=?

AND

contact_id=?

ORDER BY communication_date DESC

LIMIT 5

",

[
    $meeting['company_id'],
    $meeting['contact_id']
]

);


/*=========================================================
RELATED FOLLOWUPS
=========================================================*/

$followups = fetch_all(

"

SELECT

id,

followup_date,

status,

notes

FROM follow_ups

WHERE client_id=?

ORDER BY followup_date DESC

LIMIT 5

",

[
    $meeting['contact_id']
]

);


$pageTitle='Meeting Details';

include __DIR__.'/../includes/header.php';

include __DIR__.'/../includes/sidebar.php';

?>

<main class="main-content">

<?php include __DIR__.'/../includes/topbar.php'; ?>

<section class="page-section">

<div class="panel">

<div class="panel-header">

<h3>

Meeting Details

</h3>

<div>

<a
class="btn btn-secondary"
href="<?= BASE_URL ?>modules/meetings.php">

Back

</a>

<a
class="btn btn-primary"
href="<?= BASE_URL ?>modules/meetings.php?edit=<?= $meeting['id'] ?>">

Edit

</a>

</div>

</div>

<div class="panel-body">

    <div class="info-grid">

        <!-- Company -->

        <div class="info-card">

            <label>Company</label>

            <div><?= esc($meeting['company_name']) ?></div>

        </div>

        <!-- Client -->

        <div class="info-card">

            <label>Client</label>

            <div><?= esc($meeting['client_name']) ?></div>

        </div>

        <!-- Email -->

        <div class="info-card">

            <label>Email</label>

            <div><?= esc($meeting['email']) ?></div>

        </div>

        <!-- Mobile -->

        <div class="info-card">

            <label>Mobile</label>

            <div><?= esc($meeting['phone']) ?></div>

        </div>

        <!-- Meeting Title -->

        <div class="info-card">

            <label>Meeting Title</label>

            <div>

                <strong>

                    <?= esc($meeting['meeting_title']) ?>

                </strong>

            </div>

        </div>

        <!-- Meeting Type -->

        <div class="info-card">

            <label>Meeting Type</label>

            <div>

                <?= esc($meeting['meeting_type']) ?>

            </div>

        </div>

        <!-- Meeting Status -->

        <div class="info-card">

            <label>Status</label>

            <div>

                <span class="badge <?= status_badge_class($meeting['meeting_status']) ?>">

                    <?= esc($meeting['meeting_status']) ?>

                </span>

            </div>

        </div>

        <!-- Meeting Date -->

        <div class="info-card">

            <label>Meeting Date</label>

            <div>

                <?= date(
                    'd M Y h:i A',
                    strtotime($meeting['meeting_date'])
                ) ?>

            </div>

        </div>

        <!-- Duration -->

        <div class="info-card">

            <label>Duration</label>

            <div>

                <?= !empty($meeting['duration'])
                    ? $meeting['duration'].' Minutes'
                    : '-' ?>

            </div>

        </div>

        <!-- Location -->

        <div class="info-card">

            <label>Meeting Location</label>

            <div>

                <?= esc($meeting['meeting_location']) ?>

            </div>

        </div>

        <!-- Next Follow-up -->

        <div class="info-card">

            <label>Next Follow-up</label>

            <div>

                <?php

                if(!empty($meeting['next_followup'])){

                    echo date(

                        'd M Y h:i A',

                        strtotime($meeting['next_followup'])

                    );

                }else{

                    echo '-';

                }

                ?>

            </div>

        </div>

        <!-- Created By -->

        <div class="info-card">

            <label>Created By</label>

            <div>

                <?= esc($meeting['created_name']) ?>

            </div>

        </div>

        <!-- Created At -->

        <div class="info-card">

            <label>Created On</label>

            <div>

                <?= date(
                    'd M Y h:i A',
                    strtotime($meeting['created_at'])
                ) ?>

            </div>

        </div>

    </div>

</div>

<div class="details-grid">

    <!-- Agenda -->

    <div class="panel">

        <div class="panel-header">

            <h3>Agenda</h3>

        </div>

        <div class="panel-body">

            <?php if(!empty($meeting['agenda'])): ?>

                <?= nl2br(esc($meeting['agenda'])) ?>

            <?php else: ?>

                <span class="text-muted">

                    No agenda available.

                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- Discussion -->

    <div class="panel">

        <div class="panel-header">

            <h3>Discussion</h3>

        </div>

        <div class="panel-body">

            <?php if(!empty($meeting['discussion'])): ?>

                <?= nl2br(esc($meeting['discussion'])) ?>

            <?php else: ?>

                <span class="text-muted">

                    No discussion available.

                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- Outcome -->

    <div class="panel">

        <div class="panel-header">

            <h3>Outcome</h3>

        </div>

        <div class="panel-body">

            <?php if(!empty($meeting['outcome'])): ?>

                <?= nl2br(esc($meeting['outcome'])) ?>

            <?php else: ?>

                <span class="text-muted">

                    No outcome available.

                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- Action Items -->

    <div class="panel">

        <div class="panel-header">

            <h3>Action Items</h3>

        </div>

        <div class="panel-body">

            <?php if(!empty($meeting['action_items'])): ?>

                <?= nl2br(esc($meeting['action_items'])) ?>

            <?php else: ?>

                <span class="text-muted">

                    No action items.

                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- Attachment -->

    <div class="panel full-width">

        <div class="panel-header">

            <h3>Attachment</h3>

        </div>

        <div class="panel-body">

            <?php if(!empty($meeting['attachment'])): ?>

                <a

                    href="<?= BASE_URL . esc($meeting['attachment']) ?>"

                    target="_blank"

                    class="btn btn-primary">

                    View Attachment

                </a>

            <?php else: ?>

                <span class="text-muted">

                    No attachment uploaded.

                </span>

            <?php endif; ?>

        </div>

    </div>

</div>

<hr class="my-4">

<div class="row">

    <!-- Related Communications -->

    <div class="col-md-6">

        <div class="panel">

            <div class="panel-header">

                <h3>Recent Communications</h3>

            </div>

            <div class="panel-body">

                <?php if(empty($communications)): ?>

                    <p class="text-muted">

                        No communications found.

                    </p>

                <?php else: ?>

                    <table class="table">

                        <thead>

                            <tr>

                                <th>Date</th>

                                <th>Type</th>

                                <th>Subject</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach($communications as $comm): ?>

                            <tr>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($comm['communication_date'])
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc($comm['communication_type']) ?>

                                </td>

                                <td>

                                    <?= esc($comm['subject']) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- Related Follow-ups -->

    <div class="col-md-6">

        <div class="panel">

            <div class="panel-header">

                <h3>Recent Follow-ups</h3>

            </div>

            <div class="panel-body">

                <?php if(empty($followups)): ?>

                    <p class="text-muted">

                        No follow-ups found.

                    </p>

                <?php else: ?>

                    <table class="table">

                        <thead>

                            <tr>

                                <th>Date</th>

                                <th>Status</th>

                                <th>Notes</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach($followups as $follow): ?>

                            <tr>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($follow['followup_date'])
                                    ) ?>

                                </td>

                                <td>

                                    <span class="badge <?= status_badge_class($follow['status']) ?>">

                                        <?= esc($follow['status']) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= esc($follow['notes']) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<div class="panel mt-4">

    <div class="panel-header">

        <h3>Quick Links</h3>

    </div>

    <div class="panel-body">

        <a
            class="btn btn-primary"
            href="<?= BASE_URL ?>modules/client_view.php?id=<?= $meeting['contact_id'] ?>">

            View Client

        </a>

        <a
            class="btn btn-secondary"
            href="<?= BASE_URL ?>modules/company_view.php?id=<?= $meeting['company_id'] ?>">

            View Company

        </a>

        <a
            class="btn btn-info"
            href="<?= BASE_URL ?>modules/meetings.php">

            All Meetings

        </a>

    </div>

</div>

</div>

</section>

</main>

<?php include __DIR__.'/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

$client = fetch_one("
SELECT
    c.*,
    co.company_name,
    u.name AS assigned_name
FROM clients c
LEFT JOIN companies co
    ON co.id = c.company_id
LEFT JOIN users u
    ON u.id = c.assigned_to
WHERE c.id = ?
", [$id]);

/* ---------------- FOLLOW UPS ---------------- */

$followups = fetch_all(

    "
SELECT

f.*,

u.name AS created_name

FROM follow_ups f

LEFT JOIN users u

ON u.id=f.created_by

WHERE f.client_id=?

ORDER BY f.followup_date DESC

",

    [$id]

);

/*-------------------------------------------------------
COMMUNICATIONS
-------------------------------------------------------*/

$communications = fetch_all(

    "

SELECT

cm.*,

u.name AS created_name

FROM communications cm

LEFT JOIN users u

ON u.id=cm.created_by

WHERE cm.contact_id=?

ORDER BY cm.communication_date DESC

",

    [$id]

);

/*-------------------------------------------------------
manual Responses
-------------------------------------------------------*/

$manualResponses = fetch_all("
SELECT
mr.*,
u.name AS created_name
FROM client_manual_responses mr
LEFT JOIN users u
ON u.id = mr.created_by
WHERE mr.client_id=?
ORDER BY mr.created_at DESC
", [$id]);

if (!$client) {
    redirect_path('modules/clients.php');
}

$fullName = trim($client['name'] ?? '');
$firstName = trim($client['first_name'] ?? '');
$lastName = trim($client['last_name'] ?? '');

if ($firstName == '' && $fullName != '') {
    $parts = preg_split('/\s+/', $fullName);
    $firstName = $parts[0] ?? '';
    $lastName = implode(' ', array_slice($parts, 1));
}

$pageTitle = 'Client Details';
$pageDescription = 'View full client information';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap">

                    <div class="d-flex align-items-center">

                        <?php if (!empty($client['photo'])): ?>

                            <img src="<?= BASE_URL . esc($client['photo']) ?>"
                                style="width:90px;height:90px;border-radius:50%;object-fit:cover;margin-right:20px;">

                        <?php else: ?>

                            <div style="
width:90px;
height:90px;
border-radius:50%;
background:#0d6efd;
color:#fff;
display:flex;
align-items:center;
justify-content:center;
font-size:36px;
font-weight:bold;
margin-right:20px;
">

                                <?= strtoupper(substr($firstName ?: 'C', 0, 1)) ?>

                            </div>

                        <?php endif; ?>

                        <div>

                            <h3 class="mb-1">
                                <?= esc(trim($firstName . ' ' . $lastName)) ?>
                            </h3>

                            <div class="text-muted">

                                <?= esc($client['designation'] ?? '-') ?>

                            </div>

                            <div class="mt-2">

                                <span class="badge bg-success">
                                    <?= esc($client['status'] ?? '-') ?>
                                </span>

                                <span class="badge bg-secondary">

                                    Assigned :
                                    <?= esc($client['assigned_name'] ?? '-') ?>

                                </span>

                            </div>

                        </div>

                    </div>

                    <div>

                        <a href="<?= BASE_URL ?>modules/clients.php" class="btn btn-outline-secondary">

                            Back

                        </a>

                        <a href="<?= BASE_URL ?>modules/client_create.php?edit=<?= $client['id'] ?>"
                            class="btn btn-primary">

                            Edit Client

                        </a>

                    </div>

                </div>

            </div>

        </div>

        <div class="row g-4">

            <!-- Client Information -->
            <div class="col-lg-8">

                <div class="card shadow-sm h-100">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2 text-primary"></i>
                            Client Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="text-muted small">Salutation</label>
                                <h6><?= esc($client['salutation'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Designation</label>
                                <h6><?= esc($client['designation'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Company</label>
                                <h6><?= esc($client['company_name'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Assigned To</label>
                                <h6><?= esc($client['assigned_name'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Email</label>
                                <h6>
                                    <a href="mailto:<?= esc($client['email_id']) ?>">
                                        <?= esc($client['email_id'] ?? '-') ?>
                                    </a>
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Mobile</label>
                                <h6>
                                    <a href="tel:<?= esc($client['phone_number']) ?>">
                                        <?= esc($client['phone_number'] ?? '-') ?>
                                    </a>
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">WhatsApp</label>
                                <h6>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $client['whatsapp_number'] ?? '') ?>"
                                        target="_blank">
                                        <?= esc($client['whatsapp_number'] ?? '-') ?>
                                    </a>
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Status</label>

                                <h6>
                                    <span class="badge <?= status_badge_class($client['status']) ?>">
                                        <?= esc($client['status']) ?>
                                    </span>
                                </h6>

                            </div>

                            <div class="col-md-12">
                                <label class="text-muted small">Address</label>

                                <h6>
                                    <?= esc($client['person_address'] ?? '-') ?>
                                </h6>

                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">City</label>

                                <h6>
                                    <?= esc($client['person_city'] ?? '-') ?>
                                </h6>

                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Country</label>

                                <h6>
                                    <?= esc($client['person_country'] ?? '-') ?>
                                </h6>

                            </div>

                            <div class="col-md-12">

                                <label class="text-muted small">
                                    LinkedIn Profile
                                </label>

                                <h6>

                                    <?php if (!empty($client['linkedin_profile_url'])): ?>

                                        <a href="<?= esc($client['linkedin_profile_url']) ?>" target="_blank">

                                            <?= esc($client['linkedin_profile_url']) ?>

                                        </a>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </h6>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Right Side -->
            <div class="col-lg-4">

                <div class="card shadow-sm mb-4">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            Photo
                        </h5>
                    </div>

                    <div class="card-body text-center">

                        <?php if (!empty($client['photo'])): ?>

                            <img src="<?= BASE_URL . esc($client['photo']) ?>" class="img-fluid rounded">

                        <?php else: ?>

                            <div class="text-muted">
                                No Photo
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="card shadow-sm">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            Visiting Card
                        </h5>
                    </div>

                    <div class="card-body text-center">

                        <?php if (!empty($client['visiting_card'])): ?>

                            <img src="<?= BASE_URL . esc($client['visiting_card']) ?>" class="img-fluid rounded">

                        <?php else: ?>

                            <div class="text-muted">
                                No Visiting Card
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="row mt-4">

            <!-- Referral Information -->
            <div class="col-lg-6">

                <div class="card shadow-sm h-100">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Referral Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="text-muted small">Referred By</label>
                                <h6><?= esc($client['referred_by_name'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Company</label>
                                <h6><?= esc($client['referred_by_company'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Mobile</label>
                                <h6><?= esc($client['referred_by_number'] ?? '-') ?></h6>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small">Email</label>
                                <h6><?= esc($client['referred_by_email'] ?? '-') ?></h6>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Event Information -->
            <div class="col-lg-6">

                <div class="card shadow-sm h-100">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2 text-success"></i>
                            Event Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-12">

                                <label class="text-muted small">
                                    Met At Event
                                </label>

                                <h6>
                                    <?= esc($client['events_met_at'] ?? '-') ?>
                                </h6>

                            </div>

                            <div class="col-md-12">

                                <label class="text-muted small">
                                    Created On
                                </label>

                                <h6>

                                    <?= !empty($client['created_at'])
                                        ? date('d M Y h:i A', strtotime($client['created_at']))
                                        : '-' ?>

                                </h6>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="card shadow-sm mt-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">
                    <i class="fas fa-sticky-note me-2 text-warning"></i>
                    Notes
                </h5>

            </div>

            <div class="card-body">

                <?php if (!empty($client['notes'])): ?>

                    <p style="white-space:pre-line;">

                        <?= esc($client['notes']) ?>

                    </p>

                <?php else: ?>

                    <div class="text-muted">

                        No notes available.

                    </div>

                <?php endif; ?>

            </div>
        </div>

        <!-- follow_ups -->

        <div class="card shadow-sm mt-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="fas fa-calendar-check me-2 text-primary"></i>

                    Follow-up History

                </h5>

            </div>

            <div class="card-body">

                <?php if (empty($followups)): ?>

                    <div class="text-muted">

                        No follow-ups available.

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover">

                            <thead>

                                <tr>

                                    <th>Date</th>

                                    <th>Status</th>

                                    <th>Notes</th>

                                    <th>Created By</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($followups as $row): ?>

                                    <tr>

                                        <td>

                                            <?= date(
                                                'd M Y h:i A',
                                                strtotime($row['followup_date'])
                                            ) ?>

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

                                            <?= esc($row['created_name']) ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- COMMUNICATIONS part
         -->

        <style>
            .timeline-item {

                display: flex;

                gap: 20px;

                margin-bottom: 25px;

            }

            .timeline-icon {

                width: 50px;

                height: 50px;

                border-radius: 50%;

                background: #f4f4f4;

                display: flex;

                align-items: center;

                justify-content: center;

                font-size: 24px;

                flex-shrink: 0;

            }

            .timeline-content {

                flex: 1;

                background: #fff;

                border: 1px solid #eee;

                padding: 18px;

                border-radius: 12px;

            }
        </style>

        <!-- manual respons -->

        <!-- =========================================================
     MANUAL RESPONSE
========================================================= -->

        <div class="card shadow-sm mt-4" id="manual-response">

            <!-- HEADER -->
            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    <i class="fas fa-comments text-success me-2"></i>
                    Manual Response
                </h5>

                <button class="btn btn-success btn-sm" type="button" onclick="toggleManualResponse()">

                    <i class="fas fa-plus"></i>
                    Add Response

                </button>

            </div>


            <div class="card-body">

                <!-- =====================================================
             ADD RESPONSE FORM
        ====================================================== -->

                <form id="manualResponseForm" action="<?= BASE_URL ?>modules/manual_response_save.php" method="post"
                    style="display:none;">

                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">


                    <div class="row">

                        <!-- COMMUNICATION BY -->
                        <div class="col-md-3">

                            <label class="form-label fw-semibold">
                                Communication By
                            </label>

                            <select class="form-select" name="communication_by" required>

                                <option value="">
                                    Select
                                </option>

                                <!-- CLIENT -->
                                <option value="Client">
                                    Client
                                </option>

                                <!-- LOGGED-IN USER -->
                                <option value="<?= esc($_SESSION['user']['name'] ?? '') ?>">

                                    <?= esc($_SESSION['user']['name'] ?? 'User') ?>

                                </option>

                            </select>

                        </div>


                        <!-- RESPONSE -->
                        <div class="col-md-9">

                            <label class="form-label fw-semibold">
                                Response
                            </label>

                            <textarea class="form-control" name="response" rows="4" required></textarea>

                        </div>

                    </div>


                    <!-- BUTTONS -->
                    <div class="mt-3">

                        <button class="btn btn-success" type="submit">

                            <i class="fas fa-save me-1"></i>
                            Save Response

                        </button>


                        <button class="btn btn-secondary" type="button" onclick="toggleManualResponse()">

                            Cancel

                        </button>

                    </div>

                    <hr>

                </form>


                <!-- =====================================================
             RESPONSE HISTORY
        ====================================================== -->

                <?php if (empty($manualResponses)): ?>

                    <div class="text-center text-muted py-4">

                        <i class="fas fa-comments fa-2x mb-2 opacity-50"></i>

                        <div>
                            No manual responses available.
                        </div>

                    </div>


                <?php else: ?>


                    <?php foreach ($manualResponses as $row): ?>

                        <div class="timeline-item">

                            <!-- ICON -->
                            <div class="timeline-icon">

                                <?php if ($row['communication_by'] === 'Client'): ?>

                                    👤

                                <?php else: ?>

                                    👨‍💼

                                <?php endif; ?>

                            </div>


                            <!-- CONTENT -->
                            <div class="timeline-content">

                                <div class="d-flex justify-content-between align-items-start">

                                    <h6 class="mb-1">

                                        <?php if ($row['communication_by'] === 'Client'): ?>

                                            <?= esc(
                                                trim(
                                                    ($client['first_name'] ?? '') .
                                                    ' ' .
                                                    ($client['last_name'] ?? '')
                                                )
                                            ) ?>

                                            <span class="badge bg-danger ms-2">
                                                Client
                                            </span>


                                        <?php else: ?>

                                            <?= esc($row['communication_by']) ?>

                                            <span class="badge bg-success ms-2">
                                                <?= esc($row['communication_by']) ?>
                                            </span>

                                        <?php endif; ?>

                                    </h6>


                                    <small class="text-muted">

                                        <?= !empty($row['created_at'])
                                            ? date(
                                                'd M Y h:i A',
                                                strtotime($row['created_at'])
                                            )
                                            : '';
                                        ?>

                                    </small>

                                </div>


                                <!-- RESPONSE -->
                                <div class="mt-2">

                                    <?= nl2br(
                                        esc($row['response'])
                                    ) ?>

                                </div>


                                <!-- ATTACHMENT -->
                                <?php if (!empty($row['attachment'])): ?>

                                    <div class="mt-3">

                                        <a href="<?= BASE_URL . esc($row['attachment']) ?>" target="_blank"
                                            class="btn btn-sm btn-outline-primary">

                                            <i class="fas fa-paperclip me-1"></i>
                                            Attachment

                                        </a>

                                    </div>

                                <?php endif; ?>


                                <!-- CREATED BY -->
                                <div class="text-muted small mt-3">

                                    <i class="fas fa-user me-1"></i>

                                    Added by:

                                    <strong>

                                        <?= esc(
                                            $row['created_name']
                                            ?? $row['created_by_name']
                                            ?? 'Unknown User'
                                        ) ?>

                                    </strong>

                                </div>

                            </div>

                        </div>


                        <hr>

                    <?php endforeach; ?>


                <?php endif; ?>

            </div>

        </div>


        <!-- =========================================================
     TOGGLE SCRIPT
========================================================= -->

        <script>

            function toggleManualResponse() {
                const form = document.getElementById("manualResponseForm");

                if (form.style.display === "none" || form.style.display === "") {
                    form.style.display = "block";
                }
                else {
                    form.style.display = "none";
                }
            }

        </script>


        <!-- manual respons end  -->

        <div class="card shadow-sm mt-4">

            <div class="card-header">

                <h5>

                    <i class="fas fa-comments text-primary me-2"></i>

                    Communication Timeline

                </h5>

            </div>

            <div class="card-body">

                <?php if (empty($communications)): ?>

                    <div class="text-center text-muted">

                        No communications available.

                    </div>

                <?php else: ?>

                    <?php foreach ($communications as $row): ?>

                        <div class="timeline-item">

                            <div class="timeline-icon">

                                <?php

                                switch ($row['communication_type']) {

                                    case 'Call':

                                        echo '📞';

                                        break;

                                    case 'Email':

                                        echo '📧';

                                        break;

                                    case 'WhatsApp':

                                        echo '💬';

                                        break;

                                    case 'Meeting':

                                        echo '🤝';

                                        break;

                                    case 'Visit':

                                        echo '🏢';

                                        break;

                                    case 'Video Call':

                                        echo '🎥';

                                        break;

                                    default:

                                        echo '📝';

                                }

                                ?>

                            </div>

                            <div class="timeline-content">

                                <div class="d-flex justify-content-between">

                                    <h6>

                                        <?= esc($row['subject']) ?>

                                    </h6>

                                    <small>

                                        <?= date(
                                            'd M Y h:i A',

                                            strtotime($row['communication_date'])
                                        ) ?>

                                    </small>

                                </div>

                                <div class="mb-2">

                                    <?php if ($row['communication_by'] == "Client"): ?>

                                        <span class="badge bg-danger">

                                            Client

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-success">

                                            Unire

                                        </span>

                                    <?php endif; ?>

                                    <span class="badge bg-secondary">

                                        <?= esc($row['communication_type']) ?>

                                    </span>

                                </div>

                                <p>

                                    <?= nl2br(esc($row['communication'])) ?>

                                </p>

                                <?php if (!empty($row['attachment'])): ?>

                                    <a href="<?= BASE_URL . esc($row['attachment']) ?>" target="_blank"
                                        class="btn btn-sm btn-outline-primary">

                                        Attachment

                                    </a>

                                <?php endif; ?>

                                <?php if (!empty($row['next_followup'])): ?>

                                    <div class="mt-2">

                                        <strong>

                                            Next Follow-up :

                                        </strong>

                                        <?= date(

                                            'd M Y h:i A',

                                            strtotime($row['next_followup'])

                                        ) ?>

                                    </div>

                                <?php endif; ?>

                                <div class="text-muted mt-2">

                                    Added By :

                                    <?= esc($row['created_name']) ?>

                                </div>

                            </div>

                        </div>

                        <hr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>
    </div>

</main>



<?php include __DIR__ . '/../includes/footer.php'; ?>
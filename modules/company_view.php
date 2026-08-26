<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'Invalid Company ID.';
    redirect_path('modules/companylist.php');
    exit;
}

$company = fetch_one("SELECT * FROM companies WHERE id = ?", [$id]);
if (!$company) {
    $_SESSION['error'] = 'Company not found.';
    redirect_path('modules/companylist.php');
    exit;
}

$contacts = fetch_all(
    "SELECT *
     FROM clients
     WHERE company_id = ?
     ORDER BY first_name ASC, last_name ASC",
    [$id]
);

$meetings = fetch_all(
    "SELECT *
     FROM meetings
     WHERE company_id = ?
     ORDER BY meeting_date DESC, id DESC",
    [$id]
);

$followups = fetch_all(
    "SELECT
        f.*,
        CONCAT(c.first_name,' ',c.last_name) AS client_name,
        u.name AS created_name
     FROM follow_ups f
     INNER JOIN clients c ON c.id = f.client_id
     LEFT JOIN users u ON u.id = f.created_by
     WHERE c.company_id = ?
     ORDER BY f.followup_date DESC, f.id DESC",
    [$id]
);

$communications = fetch_all(
    "SELECT
        cm.*,
        CONCAT(c.first_name,' ',c.last_name) AS client_name,
        u.name AS created_name
     FROM communications cm
     INNER JOIN clients c ON c.id = cm.contact_id
     LEFT JOIN users u ON u.id = cm.created_by
     WHERE cm.company_id = ?
     ORDER BY cm.communication_date DESC, cm.id DESC",
    [$id]
);

$totalContacts = count($contacts);
$totalMeetings = count($meetings);
$totalFollowups = count($followups);
$totalCommunications = count($communications);

$lastMeeting = $meetings[0] ?? null;
$upcomingMeeting = fetch_one(
    "SELECT *
     FROM meetings
     WHERE company_id = ?
       AND meeting_date >= NOW()
     ORDER BY meeting_date ASC, id ASC
     LIMIT 1",
    [$id]
);

// manual response fatch 

$companyResponses = fetch_all("
SELECT
    mr.*,
    c.id AS client_id,
    c.first_name,
    c.last_name,
    c.designation,
    u.name AS created_name
FROM client_manual_responses mr
INNER JOIN clients c
    ON c.id = mr.client_id
LEFT JOIN users u
    ON u.id = mr.created_by
WHERE c.company_id = ?
ORDER BY mr.created_at DESC
", [$id]);

$pageTitle = 'Company Details';
$pageDescription = $company['company_name'] ?? 'Company Details';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <style>
        .main-content {
            background: #f4f7fb;
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1600px;
        }

        .hero-card,
        .stat-card,
        .panel-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
        }

        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            overflow: hidden;
        }

        .hero-sub {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.88rem;
        }

        .stat-card .card-body {
            padding: 1rem 1.15rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }

        .panel-card .card-header {
            background: #fff;
            border: 0;
            padding: 1rem 1.25rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .info-label {
            font-size: 0.72rem;
            color: #6b7280;
            display: block;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .info-value {
            font-weight: 600;
            color: #111827;
        }

        .muted-box {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 14px;
            padding: 1rem;
        }

        .small-head {
            font-size: 0.78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .soft-table thead th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6b7280;
            border-bottom: 1px solid #eef2f7;
        }

        .soft-table td,
        .soft-table th {
            padding: 0.8rem 0.75rem;
            vertical-align: middle;
        }

        .timeline-item {
            border-left: 3px solid #e5e7eb;
            padding-left: 1rem;
            position: relative;
            margin-bottom: 1rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0.4rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #2563eb;
        }

        .badge-soft {
            background: #eef2ff;
            color: #3730a3;
        }
    </style>

    <div class="container-fluid page-wrap px-4 py-4">
        <div class="hero-card mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="hero-sub mb-1">Company Details</div>
                        <h2 class="fw-bold mb-1"><?php echo esc($company['company_name'] ?? '-'); ?></h2>
                        <div class="hero-sub"><?php echo esc($company['industry'] ?? '-'); ?></div>
                    </div>
                    <div class="text-end">

                        <div class="hero-sub">Status</div>
                        <a class="btn btn-sm btn-primary"
                            href="<?= BASE_URL ?>modules/company_edit.php?id=<?= $company['id'] ?>">
                            <i class="fa fa-edit"></i> Edit
                        </a>&nbsp;&nbsp;&nbsp;&nbsp;
                        <span
                            class="badge bg-light text-dark px-3 py-2 mt-1"><?php echo esc($company['status'] ?? '-'); ?></span>



                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small-head">Contacts</div>
                            <div class="fs-3 fw-bold mb-0"><?php echo $totalContacts; ?></div>
                        </div>
                        <div class="stat-icon text-primary"><i class="fa fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small-head">Meetings</div>
                            <div class="fs-3 fw-bold mb-0"><?php echo $totalMeetings; ?></div>
                        </div>
                        <div class="stat-icon text-success"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small-head">Communications</div>
                            <div class="fs-3 fw-bold mb-0"><?php echo $totalCommunications; ?></div>
                        </div>
                        <div class="stat-icon text-warning"><i class="fa fa-comments"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small-head">Follow-ups</div>
                            <div class="fs-3 fw-bold mb-0"><?php echo $totalFollowups; ?></div>
                        </div>
                        <div class="stat-icon text-danger"><i class="fa fa-bell"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card panel-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="section-title"><i class="fa fa-building text-primary me-2"></i>Company Information
                        </h5>
                        <span class="badge badge-soft">Overview</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="muted-box"><span class="info-label">Company Name</span>
                                    <div class="info-value"><?php echo esc($company['company_name'] ?? '-'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="muted-box"><span class="info-label">Industry</span>
                                    <div class="info-value"><?php echo esc($company['industry'] ?? '-'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="muted-box"><span class="info-label">Website</span>
                                    <div class="info-value">
                                        <?php echo !empty($company['website']) ? '<a href="' . esc($company['website']) . '" target="_blank">' . esc($company['website']) . '</a>' : '-'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="muted-box"><span class="info-label">Email</span>
                                    <div class="info-value"><?php echo esc($company['company_email'] ?? '-'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="muted-box"><span class="info-label">Phone</span>
                                    <div class="info-value"><?php echo esc($company['company_phone'] ?? '-'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="muted-box"><span class="info-label">LinkedIn</span>
                                    <div class="info-value">
                                        <?php echo !empty($company['linkedin']) ? '<a href="' . esc($company['linkedin']) . '" target="_blank">View Profile</a>' : '-'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="muted-box"><span class="info-label">Address</span>
                                    <div class="info-value"><?php echo nl2br(esc($company['address'] ?? '-')); ?></div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label fw-bold">Systems Used</label>

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php
                                    $systems = explode(',', $company['systems_used'] ?? '');

                                    foreach ($systems as $system) {
                                        $system = trim($system);
                                        if ($system == '')
                                            continue;
                                        ?>
                                        <span class="badge rounded-pill bg-primary px-3 py-2">
                                            <?= esc($system) ?>
                                        </span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card panel-card h-100">
                    <div class="card-header">
                        <h5 class="section-title"><i class="fa fa-chart-line text-success me-2"></i>Sales Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0 soft-table">
                            <tr>
                                <th style="width:45%">Lead Source</th>
                                <td><?php echo esc($company['lead_source'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Prospect</th>
                                <td><?php echo esc($company['prospect'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span class="badge bg-success"><?php echo esc($company['status'] ?? '-'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Assigned To</th>
                                <td><?php echo esc($company['assigned_to'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>City</th>
                                <td><?php echo esc($company['city'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>State</th>
                                <td><?php echo esc($company['state'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Country</th>
                                <td><?php echo esc($company['country'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Pincode</th>
                                <td><?php echo esc($company['pincode'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-0">
            <div class="col-lg-6">
                <div class="card panel-card h-100">
                    <div class="card-header">
                        <h5 class="section-title"><i class="fa fa-calendar-check text-success me-2"></i>Meetings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="muted-box h-100">
                                    <div class="small-head mb-2">Last Meeting</div>
                                    <?php if ($lastMeeting): ?>
                                        <div class="fw-bold"><?php echo esc($lastMeeting['meeting_title'] ?? '-'); ?></div>
                                        <div class="text-muted small mb-1">
                                            <?php echo !empty($lastMeeting['meeting_date']) ? date('d M Y h:i A', strtotime($lastMeeting['meeting_date'])) : '-'; ?>
                                        </div>
                                        <div><?php echo esc($lastMeeting['meeting_location'] ?? '-'); ?></div>
                                        <div class="mt-2"><span
                                                class="badge bg-secondary"><?php echo esc($lastMeeting['meeting_status'] ?? '-'); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">No meeting found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="muted-box h-100">
                                    <div class="small-head mb-2">Upcoming Meeting</div>
                                    <?php if ($upcomingMeeting): ?>
                                        <div class="fw-bold"><?php echo esc($upcomingMeeting['meeting_title'] ?? '-'); ?>
                                        </div>
                                        <div class="text-muted small mb-1">
                                            <?php echo !empty($upcomingMeeting['meeting_date']) ? date('d M Y h:i A', strtotime($upcomingMeeting['meeting_date'])) : '-'; ?>
                                        </div>
                                        <div><?php echo esc($upcomingMeeting['meeting_location'] ?? '-'); ?></div>
                                        <div class="mt-2"><span
                                                class="badge bg-secondary"><?php echo esc($upcomingMeeting['meeting_status'] ?? '-'); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">No upcoming meeting.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card panel-card h-100">
                    <div class="card-header">
                        <h5 class="section-title"><i class="fa fa-bell text-danger me-2"></i>Follow-ups</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($followups)): ?>
                            <div class="text-muted">No follow-ups found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm soft-table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                            <th>Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($followups as $row): ?>
                                            <tr>
                                                <td><?php echo !empty($row['followup_date']) ? date('d M Y h:i A', strtotime($row['followup_date'])) : '-'; ?>
                                                </td>
                                                <td><?php echo esc($row['client_name'] ?? '-'); ?></td>
                                                <td><span
                                                        class="badge <?php echo status_badge_class($row['status'] ?? 'Pending'); ?>"><?php echo esc($row['status'] ?? '-'); ?></span>
                                                </td>
                                                <td><?php echo nl2br(esc($row['notes'] ?? '-')); ?></td>
                                                <td><?php echo esc($row['created_name'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-0">
            <div class="col-lg-6">
                <div class="card panel-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="section-title"><i class="fa fa-users text-primary me-2"></i>Contacts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contacts)): ?>
                            <div class="text-muted">No contacts found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table soft-table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Designation</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contacts as $i => $contact): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td><?php echo esc(trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')) ?: '-'); ?>
                                                </td>
                                                <td><?php echo esc($contact['designation'] ?? '-'); ?></td>
                                                <td><?php echo !empty($contact['email']) ? '<a href="mailto:' . esc($contact['email']) . '">' . esc($contact['email']) . '</a>' : '-'; ?>
                                                </td>
                                                <td><?php echo esc($contact['phone'] ?? '-'); ?></td>
                                                <td><?php echo esc($contact['status'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card panel-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="section-title"><i class="fa fa-comments text-warning me-2"></i>Company Communication
                            Responses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($communications)): ?>
                            <div class="text-muted">No communication history.</div>
                        <?php else: ?>
                            <?php foreach ($communications as $row): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold">
                                                <?php echo esc(ucfirst($row['communication_type'] ?? '-')); ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo !empty($row['communication_date']) ? date('d M Y h:i A', strtotime($row['communication_date'])) : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2"><?php echo nl2br(esc($row['communication'] ?? '')); ?></div>
                                    <div class="mt-2 small text-muted">By <?php echo esc($row['created_name'] ?? 'System'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        


        <!-- add manual response section end   -->
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
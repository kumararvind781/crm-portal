<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Add Client';
$pageDescription = 'Create or update a full client profile with contact and media details.';

$editId = (int)($_GET['edit'] ?? 0);
$client = $editId ? fetch_one('SELECT * FROM clients WHERE id = ?', [$editId]) : null;
if ($client) $pageTitle = 'Edit Client';

$companies = fetch_all('SELECT id, company_name FROM companies ORDER BY company_name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $existing = $id ? fetch_one('SELECT photo, visiting_card FROM clients WHERE id = ?', [$id]) : null;

    $photo = $existing['photo'] ?? null;
    $visitingCard = $existing['visiting_card'] ?? null;

    if (!empty($_FILES['photo']['name'])) {
        $uploaded = upload_card_file($_FILES['photo']);
        if ($uploaded) {
            delete_file_if_exists($photo);
            $photo = $uploaded;
        }
    }

    if (!empty($_FILES['visiting_card']['name'])) {
        $uploadedCard = upload_card_file($_FILES['visiting_card']);
        if ($uploadedCard) {
            delete_file_if_exists($visitingCard);
            $visitingCard = $uploadedCard;
        }
    }

    $salutation = trim($_POST['salutation'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $fullName = trim($salutation . ' ' . $firstName . ' ' . $lastName);

    $params = [
        $fullName,
        $salutation,
        $firstName,
        $lastName,
        $designation,
        trim($_POST['person_address'] ?? ''),
        (int)($_POST['company_id'] ?? 0),
        trim($_POST['person_city'] ?? ''),
        trim($_POST['person_country'] ?? ''),
        trim($_POST['email_id'] ?? ''),
        trim($_POST['phone_number'] ?? ''),
        trim($_POST['whatsapp_number'] ?? ''),
        $photo,
        trim($_POST['linkedin_profile_url'] ?? ''),
        $visitingCard,
        trim($_POST['events_met_at'] ?? ''),
        trim($_POST['referred_by_name'] ?? ''),
        trim($_POST['referred_by_company'] ?? ''),
        trim($_POST['referred_by_number'] ?? ''),
        trim($_POST['referred_by_email'] ?? ''),
        trim($_POST['status'] ?? 'Active'),
        (int)($_POST['assigned_to'] ?? 0),
        trim($_POST['notes'] ?? '')
    ];

    if ($id) {
        $params[] = $id;
        execute_query(
            'UPDATE clients SET name=?, salutation=?, first_name=?, last_name=?, designation=?, person_address=?, company_id=?, person_city=?, person_country=?, email_id=?, phone_number=?, whatsapp_number=?, photo=?, linkedin_profile_url=?, visiting_card=?, events_met_at=?, referred_by_name=?, referred_by_company=?, referred_by_number=?, referred_by_email=?, status=?, assigned_to=?, notes=? WHERE id=?',
            $params
        );
    } else {
        execute_query(
            'INSERT INTO clients (name, salutation, first_name, last_name, designation, person_address, company_id, person_city, person_country, email_id, phone_number, whatsapp_number, photo, linkedin_profile_url, visiting_card, events_met_at, referred_by_name, referred_by_company, referred_by_number, referred_by_email, status, assigned_to, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            $params
        );
    }

    redirect_path('modules/clients.php');
}

$users = fetch_all('SELECT id, name FROM users ORDER BY name ASC');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <style>
        .main-content{background:#f4f7fb;min-height:100vh}
        .page-wrap{max-width:1500px}
        .panel{border:0;border-radius:18px;box-shadow:0 10px 28px rgba(15,23,42,.07)}
        .panel-head{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:#fff;border-radius:18px 18px 0 0}
        .panel-head .sub{color:rgba(255,255,255,.72);font-size:.88rem}
        .form-card{background:#fff;border-radius:18px;padding:1.25rem}
        .form-label{font-size:.76rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;margin-bottom:.35rem}
        .preview-thumb{width:110px;height:110px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb}
        .file-box{border:1px dashed #d1d5db;border-radius:14px;padding:1rem;background:#f8fafc}
        .actions{display:flex;gap:.75rem;flex-wrap:wrap}
    </style>

    <div class="container-fluid page-wrap px-4 py-4">
        <div class="panel">
            <div class="panel-head p-4 p-lg-5 d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="sub mb-1">Client Profile</div>
                    <h2 class="fw-bold mb-1"><?php echo $client ? 'Edit Client' : 'Add New Client'; ?></h2>
                    <div class="sub">Create or update a full client profile with contact and media details.</div>
                </div>
                <a class="btn btn-light" href="<?php echo BASE_URL; ?>modules/clients.php">Back to Clients</a>
            </div>

            <div class="form-card">
                <form method="post" enctype="multipart/form-data" class="row g-4">
                    <input type="hidden" name="id" value="<?php echo esc($client['id'] ?? ''); ?>">

                    <div class="col-md-4">
                        <label class="form-label">Salutation</label>
                        <select name="salutation" class="form-select">
                            <?php foreach (['Mr','Mrs','Ms','Dr','Prof'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo (($client['salutation'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo esc($client['first_name'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo esc($client['last_name'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" class="form-control" value="<?php echo esc($client['designation'] ?? ''); ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Person Address</label>
                        <input type="text" name="person_address" class="form-control" value="<?php echo esc($client['person_address'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo (int)$company['id']; ?>" <?php echo ((int)($client['company_id'] ?? 0) === (int)$company['id']) ? 'selected' : ''; ?>>
                                    <?php echo esc($company['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Person City</label>
                        <input type="text" name="person_city" class="form-control" value="<?php echo esc($client['person_city'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Person Country</label>
                        <input type="text" name="person_country" class="form-control" value="<?php echo esc($client['person_country'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Email ID</label>
                        <input type="email" name="email_id" class="form-control" value="<?php echo esc($client['email_id'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo esc($client['phone_number'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">WhatsApp / Alternate Number</label>
                        <input type="text" name="whatsapp_number" class="form-control" value="<?php echo esc($client['whatsapp_number'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <div class="file-box h-100">
                            <label class="form-label">Photo of Person</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <?php if (!empty($client['photo'])): ?>
                                <div class="mt-3"><img class="preview-thumb" src="<?php echo BASE_URL . esc($client['photo']); ?>" alt="Photo"></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="file-box h-100">
                            <label class="form-label">Visiting Card (pdf, jpg)</label>
                            <input type="file" name="visiting_card" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <?php if (!empty($client['visiting_card'])): ?>
                                <div class="mt-3 small text-muted">
                                    <a href="<?php echo BASE_URL . esc($client['visiting_card']); ?>" target="_blank">View uploaded card</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">LinkedIn Profile URL</label>
                        <input type="url" name="linkedin_profile_url" class="form-control" value="<?php echo esc($client['linkedin_profile_url'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Event(s) Met At</label>
                        <input type="text" name="events_met_at" class="form-control" value="<?php echo esc($client['events_met_at'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Referred By - Name</label>
                        <input type="text" name="referred_by_name" class="form-control" value="<?php echo esc($client['referred_by_name'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Referred By - Company</label>
                        <input type="text" name="referred_by_company" class="form-control" value="<?php echo esc($client['referred_by_company'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Referred By - Number</label>
                        <input type="text" name="referred_by_number" class="form-control" value="<?php echo esc($client['referred_by_number'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Referred By - Email</label>
                        <input type="email" name="referred_by_email" class="form-control" value="<?php echo esc($client['referred_by_email'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Active','Pending','Qualified','Inactive'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo (($client['status'] ?? 'Active') === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Assigned User</label>
                        <select name="assigned_to" class="form-select">
                            <option value="0">Select User</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo (int)$u['id']; ?>" <?php echo ((int)($client['assigned_to'] ?? 0) === (int)$u['id']) ? 'selected' : ''; ?>><?php echo esc($u['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" value="<?php echo esc($client['notes'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <div class="actions pt-2">
                            <button class="btn btn-primary px-4" type="submit"><?php echo $client ? 'Update Client' : 'Save Client'; ?></button>
                            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>modules/clients.php">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
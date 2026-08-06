<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

/*
|--------------------------------------------------------------------------
| Company Edit
|--------------------------------------------------------------------------
*/

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid Company.";
    redirect_path('modules/company_list.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Fetch Company
|--------------------------------------------------------------------------
*/

$company = fetch_one(
    "SELECT * FROM companies WHERE id=?",
    [$id]
);

if (!$company) {
    $_SESSION['error'] = "Company not found.";
    redirect_path('modules/company_list.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = "Edit Company";
$pageDescription = "Update company information.";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<section class="panel wide-form-panel">

<div class="panel-header">

<div>

<h2>Edit Company</h2>

<p>Update company details.</p>

</div>

<div>

<a
href="<?= BASE_URL ?>modules/company_list.php"
class="btn btn-outline">

<i class="fa fa-arrow-left"></i>

Back

</a>

</div>

</div>

<form
method="post"
action="<?= BASE_URL ?>modules/company_save.php"
class="form-grid company-form">

<input
type="hidden"
name="id"
value="<?= $company['id']; ?>">

<div class="section-title">

Company Information

</div>

<div>

<label>Company Name <span class="text-danger">*</span></label>

<input
type="text"
name="company_name"
class="form-control"
required
value="<?= esc($company['company_name']); ?>">

</div>

<div>

<label>Industry</label>

<select
name="industry"
class="form-control">

<option value="">Select Industry</option>

<option <?= ($company['industry']=="IT")?"selected":""; ?>>IT</option>

<option <?= ($company['industry']=="Manufacturing")?"selected":""; ?>>Manufacturing</option>

<option <?= ($company['industry']=="Healthcare")?"selected":""; ?>>Healthcare</option>

<option <?= ($company['industry']=="Education")?"selected":""; ?>>Education</option>

<option <?= ($company['industry']=="Finance")?"selected":""; ?>>Finance</option>

<option <?= ($company['industry']=="Retail")?"selected":""; ?>>Retail</option>

<option <?= ($company['industry']=="Government")?"selected":""; ?>>Government</option>

<option <?= ($company['industry']=="Real Estate")?"selected":""; ?>>Real Estate</option>

<option <?= ($company['industry']=="Other")?"selected":""; ?>>Other</option>

</select>

</div>

<div class="full">

<label>Company Address</label>

<textarea
name="address"
rows="3"
class="form-control"><?= esc($company['address']); ?></textarea>

</div>

<div>

<label>City</label>

<input
type="text"
name="city"
class="form-control"
value="<?= esc($company['city']); ?>">

</div>

<div>

<label>State</label>

<input
type="text"
name="state"
class="form-control"
value="<?= esc($company['state']); ?>">

</div>

<div>

<label>Country</label>

<input
type="text"
name="country"
class="form-control"
value="<?= esc($company['country']); ?>">

</div>

<div>

<label>Pincode</label>

<input
type="text"
name="pincode"
class="form-control"
value="<?= esc($company['pincode']); ?>">

</div>

<hr class="full">

<div class="section-title full">

Business Information

</div>

<div>

<label>Website</label>

<input
type="url"
name="website"
class="form-control"
value="<?= esc($company['website']); ?>">

</div>

<div>

<label>LinkedIn</label>

<input
type="url"
name="linkedin"
class="form-control"
value="<?= esc($company['linkedin']); ?>">

</div>

<div>

<label>Company Email</label>

<input
type="email"
name="company_email"
class="form-control"
value="<?= esc($company['company_email']); ?>">

</div>

<div>

<label>Company Phone</label>

<input
type="text"
name="company_phone"
class="form-control"
value="<?= esc($company['company_phone']); ?>">

</div>

<div class="full">

<label>Systems Used</label>

<textarea
name="systems_used"
rows="3"
class="form-control"><?= esc($company['systems_used']); ?></textarea>

</div>

<hr class="full">

<div class="section-title full">

Sales Information

</div>

<div>

<label>Prospect Level</label>

<select
name="prospect"
class="form-control">

<option value="Hot" <?= ($company['prospect']=="Hot")?"selected":""; ?>>Hot</option>

<option value="Warm" <?= ($company['prospect']=="Warm")?"selected":""; ?>>Warm</option>

<option value="Cold" <?= ($company['prospect']=="Cold")?"selected":""; ?>>Cold</option>

</select>

</div>

<div>

<label>Lead Source</label>

<select
name="lead_source"
class="form-control">

<option value="">Select</option>

<option value="Reference" <?= ($company['lead_source']=="Reference")?"selected":""; ?>>Reference</option>

<option value="Website" <?= ($company['lead_source']=="Website")?"selected":""; ?>>Website</option>

<option value="LinkedIn" <?= ($company['lead_source']=="LinkedIn")?"selected":""; ?>>LinkedIn</option>

<option value="Cold Call" <?= ($company['lead_source']=="Cold Call")?"selected":""; ?>>Cold Call</option>

<option value="Email" <?= ($company['lead_source']=="Email")?"selected":""; ?>>Email</option>

<option value="Exhibition" <?= ($company['lead_source']=="Exhibition")?"selected":""; ?>>Exhibition</option>

<option value="Other" <?= ($company['lead_source']=="Other")?"selected":""; ?>>Other</option>

</select>

</div>

<div>

<label>Assigned To</label>

<select
name="assigned_to"
class="form-control">

<option value="">Select User</option>

<?php
$users = fetch_all("SELECT id,name FROM users ORDER BY name");

foreach($users as $user){
?>

<option
value="<?= $user['id']; ?>"
<?= ($company['assigned_to']==$user['id']) ? "selected" : ""; ?>>

<?= esc($user['name']); ?>

</option>

<?php } ?>

</select>

</div>

<div>

<label>Status</label>

<select
name="status"
class="form-control">

<option value="Active" <?= ($company['status']=="Active")?"selected":""; ?>>Active</option>

<option value="Pending" <?= ($company['status']=="Pending")?"selected":""; ?>>Pending</option>

<option value="Inactive" <?= ($company['status']=="Inactive")?"selected":""; ?>>Inactive</option>

</select>

</div>

<div class="full">

<label>Remarks</label>

<textarea
name="remarks"
rows="5"
class="form-control"><?= esc($company['remarks']); ?></textarea>

</div>

<div class="full form-actions">

<button
type="submit"
class="btn btn-primary">

<i class="fa fa-save"></i>

Update Company

</button>

<a
href="<?= BASE_URL ?>modules/company_list.php"
class="btn btn-secondary">

Cancel

</a>

</div>

</form>

</section>

</main>

<?php include __DIR__.'/../includes/footer.php'; ?>
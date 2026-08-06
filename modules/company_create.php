<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = "Add Company";
$pageDescription = "Create a new company.";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<section class="panel wide-form-panel">

    <div class="panel-header">
        <div>
            <h2>Add New Company</h2>
            <p>Create a company before adding contact persons.</p>
        </div>

        <a href="<?= BASE_URL ?>modules/company_list.php"
           class="btn btn-outline">
            <i class="fa fa-arrow-left"></i>
            Back
        </a>
    </div>

<form
method="post"
action="<?= BASE_URL ?>modules/company_save.php"
class="form-grid company-form">

<div class="section-title">
    Company Information
</div>

<div>
<label>Company Name *</label>
<input
type="text"
name="company_name"
required>
</div>

<div>
<label>Industry</label>

<select name="industry">

<option value="">Select</option>

<option>IT</option>

<option>Manufacturing</option>

<option>Education</option>

<option>Healthcare</option>

<option>Finance</option>

<option>Retail</option>

<option>Government</option>

<option>Pharma</option>

<option>Real Estate</option>

<option>Other</option>

</select>

</div>

<div class="full">

<label>Company Address</label>

<textarea
name="address"
rows="3"></textarea>

</div>

<div>

<label>City</label>

<input
type="text"
name="city">

</div>

<div>

<label>State</label>

<input
type="text"
name="state">

</div>

<div>

<label>Country</label>

<input
type="text"
name="country"
value="India">

</div>

<div>

<label>Pincode</label>

<input
type="text"
name="pincode">

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
placeholder="https://">

</div>

<div>

<label>LinkedIn</label>

<input
type="url"
name="linkedin">

</div>

<div>

<label>Email</label>

<input
type="email"
name="company_email">

</div>

<div>

<label>Phone</label>

<input
type="text"
name="company_phone">

</div>

<div class="full">

<label>Systems Used</label>

<textarea
name="systems_used"
rows="3"
placeholder="SAP, Oracle, Tally, ERP, CRM..."></textarea>

</div>

<hr class="full">

<div class="section-title full">

Sales Information

</div>

<div>

<label>Prospect Level</label>

<select name="prospect">

<option value="Hot">Hot</option>

<option value="Warm" selected>Warm</option>

<option value="Cold">Cold</option>

</select>

</div>

<div>

<label>Lead Source</label>

<select name="lead_source">

<option>Reference</option>

<option>Website</option>

<option>Exhibition</option>

<option>Cold Call</option>

<option>LinkedIn</option>

<option>Email</option>

<option>Other</option>

</select>

</div>

<div>

<label>Assigned To</label>

<select name="assigned_to">

<?php
$users=fetch_all("SELECT id,name FROM users ORDER BY name");
foreach($users as $u){
?>

<option value="<?= $u['id'] ?>">

<?= esc($u['name']) ?>

</option>

<?php } ?>

</select>

</div>

<div>

<label>Status</label>

<select name="status">

<option value="Active">Active</option>

<option value="Pending">Pending</option>

<option value="Inactive">Inactive</option>

</select>

</div>

<div class="full">

<label>Remarks</label>

<textarea
name="remarks"
rows="5"></textarea>

</div>

<div class="full form-actions">

<button
class="btn btn-primary"
type="submit">

<i class="fa fa-save"></i>

Save Company

</button>

<a
href="<?= BASE_URL ?>modules/company_list.php"
class="btn btn-outline">

Cancel

</a>

</div>

</form>

</section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
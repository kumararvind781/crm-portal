<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = 'Invalid Company.';
    redirect_path('modules/company_list.php');
    exit;
}

$company = fetch_one('SELECT * FROM companies WHERE id=?', [$id]);
if (!$company) {
    $_SESSION['error'] = 'Company not found.';
    redirect_path('modules/company_list.php');
    exit;
}

$pageTitle = 'Edit Company';
$pageDescription = 'Update company information.';
$systems = fetch_all('SELECT system_name FROM master_systems ORDER BY system_name');
$selectedSystems = array_values(array_unique(array_filter(array_map(
    'trim',
    explode(',', (string) ($company['systems_used'] ?? ''))
))));

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
            <a href="<?= BASE_URL ?>modules/company_list.php" class="btn btn-outline">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>

        <form method="post" action="<?= BASE_URL ?>modules/company_save.php" class="form-grid company-form">
            <input type="hidden" name="id" value="<?= (int) $company['id']; ?>">

            <div class="section-title full">Company Information</div>
            <div>
                <label>Company Name <span class="text-danger">*</span></label>
                <input type="text" name="company_name" class="form-control" required
                    value="<?= esc($company['company_name'] ?? ''); ?>">
            </div>
            <div>
                <label>Industry</label>
                <select name="industry" class="form-control">
                    <option value="">Select Industry</option>
                    <?php foreach (['IT', 'Manufacturing', 'Healthcare', 'Education', 'Finance', 'Retail', 'Government', 'Real Estate', 'Other'] as $item): ?>
                        <option value="<?= esc($item); ?>" <?= (($company['industry'] ?? '') === $item) ? 'selected' : ''; ?>>
                            <?= esc($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full"><label>Company Address</label><textarea name="address" rows="3"
                    class="form-control"><?= esc($company['address'] ?? ''); ?></textarea></div>
            <div><label>City</label><input type="text" name="city" class="form-control"
                    value="<?= esc($company['city'] ?? ''); ?>"></div>
            <div><label>State</label><input type="text" name="state" class="form-control"
                    value="<?= esc($company['state'] ?? ''); ?>"></div>
            <div><label>Country</label><input type="text" name="country" class="form-control"
                    value="<?= esc($company['country'] ?? ''); ?>"></div>
            <div><label>Pincode</label><input type="text" name="pincode" class="form-control"
                    value="<?= esc($company['pincode'] ?? ''); ?>"></div>

            <hr class="full">
            <div class="section-title full">Business Information</div>
            <div><label>Website</label><input type="url" name="website" class="form-control"
                    value="<?= esc($company['website'] ?? ''); ?>"></div>
            <div><label>LinkedIn</label><input type="url" name="linkedin" class="form-control"
                    value="<?= esc($company['linkedin'] ?? ''); ?>"></div>
            <div><label>Company Email</label><input type="email" name="company_email" class="form-control"
                    value="<?= esc($company['company_email'] ?? ''); ?>"></div>
            <div><label>Company Phone</label><input type="text" name="company_phone" class="form-control"
                    value="<?= esc($company['company_phone'] ?? ''); ?>"></div>

            <div class="full">
                <label>Systems Used</label>

                <select id="system_select" class="form-control">
                    <option value="">Select System</option>

                    <?php foreach ($systems as $row): ?>
                        <option value="<?= esc($row['system_name']); ?>">
                            <?= esc($row['system_name']); ?>
                        </option>
                    <?php endforeach; ?>

                    <option value="__other__">Other</option>
                </select>

                <div id="otherDiv" style="display:none;margin-top:10px;">
                    <input type="text" id="otherSystem" name="other_system" class="form-control"
                        placeholder="Enter new system">
                </div>

                <input type="hidden" name="systems_used" id="systems_used"
                    value="<?= esc(implode(',', $selectedSystems)); ?>">

                <div id="selectedSystems" style="margin-top:10px;"></div>
            </div>

            <hr class="full">
            <div class="section-title full">Sales Information</div>
            <div><label>Prospect Level</label><select name="prospect" class="form-control">
                    <?php foreach (['Hot', 'Warm', 'Cold'] as $item): ?>
                        <option value="<?= $item; ?>" <?= (($company['prospect'] ?? '') === $item) ? 'selected' : ''; ?>>
                            <?= $item; ?>
                        </option><?php endforeach; ?>
                </select></div>
            <div><label>Lead Source</label><select name="lead_source" class="form-control">
                    <option value="">Select</option>
                    <?php foreach (['Reference', 'Website', 'LinkedIn', 'Cold Call', 'Email', 'Exhibition', 'Other'] as $item): ?>
                        <option value="<?= esc($item); ?>" <?= (($company['lead_source'] ?? '') === $item) ? 'selected' : ''; ?>><?= esc($item); ?></option><?php endforeach; ?>
                </select></div>
            <div><label>Assigned To</label><select name="assigned_to" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach (fetch_all('SELECT id,name FROM users ORDER BY name') as $user): ?>
                        <option value="<?= (int) $user['id']; ?>" <?= ((int) ($company['assigned_to'] ?? 0) === (int) $user['id']) ? 'selected' : ''; ?>><?= esc($user['name']); ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Status</label><select name="status" class="form-control">
                    <?php foreach (['Active', 'Pending', 'Inactive'] as $item): ?>
                        <option value="<?= $item; ?>" <?= (($company['status'] ?? '') === $item) ? 'selected' : ''; ?>>
                            <?= $item; ?>
                        </option><?php endforeach; ?>
                </select></div>
            <div class="full"><label>Remarks</label><textarea name="remarks" rows="5"
                    class="form-control"><?= esc($company['remarks'] ?? ''); ?></textarea></div>
            <div class="full form-actions"><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>
                    Update Company</button><a href="<?= BASE_URL ?>modules/company_list.php"
                    class="btn btn-secondary">Cancel</a></div>
        </form>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('system_select');
        const hidden = document.getElementById('systems_used');
        const box = document.getElementById('selectedSystems');
        const otherDiv = document.getElementById('otherDiv');
        const otherInput = document.getElementById('otherSystem');

        let systems = hidden.value.split(',').map(v => v.trim()).filter(Boolean);
        systems = [...new Set(systems)];

        function sync() { hidden.value = systems.join(','); }

        function render() {
            box.replaceChildren();
            systems.forEach(function (name) {
                const tag = document.createElement('span');
                tag.className = 'system-tag';
                tag.style.cssText = 'display:inline-flex;align-items:center;gap:6px;background:#1677ff;color:#fff;padding:7px 11px;border-radius:16px;margin:4px;';
                tag.append(document.createTextNode(name));
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.style.cssText = 'border:0;background:transparent;color:#fff;cursor:pointer;font-size:17px;';
                remove.addEventListener('click', function () {
                    systems = systems.filter(item => item !== name);
                    sync();
                    render();
                });
                tag.appendChild(remove);
                box.appendChild(tag);
            });
        }

        select.addEventListener('change', function () {
            const value = this.value;
            this.value = '';
            if (value === '__other__') {
                otherDiv.style.display = 'block';
                otherInput.focus();
                return;
            }
            if (value && !systems.includes(value)) systems.push(value);
            sync();
            render();
        });

        function addOther() {
            const value = otherInput.value.trim();
            if (value && !systems.includes(value)) systems.push(value);
            otherInput.value = '';
            otherDiv.style.display = 'none';
            sync();
            render();
        }

        otherInput.addEventListener('change', addOther);
        otherInput.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); addOther(); } });
        sync();
        render();
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
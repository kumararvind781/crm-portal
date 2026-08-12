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

$pageTitle       = 'Edit Company';
$pageDescription = 'Update company information.';

// All systems (including manually added in past)
$systems = fetch_all('SELECT system_name FROM master_systems ORDER BY system_name');

// Systems already used by this company
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

            <!-- Company Information -->
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

            <div class="full">
                <label>Company Address</label>
                <textarea name="address" rows="3" class="form-control"><?= esc($company['address'] ?? ''); ?></textarea>
            </div>

            <div>
                <label>City</label>
                <input type="text" name="city" class="form-control" value="<?= esc($company['city'] ?? ''); ?>">
            </div>

            <div>
                <label>State</label>
                <input type="text" name="state" class="form-control" value="<?= esc($company['state'] ?? ''); ?>">
            </div>

            <div>
                <label>Country</label>
                <input type="text" name="country" class="form-control" value="<?= esc($company['country'] ?? ''); ?>">
            </div>

            <div>
                <label>Pincode</label>
                <input type="text" name="pincode" class="form-control" value="<?= esc($company['pincode'] ?? ''); ?>">
            </div>

            <!-- Business Information -->
            <hr class="full">
            <div class="section-title full">Business Information</div>

            <div>
                <label>Website</label>
                <input type="url" name="website" class="form-control" value="<?= esc($company['website'] ?? ''); ?>">
            </div>

            <div>
                <label>LinkedIn</label>
                <input type="url" name="linkedin" class="form-control" value="<?= esc($company['linkedin'] ?? ''); ?>">
            </div>

            <div>
                <label>Company Email</label>
                <input type="email" name="company_email" class="form-control"
                       value="<?= esc($company['company_email'] ?? ''); ?>">
            </div>

            <div>
                <label>Company Phone</label>
                <input type="text" name="company_phone" class="form-control"
                       value="<?= esc($company['company_phone'] ?? ''); ?>">
            </div>

            <!-- Systems Used -->
            <div class="full">
                <label>Systems Used</label>

                <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                    <select id="system_select" class="form-control" style="flex:1;">
                        <option value="">Select System</option>
                        <?php foreach ($systems as $row) { ?>
                            <option value="<?= esc($row['system_name']); ?>">
                                <?= esc($row['system_name']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <button type="button" id="addSystemBtn" class="btn btn-outline">
                        + Add System
                    </button>
                </div>

                <div id="addSystemDiv" style="display:none;margin-bottom:10px;">
                    <input type="text" id="addSystemInput" name="other_system" class="form-control"
                           placeholder="Enter new system name">
                    <small style="display:block;margin-top:4px;color:#666;">
                        Type name and press Enter or click “Add New System”.
                    </small>
                    <button type="button" id="confirmAddSystem" class="btn btn-primary" style="margin-top:6px;">
                        Add New System
                    </button>
                </div>

                <input type="hidden" name="systems_used" id="systems_used"
                       value="<?= esc(implode(',', $selectedSystems)); ?>">

                <div id="selectedSystems" style="margin-top:10px;"></div>
            </div>

            <!-- Sales Information -->
            <hr class="full">
            <div class="section-title full">Sales Information</div>

            <div>
                <label>Prospect Level</label>
                <select name="prospect" class="form-control">
                    <?php foreach (['Hot', 'Warm', 'Cold'] as $item): ?>
                        <option value="<?= $item; ?>" <?= (($company['prospect'] ?? '') === $item) ? 'selected' : ''; ?>>
                            <?= $item; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Lead Source</label>
                <select name="lead_source" class="form-control">
                    <option value="">Select</option>
                    <?php foreach (['Reference', 'Website', 'LinkedIn', 'Cold Call', 'Email', 'Exhibition', 'Other'] as $item): ?>
                        <option value="<?= esc($item); ?>" <?= (($company['lead_source'] ?? '') === $item) ? 'selected' : ''; ?>>
                            <?= esc($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Assigned To</label>
                <select name="assigned_to" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach (fetch_all('SELECT id,name FROM users ORDER BY name') as $user): ?>
                        <option value="<?= (int) $user['id']; ?>" <?= ((int) ($company['assigned_to'] ?? 0) === (int) $user['id']) ? 'selected' : ''; ?>>
                            <?= esc($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Status</label>
                <select name="status" class="form-control">
                    <?php foreach (['Active', 'Pending', 'Inactive'] as $item): ?>
                        <option value="<?= $item; ?>" <?= (($company['status'] ?? '') === $item) ? 'selected' : ''; ?>>
                            <?= $item; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full">
                <label>Remarks</label>
                <textarea name="remarks" rows="5" class="form-control"><?= esc($company['remarks'] ?? ''); ?></textarea>
            </div>

            <div class="full form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Update Company
                </button>
                <a href="<?= BASE_URL ?>modules/company_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const systemSelect     = document.getElementById('system_select');
    const systemsInput     = document.getElementById('systems_used');
    const selectedBox      = document.getElementById('selectedSystems');
    const addSystemBtn     = document.getElementById('addSystemBtn');
    const addSystemDiv     = document.getElementById('addSystemDiv');
    const addSystemInput   = document.getElementById('addSystemInput');
    const confirmAddSystem = document.getElementById('confirmAddSystem');

    let systems = systemsInput.value
        .split(',')
        .map(function (v) { return v.trim(); })
        .filter(function (v) { return v !== ''; });

    systems = Array.from(new Set(systems));

    function syncHidden() {
        systemsInput.value = systems.join(',');
    }

    function renderTags() {
        selectedBox.innerHTML = '';
        systems.forEach(function (name) {
            const tag = document.createElement('span');
            tag.style.cssText =
                'display:inline-flex;align-items:center;gap:6px;' +
                'margin:4px;padding:6px 10px;border-radius:16px;' +
                'background:#1677ff;color:#fff;font-size:13px;';

            const text = document.createElement('span');
            text.textContent = name;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.style.cssText =
                'border:0;background:transparent;color:#fff;' +
                'cursor:pointer;font-size:16px;line-height:1;';

            remove.addEventListener('click', function () {
                systems = systems.filter(function (item) {
                    return item !== name;
                });
                syncHidden();
                renderTags();
            });

            tag.appendChild(text);
            tag.appendChild(remove);
            selectedBox.appendChild(tag);
        });
    }

    // Choose existing system from dropdown
    systemSelect.addEventListener('change', function () {
        const value = this.value.trim();
        this.value = '';

        if (!value) {
            return;
        }

        if (!systems.includes(value)) {
            systems.push(value);
            syncHidden();
            renderTags();
        }
    });

    // Show add-system input
    addSystemBtn.addEventListener('click', function () {
        addSystemDiv.style.display = 'block';
        addSystemInput.focus();
    });

    // Add new system name locally (for this company, and save.php will insert into DB)
function addNewSystemName() {
    const value = addSystemInput.value.trim();
    if (!value) {
        return;
    }

    if (!systems.includes(value)) {
        systems.push(value);
    }

    // IMPORTANT: send the new system to PHP via other_system field
    const otherField = document.querySelector('input[name="other_system"]');
    if (otherField) {
        otherField.value = value;
    }

    addSystemInput.value  = '';
    addSystemDiv.style.display = 'none';

    syncHidden();
    renderTags();
} 
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
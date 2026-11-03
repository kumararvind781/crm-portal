<?php

require_once __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$pageTitle = 'Bulk Upload & Export';
$pageDescription = 'Import and export companies and clients using one Excel workbook.';

// Session is already started by the existing CRM config.php.
if (empty($_SESSION['bulk_upload_csrf'])) {
    $_SESSION['bulk_upload_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['bulk_upload_csrf'];


function bu_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function bu_norm($v): string {
    $v = trim((string)$v);
    $v = preg_replace('/\s+/u', ' ', $v);
    return mb_strtolower($v, 'UTF-8');
}
function bu_col_index(string $cellRef): int {
    preg_match('/^[A-Z]+/', strtoupper($cellRef), $m);
    $letters = $m[0] ?? 'A';
    $n = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $n = $n * 26 + (ord($letters[$i]) - 64);
    }
    return max(0, $n - 1);
}
function bu_read_xlsx(string $file): array {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive extension is not enabled on the server.');
    }
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        throw new RuntimeException('Unable to open the Excel workbook.');
    }

    $shared = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $ss = @simplexml_load_string($ssXml);
        if ($ss) {
            foreach ($ss->si as $si) {
                $txt = '';
                if (isset($si->t)) {
                    $txt = (string)$si->t;
                } else {
                    foreach ($si->r as $run) $txt .= (string)$run->t;
                }
                $shared[] = $txt;
            }
        }
    }

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml === false || $relsXml === false) {
        $zip->close();
        throw new RuntimeException('Invalid Excel workbook structure.');
    }
    $wb = @simplexml_load_string($workbookXml);
    $rels = @simplexml_load_string($relsXml);
    if (!$wb || !$rels) {
        $zip->close();
        throw new RuntimeException('Could not read workbook sheet definitions.');
    }

    $relMap = [];
    foreach ($rels->Relationship as $rel) {
        $id = (string)$rel['Id'];
        $target = (string)$rel['Target'];
        $target = ltrim($target, '/');
        if (strpos($target, 'xl/') !== 0) $target = 'xl/' . $target;
        $relMap[$id] = $target;
    }

    $result = [];
    $ns = $wb->getNamespaces(true);
    $rNs = $ns['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    foreach ($wb->sheets->sheet as $sheet) {
        $name = (string)$sheet['name'];
        $attrs = $sheet->attributes($rNs);
        $rid = (string)($attrs['id'] ?? '');
        if (!$rid || empty($relMap[$rid])) continue;
        $sheetXml = $zip->getFromName($relMap[$rid]);
        if ($sheetXml === false) continue;
        $sx = @simplexml_load_string($sheetXml);
        if (!$sx || !isset($sx->sheetData->row)) continue;

        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                $idx = bu_col_index($ref);
                $type = (string)$cell['t'];
                $value = '';
                if ($type === 's') {
                    $si = (int)$cell->v;
                    $value = $shared[$si] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($cell->is->t) ? (string)$cell->is->t : '';
                } else {
                    $value = isset($cell->v) ? (string)$cell->v : '';
                }
                $values[$idx] = trim((string)$value);
            }
            if ($values) {
                $max = max(array_keys($values));
                $line = array_fill(0, $max + 1, '');
                foreach ($values as $i => $v) $line[$i] = $v;
                $rows[] = $line;
            }
        }
        $result[$name] = $rows;
    }
    $zip->close();
    return $result;
}
function bu_sheet_records(array $rows): array {
    if (!$rows) return [];
    $headers = array_map(static fn($v) => strtolower(trim((string)$v)), array_shift($rows));
    $records = [];
    foreach ($rows as $row) {
        $record = [];
        foreach ($headers as $i => $key) {
            if ($key !== '') $record[$key] = trim((string)($row[$i] ?? ''));
        }
        if (count(array_filter($record, static fn($v) => $v !== '')) > 0) $records[] = $record;
    }
    return $records;
}
function bu_val(array $r, string $key, string $default = ''): string {
    return trim((string)($r[$key] ?? $default));
}
function bu_allowed_enum(string $value, array $allowed, string $default): string {
    return in_array($value, $allowed, true) ? $value : $default;
}

$message = '';
$errors = [];
$stats = ['companies_added' => 0, 'companies_skipped' => 0, 'clients_added' => 0, 'clients_skipped' => 0];

if (isset($_GET['export'])) {
    $type = (string)$_GET['export'];
    if (!in_array($type, ['companies', 'clients', 'all'], true)) {
        http_response_code(400);
        exit('Invalid export type.');
    }

    if ($type === 'companies') {
        $sql = "SELECT id, company_name, address, city, state, country, pincode, website, company_email, company_phone, linkedin, industry, systems_used, prospect, lead_source, assigned_to, status, remarks, created_by, created_at FROM companies ORDER BY id";
        $filename = 'unire_crm_companies_' . date('Ymd_His') . '.csv';
    } elseif ($type === 'clients') {
        $sql = "SELECT c.id, c.name, c.salutation, c.first_name, c.last_name, c.designation, c.person_address, c.company_id, co.company_name AS company, c.person_city, c.person_country, c.email_id, c.business_email, c.phone_number, c.business_phone, c.whatsapp_number, c.photo, c.linkedin_profile_url, c.visiting_card, c.events_met_at, c.referred_by_name, c.referred_by_company, c.referred_by_number, c.referred_by_email, c.phone, c.email, c.status, c.assigned_to, c.notes, c.created_at FROM clients c LEFT JOIN companies co ON co.id=c.company_id ORDER BY c.id";
        $filename = 'unire_crm_clients_' . date('Ymd_His') . '.csv';
    } else {
        $sql = "SELECT co.id AS company_id, co.company_name, co.address AS company_address, co.city AS company_city, co.state AS company_state, co.country AS company_country, co.pincode, co.website, co.company_email, co.company_phone, co.industry, c.id AS client_id, c.name AS client_name, c.designation, c.email_id, c.business_email, c.phone, c.phone_number, c.business_phone, c.whatsapp_number, c.status AS client_status FROM companies co LEFT JOIN clients c ON c.company_id=co.id ORDER BY co.id, c.id";
        $filename = 'unire_crm_companies_clients_' . date('Ymd_His') . '.csv';
    }

    $stmt = $pdo->query($sql);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM helps Excel display text correctly.
    fwrite($out, "\xEF\xBB\xBF");
    $first = true;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($first) {
            fputcsv($out, array_keys($row));
            $first = false;
        }
        fputcsv($out, array_values($row));
    }
    if ($first) fputcsv($out, ['No records']);
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_workbook'])) {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Security token expired. Refresh the page and try again.';
    } elseif (!isset($_FILES['workbook']) || ($_FILES['workbook']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid .xlsx workbook.';
    } else {
        $file = $_FILES['workbook'];
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $errors[] = 'Only .xlsx Excel workbooks are supported.';
        } elseif (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            $errors[] = 'Maximum upload size is 10 MB.';
        } else {
            try {
                $sheets = bu_read_xlsx($file['tmp_name']);
                $companySheet = $sheets['Companies'] ?? $sheets['companies'] ?? $sheets['Sheet1'] ?? null;
                $clientSheet = $sheets['Clients'] ?? $sheets['clients'] ?? null;
                if ($companySheet === null || $clientSheet === null) {
                    throw new RuntimeException('Workbook must contain two sheets named Companies and Clients. Download the template below.');
                }

                $companyRows = bu_sheet_records($companySheet);
                $clientRows = bu_sheet_records($clientSheet);
                if (!$companyRows && !$clientRows) {
                    throw new RuntimeException('Both sheets are empty. Add data below the header row and upload again.');
                }

                $pdo->beginTransaction();

                // Existing company lookup (case-insensitive after normalization).
                $companyMap = [];
                $existing = $pdo->query("SELECT id, company_name FROM companies")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($existing as $co) $companyMap[bu_norm($co['company_name'])] = (int)$co['id'];

                $companyInsert = $pdo->prepare("
                    INSERT INTO companies
                    (company_name, address, city, state, country, pincode, website, company_email, company_phone, linkedin, industry, systems_used, prospect, lead_source, assigned_to, status, remarks, created_by)
                    VALUES
                    (:company_name, :address, :city, :state, :country, :pincode, :website, :company_email, :company_phone, :linkedin, :industry, :systems_used, :prospect, :lead_source, :assigned_to, :status, :remarks, :created_by)
                ");

                $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
                foreach ($companyRows as $i => $r) {
                    $companyName = bu_val($r, 'company_name');
                    if ($companyName === '') {
                        $stats['companies_skipped']++;
                        $errors[] = 'Companies row ' . ($i + 2) . ': company_name is required.';
                        continue;
                    }
                    $key = bu_norm($companyName);
                    if (isset($companyMap[$key])) {
                        $stats['companies_skipped']++;
                        continue;
                    }
                    $prospect = bu_allowed_enum(bu_val($r, 'prospect', 'Warm'), ['Hot', 'Warm', 'Cold'], 'Warm');
                    $status = bu_allowed_enum(bu_val($r, 'status', 'Active'), ['Active', 'Pending', 'Inactive'], 'Active');
                    $assigned = bu_val($r, 'assigned_to');
                    $assigned = ($assigned !== '' && ctype_digit($assigned)) ? (int)$assigned : null;
                    $createdBy = bu_val($r, 'created_by');
                    $createdBy = ($createdBy !== '' && ctype_digit($createdBy)) ? (int)$createdBy : ($currentUserId ?: null);

                    $companyInsert->execute([
                        ':company_name' => $companyName,
                        ':address' => bu_val($r, 'address') ?: null,
                        ':city' => bu_val($r, 'city') ?: null,
                        ':state' => bu_val($r, 'state', bu_val($r, 'state/province')) ?: null,
                        ':country' => bu_val($r, 'country') ?: null,
                        ':pincode' => bu_val($r, 'pincode') ?: null,
                        ':website' => bu_val($r, 'website') ?: null,
                        ':company_email' => bu_val($r, 'company_email') ?: null,
                        ':company_phone' => bu_val($r, 'company_phone') ?: null,
                        ':linkedin' => bu_val($r, 'linkedin') ?: null,
                        ':industry' => bu_val($r, 'industry') ?: null,
                        ':systems_used' => bu_val($r, 'systems_used') ?: null,
                        ':prospect' => $prospect,
                        ':lead_source' => bu_val($r, 'lead_source') ?: null,
                        ':assigned_to' => $assigned,
                        ':status' => $status,
                        ':remarks' => bu_val($r, 'remarks') ?: null,
                        ':created_by' => $createdBy,
                    ]);
                    $newId = (int)$pdo->lastInsertId();
                    $companyMap[$key] = $newId;
                    $stats['companies_added']++;
                }

                $clientInsert = $pdo->prepare("
                    INSERT INTO clients
                    (name, salutation, first_name, last_name, designation, person_address, company_id, person_city, person_country, email_id, business_email, phone_number, business_phone, whatsapp_number, photo, linkedin_profile_url, visiting_card, events_met_at, referred_by_name, referred_by_company, referred_by_number, referred_by_email, company, phone, email, status, assigned_to, notes)
                    VALUES
                    (:name, :salutation, :first_name, :last_name, :designation, :person_address, :company_id, :person_city, :person_country, :email_id, :business_email, :phone_number, :business_phone, :whatsapp_number, :photo, :linkedin_profile_url, :visiting_card, :events_met_at, :referred_by_name, :referred_by_company, :referred_by_number, :referred_by_email, :company, :phone, :email, :status, :assigned_to, :notes)
                ");
                $dupeCheck = $pdo->prepare("
                    SELECT id FROM clients
                    WHERE company_id = :company_id
                      AND (phone = :phone OR (:email1 <> '' AND (email = :email2 OR email_id = :email3)))
                    LIMIT 1
                ");

                foreach ($clientRows as $i => $r) {
                    $companyName = bu_val($r, 'company');
                    $clientName = bu_val($r, 'name');
                    if ($clientName === '') {
                        $clientName = trim(bu_val($r, 'first_name') . ' ' . bu_val($r, 'last_name'));
                    }
                    $phone = bu_val($r, 'phone', bu_val($r, 'phone_number', bu_val($r, 'business_phone')));
                    if ($companyName === '' || $clientName === '' || $phone === '') {
                        $stats['clients_skipped']++;
                        $errors[] = 'Clients row ' . ($i + 2) . ': name, company and phone are required.';
                        continue;
                    }
                    $companyId = $companyMap[bu_norm($companyName)] ?? null;
                    if (!$companyId) {
                        $stats['clients_skipped']++;
                        $errors[] = 'Clients row ' . ($i + 2) . ': company "' . $companyName . '" was not found in the Companies sheet or database.';
                        continue;
                    }
                    $email = bu_val($r, 'email', bu_val($r, 'email_id'));
                    $dupeCheck->execute([
                        ':company_id' => $companyId,
                        ':phone' => $phone,
                        ':email1' => $email,
                        ':email2' => $email,
                        ':email3' => $email
                    ]);
                    if ($dupeCheck->fetchColumn()) {
                        $stats['clients_skipped']++;
                        continue;
                    }

                    $status = bu_allowed_enum(bu_val($r, 'status', 'Active'), ['Active', 'Pending', 'Qualified', 'Inactive'], 'Active');
                    $assigned = bu_val($r, 'assigned_to');
                    $assigned = ($assigned !== '' && ctype_digit($assigned)) ? (int)$assigned : null;

                    $clientInsert->execute([
                        ':name' => $clientName,
                        ':salutation' => bu_val($r, 'salutation') ?: null,
                        ':first_name' => bu_val($r, 'first_name') ?: null,
                        ':last_name' => bu_val($r, 'last_name') ?: null,
                        ':designation' => bu_val($r, 'designation') ?: null,
                        ':person_address' => bu_val($r, 'person_address') ?: null,
                        ':company_id' => $companyId,
                        ':person_city' => bu_val($r, 'person_city') ?: null,
                        ':person_country' => bu_val($r, 'person_country') ?: null,
                        ':email_id' => bu_val($r, 'email_id', $email) ?: null,
                        ':business_email' => bu_val($r, 'business_email') ?: null,
                        ':phone_number' => bu_val($r, 'phone_number', $phone) ?: null,
                        ':business_phone' => bu_val($r, 'business_phone') ?: null,
                        ':whatsapp_number' => bu_val($r, 'whatsapp_number') ?: null,
                        ':photo' => bu_val($r, 'photo') ?: null,
                        ':linkedin_profile_url' => bu_val($r, 'linkedin_profile_url') ?: null,
                        ':visiting_card' => bu_val($r, 'visiting_card') ?: null,
                        ':events_met_at' => bu_val($r, 'events_met_at') ?: null,
                        ':referred_by_name' => bu_val($r, 'referred_by_name') ?: null,
                        ':referred_by_company' => bu_val($r, 'referred_by_company') ?: null,
                        ':referred_by_number' => bu_val($r, 'referred_by_number') ?: null,
                        ':referred_by_email' => bu_val($r, 'referred_by_email') ?: null,
                        ':company' => $companyName,
                        ':phone' => $phone,
                        ':email' => $email ?: null,
                        ':status' => $status,
                        ':assigned_to' => $assigned,
                        ':notes' => bu_val($r, 'notes') ?: null,
                    ]);
                    $stats['clients_added']++;
                }

                $pdo->commit();
                $message = 'Import completed. Existing duplicate records were skipped.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
}


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <style>
        .bu-wrap{max-width:1120px;margin:28px auto;padding:0 22px}
        .bu-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:22px}
        .bu-head h2{font-size:22px;margin:0 0 6px}
        .bu-head p{margin:0;color:#6b7b77;font-size:14px}
        .bu-card{background:#fff;border:1px solid #e4ece8;border-radius:15px;padding:23px;margin-bottom:18px;box-shadow:0 4px 18px rgba(20,60,45,.035)}
        .bu-card h3{font-size:18px;margin:0 0 8px}
        .bu-help{font-size:13px;color:#6b7b77;line-height:1.6;margin:0 0 17px}
        .bu-drop{border:2px dashed #b8d9cc;border-radius:12px;padding:25px;text-align:center;background:#fbfefc}
        .bu-drop input{display:block;max-width:100%;margin:14px auto 0}
        .bu-btn{display:inline-block;border:0;border-radius:8px;padding:11px 17px;background:#168b68;color:#fff;text-decoration:none;font-weight:600;cursor:pointer;font-size:14px}
        .bu-btn.secondary{background:#eef7f2;color:#137452;border:1px solid #cfe7db}
        .bu-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
        .bu-alert{padding:13px 15px;border-radius:9px;margin:0 0 15px;font-size:14px}
        .bu-success{background:#eaf7ef;color:#17643d}
        .bu-error{background:#fff0ee;color:#9b3028}
        .bu-stats{display:flex;gap:12px;flex-wrap:wrap;margin-top:15px}
        .bu-stat{background:#f7faf8;border:1px solid #e4ece8;border-radius:9px;padding:12px 15px;min-width:145px;font-size:13px;color:#6b7b77}
        .bu-stat strong{display:block;color:#18312c;font-size:20px;margin-bottom:3px}
        .bu-note{font-size:12px;color:#6b7b77;margin-top:13px;line-height:1.6}
        @media(max-width:650px){.bu-wrap{margin:20px auto;padding:0 13px}.bu-head{align-items:flex-start;flex-direction:column}.bu-card{padding:17px}}
    </style>

    <section class="panel">
        <div class="panel-header">
            <h3>Bulk Upload &amp; Export</h3>
        </div>

        <div class="bu-wrap">
            <div class="bu-head">
                <div>
                    <h2>Companies &amp; Clients</h2>
                    <p>Import both records together using one Excel workbook.</p>
                </div>
                <a class="bu-btn secondary" href="?export=all">Export All Data</a>
            </div>

            <?php if ($message): ?>
                <div class="bu-alert bu-success"><?= bu_h($message) ?></div>
                <div class="bu-card">
                    <h3>Import Summary</h3>
                    <div class="bu-stats">
                        <div class="bu-stat"><strong><?= (int)$stats['companies_added'] ?></strong>Companies added</div>
                        <div class="bu-stat"><strong><?= (int)$stats['companies_skipped'] ?></strong>Companies skipped</div>
                        <div class="bu-stat"><strong><?= (int)$stats['clients_added'] ?></strong>Clients added</div>
                        <div class="bu-stat"><strong><?= (int)$stats['clients_skipped'] ?></strong>Clients skipped</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="bu-alert bu-error"><?= bu_h($error) ?></div>
            <?php endforeach; ?>

            <section class="bu-card">
                <h3><i class="fa-solid fa-file-excel"></i> Upload Excel Workbook</h3>
                <p class="bu-help">
                    Select one .xlsx file containing two sheets named <b>Companies</b> and <b>Clients</b>.
                    Companies are processed first, then each client is linked using the company name.
                </p>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= bu_h($csrf) ?>">
                    <div class="bu-drop">
                        <strong>Choose your Unire CRM Excel workbook</strong>
                        <input type="file" name="workbook"
                               accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        <div class="bu-note">Maximum file size: 10 MB. Only .xlsx is supported.</div>
                    </div>
                    <div class="bu-actions">
                        <button class="bu-btn" type="submit" name="import_workbook" value="1">
                            <i class="fa-solid fa-upload"></i> Validate &amp; Import
                        </button>
                        <a href="<?= BASE_URL ?>assets/templates/Unire_CRM_Sample_Data.xlsx"
                           class="bu-btn secondary" download>
                            <i class="fa-solid fa-file-excel"></i> Download Sample Excel
                        </a>
                    </div>
                </form>

                <p class="bu-note">
                    <b>Required client fields:</b> name, company and phone. The company must exist in the Companies
                    sheet or already exist in CRM. Duplicate records are skipped. Invalid rows are reported.
                </p>
            </section>

            <section class="bu-card">
                <h3>Export Existing CRM Data</h3>
                <p class="bu-help">Download current records as CSV files that open directly in Microsoft Excel.</p>
                <div class="bu-actions">
                    <a class="bu-btn secondary" href="?export=companies">Export Companies</a>
                    <a class="bu-btn secondary" href="?export=clients">Export Clients</a>
                    <a class="bu-btn" href="?export=all">Export Companies + Clients</a>
                </div>
            </section>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

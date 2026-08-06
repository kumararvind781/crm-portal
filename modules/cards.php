<?php
require_once __DIR__ . '/../includes/functions.php'; require_login();
$pageTitle='Business Cards'; $pageDescription='Upload, view, and delete client card images.'; $message='';
if (isset($_GET['delete']) && is_admin()) {
    $card = fetch_one('SELECT image_path FROM business_cards WHERE id = ?', [(int)$_GET['delete']]);
    delete_file_if_exists($card['image_path'] ?? null);
    execute_query('DELETE FROM business_cards WHERE id = ?', [(int)$_GET['delete']]);
    redirect_path('modules/cards.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = upload_card_file($_FILES['card_image'] ?? []);
    if ($path) { execute_query('INSERT INTO business_cards (client_id, image_path, uploaded_by) VALUES (?, ?, ?)', [(int)$_POST['client_id'], $path, (int)$_SESSION['user']['id']]); $message='Card uploaded successfully.'; }
    else { $message='Upload failed. Use jpg, jpeg, png, or webp.'; }
}
$clients = fetch_all('SELECT id, name, company FROM clients ORDER BY name ASC');
$cards = fetch_all('SELECT b.*, c.name AS client_name, c.company, u.name AS uploader FROM business_cards b INNER JOIN clients c ON c.id=b.client_id LEFT JOIN users u ON u.id=b.uploaded_by ORDER BY b.id DESC');
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content"><?php include __DIR__ . '/../includes/topbar.php'; ?><section class="crud-layout"><article class="panel form-panel"><div class="panel-header"><h3>Upload Card</h3></div><?php if($message): ?><div class="alert <?= str_contains($message,'successfully') ? 'success-msg' : 'error' ?>"><?= esc($message) ?></div><?php endif; ?><form method="post" enctype="multipart/form-data" class="form-grid"><div class="full"><label>Select Client</label><select name="client_id" required><?php foreach($clients as $client): ?><option value="<?= $client['id'] ?>"><?= esc($client['name'] . ' - ' . $client['company']) ?></option><?php endforeach; ?></select></div><div class="full"><label>Card Image</label><input type="file" name="card_image" accept=".jpg,.jpeg,.png,.webp" required></div><button class="btn btn-primary" type="submit">Upload Card</button></form></article><article class="panel"><div class="panel-header"><h3>Uploaded Cards</h3></div><div class="cards-grid"><?php foreach($cards as $card): ?><div class="card-item"><img src="<?= BASE_URL . esc($card['image_path']) ?>" alt="Business card"><div class="card-meta"><strong><?= esc($card['client_name']) ?></strong><span><?= esc($card['company']) ?></span><small>Uploaded by <?= esc($card['uploader'] ?? 'System') ?></small><?php if(is_admin()): ?><a class="table-action delete" href="<?= BASE_URL ?>modules/cards.php?delete=<?= $card['id'] ?>" onclick="return confirm('Delete this card?')">Delete</a><?php endif; ?></div></div><?php endforeach; ?></div></article></section></main><?php include __DIR__ . '/../includes/footer.php'; ?>

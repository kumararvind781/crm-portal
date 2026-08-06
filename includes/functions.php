<?php
require_once __DIR__ . '/db.php';

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function is_logged_in(): bool { return isset($_SESSION['user']); }
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}
function redirect_path(string $path): void {
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}
function fetch_one(string $sql, array $params = []) {
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetch();
}
function fetch_all(string $sql, array $params = []): array {
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
}
function execute_query(string $sql, array $params = []): bool {
    global $pdo; $stmt = $pdo->prepare($sql); return $stmt->execute($params);
}
function count_table(string $table): int {
    $allowed = ['clients', 'companies','business_cards', 'follow_ups', 'users'];
    if (!in_array($table, $allowed, true)) return 0;
    $row = fetch_one("SELECT COUNT(*) AS total FROM {$table}");
    return (int)($row['total'] ?? 0);
}
function get_dashboard_stats(): array {
    $pending = fetch_one("SELECT COUNT(*) total FROM follow_ups WHERE status = 'Pending'")['total'] ?? 0;
    $completed = fetch_one("SELECT COUNT(*) total FROM follow_ups WHERE status = 'Completed'")['total'] ?? 0;
    $overdue = fetch_one("SELECT COUNT(*) total FROM follow_ups WHERE status = 'Overdue'")['total'] ?? 0;
    return ['clients'=>count_table('clients'),'cards'=>count_table('business_cards'),'pending'=>(int)$pending,'users'=>count_table('users'),'completed'=>(int)$completed,'overdue'=>(int)$overdue];
}
function monthly_client_growth(): array {
    return fetch_all("SELECT DATE_FORMAT(created_at, '%b') AS month_name, MONTH(created_at) AS month_num, COUNT(*) AS total FROM clients WHERE YEAR(created_at)=YEAR(CURDATE()) GROUP BY MONTH(created_at), DATE_FORMAT(created_at, '%b') ORDER BY month_num");
}
function recent_clients(int $limit = 6): array {
    return fetch_all("SELECT c.*, u.name AS assigned_name FROM clients c LEFT JOIN users u ON u.id = c.assigned_to ORDER BY c.id DESC LIMIT {$limit}");
}
function upcoming_followups(int $limit = 5): array {
    return fetch_all("SELECT f.*, c.name AS client_name FROM follow_ups f INNER JOIN clients c ON c.id = f.client_id ORDER BY followup_date ASC LIMIT {$limit}");
}
function status_badge_class(string $status): string {
    return match ($status) {
        'Active', 'Completed', 'Qualified' => 'success',
        'Pending' => 'warning',
        'Overdue', 'Inactive' => 'danger',
        default => 'default',
    };
}
function page_title(): string { return $GLOBALS['pageTitle'] ?? 'Dashboard'; }
function page_description(): string { return $GLOBALS['pageDescription'] ?? 'Track clients, uploads, reminders and team activity in one place.'; }
function active_nav(string $page): string { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
function upload_card_file(array $file): ?string {
    if (($file['error'] ?? 1) !== 0) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) return null;
    $name = 'card_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    $target = __DIR__ . '/../uploads/' . $name;
    if (move_uploaded_file($file['tmp_name'], $target)) return 'uploads/' . $name;
    return null;
}
function is_admin(): bool {
    return in_array($_SESSION['user']['role'] ?? '', ['Super Admin','Manager'], true);
}
function require_admin(): void {
    if (!is_admin()) redirect_path('index.php');
}
function delete_file_if_exists(?string $path): void {
    if (!$path) return;
    $full = __DIR__ . '/../' . ltrim($path, '/');
    if (is_file($full)) @unlink($full);
}

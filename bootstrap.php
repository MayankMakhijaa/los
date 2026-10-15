<?php
declare(strict_types=1);
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) { header('Location: install.php'); exit; }
$config = require $configPath;
date_default_timezone_set($config['timezone'] ?? 'Asia/Kolkata');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_name('loandesk_session');
    session_start();
}
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and your Hostinger database settings.');
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) { http_response_code(419); exit('Invalid or expired form token. Refresh the page and try again.'); }
}
function require_login(): void { if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; } }
function current_user(): array { return ['id' => (int)($_SESSION['user_id'] ?? 0), 'name' => $_SESSION['user_name'] ?? '', 'email' => $_SESSION['user_email'] ?? '', 'role' => $_SESSION['user_role'] ?? 'agent']; }
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = ['message'=>$message,'type'=>$type]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function money($amount): string { return '₹' . number_format((float)$amount, 0); }
function log_activity(PDO $pdo, int $leadId, ?int $userId, string $type, string $description): void {
    $stmt = $pdo->prepare('INSERT INTO activities (lead_id,user_id,activity_type,description) VALUES (?,?,?,?)');
    $stmt->execute([$leadId,$userId,$type,$description]);
}

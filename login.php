<?php
require __DIR__ . '/bootstrap.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]); $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id']; $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email']; $_SESSION['user_role'] = $user['role'];
        header('Location: index.php'); exit;
    }
    $error = 'Email or password is incorrect.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · LoanDesk LOS</title><link rel="stylesheet" href="assets/style.css"></head><body class="auth-page"><main class="auth-card"><div class="brand-mark">LD</div><h1>Welcome back</h1><p class="muted">Sign in to your loan origination workspace.</p><?php if($error):?><div class="alert error"><?=h($error)?></div><?php endif;?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label>Email address<input type="email" name="email" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn primary full">Sign in</button></form><p class="tiny muted">First time here? Run <code>install.php</code> after configuring the database.</p></main></body></html>

<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
session_start();

if (isset($_SESSION['user_id'])) { header('Location: /mcms/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['last_activity'] = time();
            auditLog('LOGIN', 'users', $user['id'], 'Successful login');
            header('Location: /mcms/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — MedCenter</title>
<link rel="stylesheet" href="/mcms/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <svg width="36" height="36" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="9" fill="#2563eb"/><path d="M18 9v18M9 18h18" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>
      <span>MedCenter</span>
    </div>
    <h1 class="login-title">Welcome back</h1>
    <p class="login-sub">Medical Center Management System</p>

    <?php if($timeout): ?>
    <div class="alert alert-warning">Session expired due to inactivity. Please sign in again.</div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group" style="margin-bottom:16px">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group" style="margin-bottom:24px">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Sign in</button>
    </form>

    <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--gray-100);">
      <p class="text-muted" style="text-align:center;margin-bottom:10px;">Demo credentials (password: <strong>password</strong>)</p>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;font-size:12px;">
        <div style="padding:8px;background:var(--gray-50);border-radius:6px;"><strong>admin</strong><br><span class="text-muted">Admin</span></div>
        <div style="padding:8px;background:var(--gray-50);border-radius:6px;"><strong>dr_ahmed</strong><br><span class="text-muted">Doctor</span></div>
        <div style="padding:8px;background:var(--gray-50);border-radius:6px;"><strong>recept1</strong><br><span class="text-muted">Reception</span></div>
      </div>
    </div>
  </div>
</div>
<script src="/mcms/assets/js/main.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (isset($_SESSION['user_id'])) {
    auditLog('LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
}
session_unset();
session_destroy();
header('Location: /mcms/login.php');
exit;

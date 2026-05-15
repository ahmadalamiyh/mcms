<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (isset($_SESSION['user_id'])) {
    header('Location: /mcms/dashboard.php');
} else {
    header('Location: /mcms/login.php');
}
exit;

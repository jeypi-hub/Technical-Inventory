<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/AuditLog.php';
start_session_once();
$role = $_SESSION['user']['role'] ?? 'admin';
if (!empty($_SESSION['user'])) {
    AuditLog::record('auth.logout');
}
$_SESSION = [];
session_destroy();
header('Location: ' . ($role === 'staff' ? 'student_login.php' : 'admin_login.php'));

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/AuditLog.php';
start_session_once();
if (!empty($_SESSION['user'])) {
    AuditLog::record('auth.logout');
}
$_SESSION = [];
session_destroy();
header('Location: student_login.php');

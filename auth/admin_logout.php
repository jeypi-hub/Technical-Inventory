<?php
require_once __DIR__ . '/../config/database.php';
start_session_once();
$role = $_SESSION['user']['role'] ?? 'admin';
$_SESSION = [];
session_destroy();
header('Location: ' . ($role === 'staff' ? 'student_login.php' : 'admin_login.php'));

<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';
start_session_once();

if (!empty($_SESSION['user']['role'])) {
    header('Location: ' . User::dashboardPathForRole((string)$_SESSION['user']['role'], ''));
    exit;
}

header('Location: auth/login.php');
exit;

<?php
require_once __DIR__ . '/../config/database.php';
start_session_once();
$_SESSION = [];
session_destroy();
header('Location: admin_login.php');

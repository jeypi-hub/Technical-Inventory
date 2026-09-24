<?php
session_set_cookie_params(0, '/auth/');
session_start();

// Unset all session variables specific to admin or destroy completely
unset($_SESSION['user_id']);
unset($_SESSION['role']);
unset($_SESSION['name']);
session_destroy();

// Redirect back to your login page
header("Location: admin_login.php");
exit;
?>
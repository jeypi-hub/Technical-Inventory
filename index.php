<?php
require_once __DIR__ . '/config/database.php';
start_session_once();

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    $destination = $role === 'student'
        ? 'student/student_dashboard.php'
        : 'admin/admin_dashboard.php';
    header('Location: ' . $destination);
    exit;
}

header('Location: auth/student_login.php');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ACLC College — Technical Inventory</title>
<link rel="stylesheet" href="assets/css/landing.css">
<?php include __DIR__ . '/includes/pwa_head.php'; ?>
</head>
<body>
<div class="landing" style="display:none;">
</div>

</body>
</html>
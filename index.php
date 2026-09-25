<?php
require_once __DIR__ . '/config/database.php';
start_session_once();

// If a user is already logged in, respect their role redirect
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    header('Location: ' . ($role === 'admin' ? 'admin/admin_dashboard.php' : 'student/student_dashboard.php'));
    exit;
}

// Automatically redirect everyone else straight to the student portal
header('Location: auth/student_login.php');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ACLC College — Technical Inventory</title>
<link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
<div class="landing" style="display:none;">
  <!-- Hidden backup elements -->
</div>

<script>
  // Listen for the 'A' or 'a' key (ensuring user isn't typing in an input field if any exist)
  document.addEventListener('keydown', function(event) {
    if (event.key === 'a' || event.key === 'A') {
      // Prevent redirection if they are typing 'a' into a form field
      if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        return;
      }
      event.preventDefault();
      window.location.href = 'auth/admin_login.php';
    }
  });
</script>
</body>
</html>
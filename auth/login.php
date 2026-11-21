<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/AuditLog.php';
start_session_once();

$error = '';
if (!empty($_SESSION['user']['role'])) {
    header('Location: ' . User::dashboardPathForRole((string)$_SESSION['user']['role']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['user_id'] ?? '');
    $pw = $_POST['password'] ?? '';
    $account = User::findByUserId($id);

    if ($account && in_array($account['role'], ['student', 'faculty'], true) && !User::isValidBorrowerId($id)) {
        AuditLog::record('auth.login.failed', 'user', null, [
            'attempted_user_id' => $id,
            'reason' => 'invalid_borrower_id',
        ]);
        $error = 'Invalid ID format. Use C##-##-####-MAN### (Student) or FAC-### (Faculty).';
    } else {
        $u = User::authenticateAny($id, $pw);
        if (!$u) {
            AuditLog::record('auth.login.failed', 'user', null, ['attempted_user_id' => $id]);
            $error = 'Invalid ID or password.';
        } else {
            AuditLog::record('auth.login.success', 'user', (string)$u['id'], [], $u);
            $_SESSION['user'] = $u;
            header('Location: ' . User::dashboardPathForRole((string)$u['role']));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login — ACLC Technical Equipment Inventory System</title>
  <link rel="stylesheet" href="../assets/css/student_login.css">
  <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>

  <div class="container">

    <div class="left-section">
      <div class="logo-header">
        <div class="logo-row">
          <img src="../assets/images/aclc_logo.png" alt="ACLC Icon" class="logo-icon" />
          <div class="title-text">
            <h1>ACLC</h1>
            <h1>COLLEGE</h1>
          </div>
        </div>
        <div class="divider"></div>
        <div class="location">MANDAUE CITY</div>
      </div>

      <div class="welcome-text">
        <p>Technical Equipment Inventory System</p>
      </div>
    </div>

    <div class="right-section">
      <h2>Portal Login</h2>
      <p style="margin: -14px 0 22px; color: #6b6f80; font-size: 13px; text-align: center;">
        Sign in with your School ID
      </p>
      <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

      <form class="login-form" method="POST">

        <div class="input-group">
          <label for="user_id">ID No.</label>
          <input type="text" id="user_id" name="user_id"
                 placeholder="Enter your School ID"
                 required autofocus
                 value="<?= e($_POST['user_id'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="Enter your password" required />
            <button type="button" id="togglePassword" aria-label="Toggle password visibility">
              <svg id="eyeIcon" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="submit">Login</button>

      </form>
    </div>
  </div>

  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function () {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);

      if (type === 'text') {
        eyeIcon.innerHTML = `
          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
          <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
      } else {
        eyeIcon.innerHTML = `
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        `;
      }
    });
  </script>
</body>
</html>

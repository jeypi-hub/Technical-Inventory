<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requireLogin('student');
$rows = BorrowRequest::forUser((int)$user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Borrow History — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css">
<link rel="stylesheet" href="../assets/css/history.css">
</head>
<body>
<header class="stud-topbar">
  <div class="stud-brand">
    <img src="../assets/images/aclc_logo.png" alt="ACLC" class="brand-logo">
    <div><strong>ACLC COLLEGE</strong><span>MANDAUE CITY</span></div>
  </div>
  <div class="stud-right">
  <a href="student_dashboard.php" class="back-btn">← Back</a>
  <a href="../auth/student_logout.php" class="logout-btn">Logout</a>
</div>
</header>
<main class="stud-main">
  <?php if (!empty($_GET['flash'])): ?><div class="flash"><?= e($_GET['flash']) ?></div><?php endif; ?>
  <div class="card hist-card">
    <h2>My Borrowing Request History</h2>
    <div class="table-wrap">
      <table class="data-table hist">
        <thead>
          <tr><th>ID</th><th>Date &amp; Time</th><th>Equipment</th><th>Serial No.</th><th>Classroom</th><th>Teacher</th><th>Purpose</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
          $dt = $r['returned_at'] ?: $r['approved_at'] ?: $r['requested_at']; ?>
          <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= e(date('M d, Y h:i A', strtotime($dt))) ?></td>
            <td><?= e($r['equipment_name']) ?></td>
            <td><?= e($r['serial_number']) ?></td>
            <td><?= e($r['classroom']) ?></td>
            <td><?= e($r['teacher']) ?></td>
            <td><?= e($r['purpose']) ?></td>
            <td><span class="status <?= e($r['status']) ?>"><?= ucfirst(e($r['status'])) ?></span></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <a class="btn-cancel" href="actions.php?do=cancel&id=<?= (int)$r['id'] ?>"
                   onclick="return confirm('Cancel this pending request?')">Cancel</a>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr><td colspan="9" class="empty">No requests yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>

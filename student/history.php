<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requireLogin('student');
$rows = BorrowRequest::forUser((int)$user['id']);
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('My Borrow History')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css">
<link rel="stylesheet" href="../assets/css/history.css">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<header class="stud-topbar">
  <div class="stud-brand">
    <img src="../assets/images/aclc_logo.png" alt="ACLC" class="brand-logo">
    <div><strong>ACLC COLLEGE</strong><span>MANDAUE CITY</span></div>
  </div>
  <div class="stud-right">
  <a href="student_dashboard.php" class="back-btn">← <?= e(t('Back')) ?></a>
  <a href="../settings.php" class="back-btn"><?= e(t('Settings')) ?></a>
  <a href="../auth/student_logout.php" class="logout-btn"><?= e(t('Logout')) ?></a>
</div>
</header>
<main class="stud-main">
  <?php if (!empty($_GET['flash'])): ?><div class="flash"><?= e(t($_GET['flash'])) ?></div><?php endif; ?>
  <div class="card hist-card">
    <h2><?= e(t('My Borrowing Request History')) ?></h2>
    <div class="table-wrap">
      <table class="data-table hist">
        <thead>
          <tr><th><?= e(t('ID')) ?></th><th><?= e(t('Date & Time')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Serial No.')) ?></th><th><?= e(t('Quantity')) ?></th><th><?= e(t('Classroom')) ?></th><th><?= e(t('Teacher')) ?></th><th><?= e(t('Purpose')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Action')) ?></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
          $dt = $r['returned_at'] ?: $r['approved_at'] ?: $r['requested_at']; ?>
          <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= e(date('M d, Y h:i A', strtotime($dt))) ?></td>
            <td><?= e($r['equipment_name']) ?></td>
            <td><?= e($r['serial_number']) ?></td>
            <td><?= (int)($r['quantity'] ?? 1) ?></td>
            <td><?= e($r['classroom']) ?></td>
            <td><?= e($r['teacher']) ?></td>
            <td><?= e($r['purpose']) ?></td>
            <td><span class="status <?= e($r['status']) ?>"><?= e(t(ucfirst($r['status']))) ?></span></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <a class="btn-cancel" href="actions.php?do=cancel&id=<?= (int)$r['id'] ?>"
                   onclick="return confirm(<?= e(json_encode(t('Cancel this pending request?'))) ?>)"><?= e(t('Cancel')) ?></a>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr><td colspan="10" class="empty"><?= e(t('No requests yet.')) ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>

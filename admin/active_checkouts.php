<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requireStaffOrAdmin();

$active = BorrowRequest::active();
$pageTitle = 'Active Checkouts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Active Checkouts — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=12">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <section class="card active-card active-page-card">
      <div class="card-head">
        <div>
          <h2>Active Checkouts</h2>
          <p class="muted">Review borrowed equipment and mark returned items.</p>
        </div>
      </div>
      <div class="active-list active-page-list">
        <?php if (empty($active)): ?>
          <div class="empty">No active checkouts.</div>
        <?php else: foreach ($active as $a):
          $approvalTime = strtotime($a['approved_at'] ?? $a['requested_at']);
          $isOverdue = (time() - $approvalTime) > (2 * 3600);
        ?>
          <div class="active-row <?= $isOverdue ? 'overdue-row' : '' ?>">
            <div>
              <strong><?= e($a['equipment_name']) ?></strong>
              <small>(SN: <?= e($a['serial_number']) ?>)</small>
              <?php if ($isOverdue): ?>
                <span class="badge-overdue">OVERDUE (&gt; 2 hrs)</span>
              <?php endif; ?>
              <p>Borrowed by: <?= e($a['student_name']) ?> (ID: <?= e($a['student_uid']) ?>) · Room: <?= e($a['classroom']) ?></p>
            </div>
            <div class="return-actions">
              <button class="btn-return" onclick="markReturn(<?= (int)$a['id'] ?>)">Mark as Return</button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
  </main>
</div>

<div class="modal-backdrop" id="returnModal">
  <div class="modal">
    <h3>Inspect Returned Item</h3>
    <p>Select the item's condition:</p>
    <div class="condition-choices">
      <button data-c="good">Returned (Good)</button>
      <button data-c="damaged">Broken</button>
      <button data-c="lost">Lost</button>
    </div>
    <button class="modal-close" onclick="closeModal()">Cancel</button>
  </div>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requireLogin('admin');

$counts   = Equipment::counts();
$pending  = BorrowRequest::countPending();
$borrowing= BorrowRequest::countBorrowing();
$active   = BorrowRequest::active();
$monthly  = BorrowRequest::monthlyActivity((int)date('Y'));
$pageTitle= 'Home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <section class="stat-grid">
      <div class="stat-card"><span class="stat-label">Total Equipment</span><span class="stat-value"><?= (int)$counts['total'] ?></span></div>
      <div class="stat-card"><span class="stat-label">Available</span><span class="stat-value"><?= (int)$counts['available'] ?></span></div>
      <div class="stat-card"><span class="stat-label">Pending</span><span class="stat-value"><?= (int)$pending ?></span></div>
      <div class="stat-card"><span class="stat-label">Borrowing</span><span class="stat-value"><?= (int)$borrowing ?></span></div>
      <div class="stat-card"><span class="stat-label">Maintenance</span><span class="stat-value"><?= (int)$counts['maintenance'] ?></span></div>
    </section>

    <section class="dash-grid">
      <div class="card chart-card">
        <div class="card-head">
          <div>
            <h2>Monthly Equipment Activity (<?= date('Y') ?>)</h2>
            <p class="muted">Overview of item checkouts and returns per month</p>
          </div>
          <div class="legend">
            <span><i class="dot-borrowed"></i>Borrowed</span>
            <span><i class="dot-returned"></i>Returned</span>
          </div>
        </div>
        <canvas id="chart" height="260" data-months='<?= json_encode($monthly) ?>'></canvas>
      </div>

      <div class="card active-card">
        <h2>Active Checkouts</h2>
        <div class="active-list">
          <?php if (empty($active)): ?>
            <div class="empty">No active checkouts.</div>
          <?php else: foreach ($active as $a): 
            // Calculate if more than 2 hours have passed since approval
            $approvalTime = strtotime($a['approved_at'] ?? $a['requested_at']);
            $isOverdue = (time() - $approvalTime) > (2 * 3600); // 2 hours in seconds
          ?>
            <div class="active-row <?= $isOverdue ? 'overdue-row' : '' ?>">
              <div>
                <strong><?= e($a['equipment_name']) ?></strong>
                <small>(SN: <?= e($a['serial_number']) ?>)</small>
                <?php if ($isOverdue): ?>
                  <span class="badge-overdue">OVERDUE (> 2 hrs)</span>
                <?php endif; ?>
                <p>Borrowed by: <?= e($a['student_name']) ?> (ID: <?= e($a['student_uid']) ?>) · Room: <?= e($a['classroom']) ?></p>
              </div>
              <div class="return-actions">
                <button class="btn-return" onclick="markReturn(<?= (int)$a['id'] ?>)">Mark as Return</button>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <!-- Pending Requests -->
        <?php $pendReqs = BorrowRequest::pending(); ?>
        <?php if (!empty($pendReqs)): ?>
        <h2 style="margin-top:22px">Pending Requests</h2>
        <div class="active-list">
          <?php foreach ($pendReqs as $p): ?>
          <div class="active-row pending">
            <div>
              <strong><?= e($p['equipment_name']) ?></strong>
              <small>(SN: <?= e($p['serial_number']) ?>)</small>
              <p><?= e($p['student_name']) ?> · <?= e($p['classroom']) ?> · Purpose: <?= e($p['purpose']) ?></p>
            </div>
            <div class="return-actions">
              <a class="btn-approve" href="actions.php?do=approve&id=<?= (int)$p['id'] ?>">Approve</a>
              <a class="btn-reject"  href="actions.php?do=reject&id=<?= (int)$p['id'] ?>">Reject</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

<!-- Return condition modal -->
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
<script src="../assets/js/charts.js"></script>
</body>
</html>

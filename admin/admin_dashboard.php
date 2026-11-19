<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requireStaffOrAdmin();

$counts   = Equipment::counts();
$pending  = BorrowRequest::countPending();
$borrowing= BorrowRequest::countBorrowing();
$monthly  = BorrowRequest::monthlyActivity((int)date('Y'));
$pageTitle= 'Home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=8">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
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
    </section>

    <?php $pendReqs = BorrowRequest::pending(); ?>
    <?php if (!empty($pendReqs)): ?>
    <section class="card pending-card">
      <div class="card-head">
        <div>
          <h2>Pending Requests</h2>
          <p class="muted">Review equipment requests waiting for approval.</p>
        </div>
      </div>
      <div class="active-list">
        <?php foreach ($pendReqs as $p): ?>
        <div class="active-row pending">
          <div>
            <strong><?= e($p['equipment_name']) ?></strong>
            <small>(SN: <?= e($p['serial_number']) ?>)</small>
            <small> · Quantity: <?= (int)($p['quantity'] ?? 1) ?></small>
            <p><?= e($p['student_name']) ?> · <?= e($p['classroom']) ?> · Purpose: <?= e($p['purpose']) ?></p>
          </div>
          <div class="return-actions">
            <a class="btn-approve" href="actions.php?do=approve&id=<?= (int)$p['id'] ?>">Approve</a>
            <a class="btn-reject" href="actions.php?do=reject&id=<?= (int)$p['id'] ?>">Reject</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

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

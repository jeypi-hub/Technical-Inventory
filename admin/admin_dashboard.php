<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('checkout.manage');

$counts   = Equipment::counts();
$pending  = BorrowRequest::countPending();
$borrowing= BorrowRequest::countBorrowing();
$monthly  = BorrowRequest::monthlyActivity((int)date('Y'));
$pageTitle= 'Home';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Admin Dashboard')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=12">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <section class="stat-grid">
      <div class="stat-card"><span class="stat-label"><?= e(t('Total Equipment')) ?></span><span class="stat-value"><?= (int)$counts['total'] ?></span></div>
      <div class="stat-card"><span class="stat-label"><?= e(t('Available')) ?></span><span class="stat-value"><?= (int)$counts['available'] ?></span></div>
      <div class="stat-card"><span class="stat-label"><?= e(t('Pending')) ?></span><span class="stat-value"><?= (int)$pending ?></span></div>
      <div class="stat-card"><span class="stat-label"><?= e(t('Borrowing')) ?></span><span class="stat-value"><?= (int)$borrowing ?></span></div>
      <div class="stat-card"><span class="stat-label"><?= e(t('Maintenance')) ?></span><span class="stat-value"><?= (int)$counts['maintenance'] ?></span></div>
    </section>

    <section class="dash-grid">
      <div class="card chart-card">
        <div class="card-head">
          <div>
            <h2><?= e(t('Monthly Equipment Activity')) ?> (<?= date('Y') ?>)</h2>
            <p class="muted"><?= e(t('Overview of item checkouts and returns per month')) ?></p>
          </div>
          <div class="legend">
            <span><i class="dot-borrowed"></i><?= e(t('Borrowed')) ?></span>
            <span><i class="dot-returned"></i><?= e(t('Returned')) ?></span>
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
          <h2><?= e(t('Pending Requests')) ?></h2>
          <p class="muted"><?= e(t('Review equipment requests waiting for approval.')) ?></p>
        </div>
      </div>
      <div class="active-list">
        <?php foreach ($pendReqs as $p): ?>
        <div class="active-row pending">
          <div>
            <strong><?= e($p['equipment_name']) ?></strong>
            <small>(<?= e(t('SN:')) ?> <?= e($p['serial_number']) ?>)</small>
            <small> · <?= e(t('Quantity:')) ?> <?= (int)($p['quantity'] ?? 1) ?></small>
            <p><?= e($p['student_name']) ?> · <?= e($p['classroom']) ?> · <?= e(t('Purpose:')) ?> <?= e($p['purpose']) ?></p>
          </div>
          <div class="return-actions">
            <a class="btn-approve" href="actions.php?do=approve&id=<?= (int)$p['id'] ?>"><?= e(t('Approve')) ?></a>
            <a class="btn-reject" href="actions.php?do=reject&id=<?= (int)$p['id'] ?>"><?= e(t('Reject')) ?></a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

<div class="modal-backdrop" id="returnModal">
  <div class="modal">
    <h3><?= e(t('Inspect Returned Item')) ?></h3>
    <p><?= e(t("Select the item's condition:")) ?></p>
    <div class="condition-choices">
      <button data-c="good"><?= e(t('Returned (Good)')) ?></button>
      <button data-c="damaged"><?= e(t('Broken')) ?></button>
      <button data-c="lost"><?= e(t('Lost')) ?></button>
    </div>
    <button class="modal-close" onclick="closeModal()"><?= e(t('Cancel')) ?></button>
  </div>
</div>

<script src="../assets/js/admin.js"></script>
<script src="../assets/js/charts.js"></script>
</body>
</html>

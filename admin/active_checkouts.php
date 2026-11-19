<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('checkout.manage');

$active = BorrowRequest::active();
$pageTitle = 'Active Checkouts';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Active Checkouts')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=8">
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
          <h2><?= e(t('Active Checkouts')) ?></h2>
          <p class="muted"><?= e(t('Review borrowed equipment and mark returned items.')) ?></p>
        </div>
      </div>
      <div class="active-list active-page-list">
        <?php if (empty($active)): ?>
          <div class="empty"><?= e(t('No active checkouts.')) ?></div>
        <?php else: foreach ($active as $a):
          $approvalTime = strtotime($a['approved_at'] ?? $a['requested_at']);
          $isOverdue = (time() - $approvalTime) > (2 * 3600);
        ?>
          <div class="active-row <?= $isOverdue ? 'overdue-row' : '' ?>">
            <div>
              <strong><?= e($a['equipment_name']) ?></strong>
              <small>(<?= e(t('SN:')) ?> <?= e($a['serial_number']) ?>)</small>
              <?php if ($isOverdue): ?>
                <span class="badge-overdue"><?= e(t('OVERDUE (> 2 hrs)')) ?></span>
              <?php endif; ?>
              <p><?= e(t('Borrowed by:')) ?> <?= e($a['student_name']) ?> (<?= e(t('ID:')) ?> <?= e($a['student_uid']) ?>) · <?= e(t('Room:')) ?> <?= e($a['classroom']) ?></p>
            </div>
            <div class="return-actions">
              <button class="btn-return" onclick="markReturn(<?= (int)$a['id'] ?>)"><?= e(t('Mark as Return')) ?></button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
  </main>
</div>

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
</body>
</html>

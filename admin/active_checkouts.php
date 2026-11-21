<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('checkout.manage');

$active = BorrowRequest::active();
$awaitingScan = [];
$inProgress = [];
foreach ($active as $row) {
    if ($row['status'] === 'approved') {
        $awaitingScan[] = $row;
    } else {
        $inProgress[] = $row;
    }
}
$pageTitle = 'Active Checkouts';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Active Checkouts')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=16">
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
          <h2><?= e(t('Approved — Awaiting Student QR Scan')) ?> (<?= count($awaitingScan) ?>)</h2>
          <p class="muted"><?= e(t('Items reserved as Pending. The student has 10 minutes after approval to scan the QR code before the reservation automatically expires back to Available.')) ?></p>
        </div>
      </div>
      <div class="active-list active-page-list">
        <?php if (empty($awaitingScan)): ?>
          <div class="empty"><?= e(t('No approved requests awaiting QR code scan.')) ?></div>
        <?php else: foreach ($awaitingScan as $aw):
          $durLabel = BorrowRequest::formatDuration((int)($aw['duration_minutes'] ?? 60));
          $pickupExpiresTs = strtotime($aw['approved_at'] ?? 'now') + BorrowRequest::PICKUP_EXPIRY_SECONDS;
          $qrPayload = [
              'id' => (int)$aw['id'],
              'qr_token' => (string)($aw['qr_token'] ?? ''),
              'equipment_name' => (string)$aw['equipment_name'],
              'serial_number' => (string)$aw['serial_number'],
              'quantity' => (int)($aw['quantity'] ?? 1),
              'student_name' => (string)$aw['student_name'],
              'student_uid' => (string)$aw['student_uid'],
              'duration_label' => $durLabel,
              'pickup_expires_at' => date('c', $pickupExpiresTs),
          ];
        ?>
          <div id="req-<?= (int)$aw['id'] ?>" class="active-row awaiting-qr-row <?= ((int)($_GET['focus'] ?? 0) === (int)$aw['id']) ? 'focus-highlight' : '' ?>">
            <div>
              <strong><?= e($aw['equipment_name']) ?></strong>
              <small>(<?= e(t('SN:')) ?> <?= e($aw['serial_number']) ?>)</small>
              <small> · <?= e(t('Qty:')) ?> <?= (int)($aw['quantity'] ?? 1) ?></small>
              <span class="badge-duration"><?= e(t('Set Duration:')) ?> <?= e($durLabel) ?></span>
              <p>
                <?= e(t('Borrower:')) ?> <strong><?= e($aw['student_name']) ?></strong> (<?= e(t('ID:')) ?> <?= e($aw['student_uid']) ?>)
                · <?= e(t('Room:')) ?> <?= e($aw['classroom']) ?>
                · <?= e(t('Code:')) ?> <code><?= e($aw['qr_token'] ?? '') ?></code>
                · <strong class="pickup-expiry-timer" data-pickup-expires-at="<?= e(date('c', $pickupExpiresTs)) ?>">
                    <?= e(t('Pickup expires:')) ?> <?= e(date('h:i:s A', $pickupExpiresTs)) ?>
                  </strong>
              </p>
            </div>
            <div class="return-actions">
              <button type="button" class="btn-qr"
                      data-qr='<?= e(json_encode($qrPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>'
                      onclick="openQrModal(this)">
                <?= e(t('Show QR Code')) ?>
              </button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>

    <section class="card active-card active-page-card" style="margin-top: 20px;">
      <div class="card-head">
        <div>
          <h2><?= e(t('Active Borrowed & Overdue Checkouts')) ?> (<?= count($inProgress) ?>)</h2>
          <p class="muted"><?= e(t('QR code scanned and borrow timer is running. Items turn Overdue after their set duration and Lost after 24 hours.')) ?></p>
        </div>
      </div>
      <div class="active-list active-page-list">
        <?php if (empty($inProgress)): ?>
          <div class="empty"><?= e(t('No active borrowed checkouts right now.')) ?></div>
        <?php else: foreach ($inProgress as $a):
          $durLabel = BorrowRequest::formatDuration((int)($a['duration_minutes'] ?? 60));
          $dueTs = !empty($a['due_at']) ? strtotime($a['due_at']) : (strtotime($a['borrowed_at'] ?? $a['approved_at'] ?? $a['requested_at']) + ((int)($a['duration_minutes'] ?? 60) * 60));
          $isOverdue = $a['status'] === 'overdue' || (time() > $dueTs);
        ?>
          <div id="req-<?= (int)$a['id'] ?>" class="active-row <?= $isOverdue ? 'overdue-row' : '' ?> <?= ((int)($_GET['focus'] ?? 0) === (int)$a['id']) ? 'focus-highlight' : '' ?>">
            <div>
              <strong><?= e($a['equipment_name']) ?></strong>
              <small>(<?= e(t('SN:')) ?> <?= e($a['serial_number']) ?>)</small>
              <small> · <?= e(t('Qty:')) ?> <?= (int)($a['quantity'] ?? 1) ?></small>
              <span class="badge-duration"><?= e(t('Duration:')) ?> <?= e($durLabel) ?></span>
              <?php if ($isOverdue): ?>
                <span class="badge-overdue"><?= e(t('OVERDUE')) ?></span>
              <?php else: ?>
                <span class="badge-borrowed"><?= e(t('BORROWED')) ?></span>
              <?php endif; ?>
              <p>
                <?= e(t('Borrowed by:')) ?> <strong><?= e($a['student_name']) ?></strong> (<?= e(t('ID:')) ?> <?= e($a['student_uid']) ?>)
                · <?= e(t('Room:')) ?> <?= e($a['classroom']) ?>
                <?php if (!empty($a['borrowed_at'])): ?>
                  · <?= e(t('Scanned:')) ?> <?= e(date('h:i A', strtotime($a['borrowed_at']))) ?>
                <?php endif; ?>
                · <strong class="checkout-timer" data-due-at="<?= e(date('c', $dueTs)) ?>">
                    <?= e(t('Due:')) ?> <?= e(date('h:i A', $dueTs)) ?>
                  </strong>
              </p>
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

<div class="modal-backdrop" id="qrDisplayModal" aria-hidden="true">
  <div class="modal qr-modal">
    <h3 id="qrModalTitle"><?= e(t('Pickup Verification QR Code')) ?></h3>
    <p class="muted"><?= e(t('Ask the borrower to scan this QR code using their portal scanner to start the borrow timer.')) ?></p>
    <div class="qr-svg-box" id="qrModalSvgContainer"></div>
    <div class="qr-meta">
      <div class="qr-token-pill">
        <span><?= e(t('Verification Code')) ?></span>
        <strong id="qrModalTokenText"></strong>
      </div>
      <p id="qrModalDetailsText"></p>
    </div>
    <button type="button" class="btn-return" onclick="closeQrModal()"><?= e(t('Done')) ?></button>
  </div>
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
<script src="../assets/js/qr.js?v=1"></script>
<script src="../assets/js/admin.js?v=15"></script>
</body>
</html>

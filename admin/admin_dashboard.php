<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('checkout.manage');

$counts       = Equipment::counts();
$pendingReqs  = BorrowRequest::pending();
$awaitingScan = BorrowRequest::approvedAwaitingScan();
$monthly      = BorrowRequest::monthlyActivity((int)date('Y'));
$autoOpenQrId = (int)($_GET['qr_id'] ?? 0);
$pageTitle    = 'Home';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Technical Staff & Admin Dashboard')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=16">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <?php if (!empty($_GET['flash'])): ?>
      <div class="flash"><?= e(t($_GET['flash'])) ?></div>
    <?php endif; ?>

    <section class="stat-grid stat-grid-6">
      <div class="stat-card"><span class="stat-label"><?= e(t('Total Equipment')) ?></span><span class="stat-value"><?= (int)$counts['total'] ?></span></div>
      <div class="stat-card stat-available"><span class="stat-label"><?= e(t('Available')) ?></span><span class="stat-value"><?= (int)$counts['available'] ?></span></div>
      <div class="stat-card stat-pending"><span class="stat-label"><?= e(t('Pending')) ?></span><span class="stat-value"><?= (int)$counts['pending'] ?></span></div>
      <div class="stat-card stat-borrowed"><span class="stat-label"><?= e(t('Borrowed')) ?></span><span class="stat-value"><?= (int)$counts['borrowed'] ?></span></div>
      <div class="stat-card stat-overdue"><span class="stat-label"><?= e(t('Overdue')) ?></span><span class="stat-value"><?= (int)$counts['overdue'] ?></span></div>
      <div class="stat-card stat-lost"><span class="stat-label"><?= e(t('Lost')) ?></span><span class="stat-value"><?= (int)$counts['lost'] ?></span></div>
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

    <section class="card pending-card">
      <div class="card-head">
        <div>
          <h2><?= e(t('Pending Borrow Requests')) ?> (<?= count($pendingReqs) ?>)</h2>
          <p class="muted"><?= e(t('Requests submitted in advance. Approving generates a QR code for the student/faculty to scan at the Technical Department.')) ?></p>
        </div>
      </div>
      <?php if (empty($pendingReqs)): ?>
        <div class="empty"><?= e(t('No pending requests waiting for approval.')) ?></div>
      <?php else: ?>
        <div class="active-list">
          <?php
          $focusId = (int)($_GET['focus'] ?? 0);
          foreach ($pendingReqs as $p):
            $durLabel = BorrowRequest::formatDuration((int)($p['duration_minutes'] ?? 60));
            $isFocused = $focusId === (int)$p['id'];
          ?>
          <div id="req-<?= (int)$p['id'] ?>" class="active-row pending <?= $isFocused ? 'focus-highlight' : '' ?>">
            <div>
              <strong><?= e($p['equipment_name']) ?></strong>
              <small>(<?= e(t('SN:')) ?> <?= e($p['serial_number']) ?>)</small>
              <small> · <?= e(t('Qty:')) ?> <?= (int)($p['quantity'] ?? 1) ?></small>
              <span class="badge-duration"><?= e(t('Duration:')) ?> <?= e($durLabel) ?></span>
              <p>
                <strong><?= e($p['student_name']) ?></strong> (<?= e($p['student_uid']) ?> · <?= e($p['course']) ?>)
                · <?= e(t('Room:')) ?> <?= e($p['classroom']) ?>
                · <?= e(t('Teacher:')) ?> <?= e($p['teacher']) ?>
                · <?= e(t('Purpose:')) ?> <?= e($p['purpose']) ?>
              </p>
            </div>
            <div class="return-actions">
              <a class="btn-approve" href="actions.php?do=approve&id=<?= (int)$p['id'] ?>"><?= e(t('Approve & Generate QR')) ?></a>
              <a class="btn-reject" href="actions.php?do=reject&id=<?= (int)$p['id'] ?>"><?= e(t('Reject')) ?></a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if (!empty($awaitingScan)): ?>
    <section class="card pending-card awaiting-scan-card">
      <div class="card-head">
        <div>
          <h2><?= e(t('Approved — Awaiting Student QR Scan')) ?> (<?= count($awaitingScan) ?>)</h2>
          <p class="muted"><?= e(t('Show the QR code to the student/faculty within 10 minutes of approval to start their borrow duration timer. Unclaimed requests automatically expire back to Available.')) ?></p>
        </div>
      </div>
      <div class="active-list">
        <?php
        $focusId = (int)($_GET['focus'] ?? 0);
        foreach ($awaitingScan as $aw):
          $durLabel = BorrowRequest::formatDuration((int)($aw['duration_minutes'] ?? 60));
          $pickupExpiresTs = strtotime($aw['approved_at'] ?? 'now') + BorrowRequest::PICKUP_EXPIRY_SECONDS;
          $isFocused = $focusId === (int)$aw['id'];
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
        <div id="req-<?= (int)$aw['id'] ?>" class="active-row awaiting-qr-row <?= $isFocused ? 'focus-highlight' : '' ?>">
          <div>
            <strong><?= e($aw['equipment_name']) ?></strong>
            <small>(<?= e(t('SN:')) ?> <?= e($aw['serial_number']) ?>)</small>
            <small> · <?= e(t('Qty:')) ?> <?= (int)($aw['quantity'] ?? 1) ?></small>
            <span class="badge-duration"><?= e(t('Duration:')) ?> <?= e($durLabel) ?></span>
            <p>
              <?= e(t('Borrower:')) ?> <strong><?= e($aw['student_name']) ?></strong> (<?= e($aw['student_uid']) ?>)
              · <?= e(t('Code:')) ?> <code><?= e($aw['qr_token'] ?? '') ?></code>
              · <strong class="pickup-expiry-timer" data-pickup-expires-at="<?= e(date('c', $pickupExpiresTs)) ?>">
                  <?= e(t('Pickup expires:')) ?> <?= e(date('h:i:s A', $pickupExpiresTs)) ?>
                </strong>
            </p>
          </div>
          <div class="return-actions">
            <button type="button" class="btn-qr"
                    data-qr='<?= e(json_encode($qrPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>'
                    <?= $autoOpenQrId === (int)$aw['id'] ? 'data-auto-open="1"' : '' ?>
                    onclick="openQrModal(this)">
              <?= e(t('Show QR Code')) ?>
            </button>
            <a class="btn-reject" href="actions.php?do=reject&id=<?= (int)$aw['id'] ?>"
               onclick="return confirm('Cancel this approved pickup and release item back to Available?')"><?= e(t('Cancel')) ?></a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
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
<script src="../assets/js/charts.js"></script>
</body>
</html>

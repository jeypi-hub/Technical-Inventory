<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');
$isFaculty = User::isFaculty($user);
$roleLabel = $isFaculty ? 'Faculty' : 'Student';

if (($_GET['open'] ?? '') === 'borrow') {
    header('Location: borrow_request.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$items  = Equipment::all($search ?: null);
$availableCounts = Equipment::availableCountsByName();
$statusBreakdown = Equipment::statusBreakdownByName();
$notifs = Notification::forUser((int)$user['id'], 10);
$unread = Notification::unreadCount((int)$user['id']);
$flash = $_GET['flash'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t($roleLabel . ' Dashboard')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=40">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body class="has-student-nav" data-borrow-cart-key="<?= e('aclc-borrow-cart-' . (int)$user['id']) ?>">
<?php include __DIR__ . '/includes/topbar.php'; ?>

<main class="stud-main">
  <section class="notif-panel" id="notifPanel" aria-label="<?= e(t('Notifications')) ?>" aria-hidden="true">
    <div class="notif-head">
      <strong><?= e(t('Notifications')) ?></strong>
      <a href="actions.php?do=notif_read" class="mark-read"><?= e(t('Mark all read')) ?></a>
    </div>
    <?php if (empty($notifs)): ?>
      <div class="notif-empty"><?= e(t('No notifications yet.')) ?></div>
    <?php else: foreach ($notifs as $n): ?>
      <a href="actions.php?do=notif_open&id=<?= (int)$n['id'] ?>" class="notif-item <?= $n['is_read']?'':'unread' ?>">
        <span class="ni-type <?= e($n['type']) ?>" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </span>
        <div class="notif-copy">
          <p><?= e(t_notification($n['message'])) ?></p>
          <small><?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?></small>
        </div>
        <?php if (!$n['is_read']): ?><span class="notif-unread-dot" aria-hidden="true"></span><?php endif; ?>
      </a>
    <?php endforeach; endif; ?>
  </section>
  <div class="dashboard-content">
  <?php if ($flash): ?><div class="flash"><?= e(t($flash)) ?></div><?php endif; ?>
  <form method="get" class="searchbar wide">
    <input type="text" name="q" placeholder="<?= e(t('Search equipment or status...')) ?>" value="<?= e($search) ?>">
    <button type="submit">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </button>
  </form>

  <section class="equip-grid">
    <?php foreach ($items as $it):
      $st = $it['status'];
      $isAvailable = $st === 'available';
      $availableCount = $availableCounts[$it['name']] ?? 0;
      $bd = $statusBreakdown[$it['name']] ?? [
          'available' => $availableCount,
          'pending' => 0,
          'borrowed' => 0,
          'overdue' => 0,
          'lost' => 0,
      ];
    ?>
      <article class="equip-card status-card-<?= e($st) ?> <?= $isAvailable ? '' : 'unavailable' ?>"
        data-search="<?= e($it['name'] . ' ' . $it['serial_number'] . ' ' . $st) ?>">
        <button type="button" class="equip-img item-open"
          data-id="<?= (int)$it['id'] ?>"
          data-name="<?= e($it['name']) ?>"
          data-serial="<?= e($it['serial_number']) ?>"
          data-image="<?= !empty($it['image']) ? e('../assets/upload/' . $it['image']) : '' ?>"
          data-status="<?= e($st) ?>"
          data-available="<?= (int)$availableCount ?>"
          data-pending="<?= (int)($bd['pending'] ?? 0) ?>"
          data-borrowed="<?= (int)($bd['borrowed'] ?? 0) ?>"
          data-overdue="<?= (int)($bd['overdue'] ?? 0) ?>"
          data-lost="<?= (int)($bd['lost'] ?? 0) ?>"
          aria-label="<?= e(t('View details for')) ?> <?= e($it['name']) ?>">
          <?php if (!empty($it['image'])): ?>
            <img src="../assets/upload/<?= e($it['image']) ?>" alt="">
          <?php else: ?>
            <div class="placeholder"></div>
          <?php endif; ?>
          <span class="image-status-pill status-pill-<?= e($st) ?>"><?= e(t(ucfirst($st))) ?></span>
          <span class="image-stock"><?= e(str_replace('{count}', (string)$availableCount, t('{count} available'))) ?></span>
          <span class="image-name"><?= e($it['name']) ?></span>
          <span class="image-hint"><?= e(t('Click to view')) ?></span>
        </button>
      </article>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
      <p class="empty"><?= e(t('No equipment yet.')) ?></p>
    <?php endif; ?>
  </section>
  </div>
</main>

<dialog class="item-dialog" id="itemDialog" aria-labelledby="detailName">
  <div class="item-dialog-panel">
    <button type="button" class="dialog-close" aria-label="<?= e(t('Close item details')) ?>">&times;</button>
    <div class="modal-bento">
      <div class="detail-image-tile" id="detailImageTile">
        <img id="detailImage" alt="">
        <span class="detail-stock" id="detailStock"></span>
        <h2 id="detailName"></h2>
      </div>
      <div class="detail-side">
        <div class="detail-stat-tile">
          <span class="detail-label"><?= e(t('Unit Status & Availability Breakdown')) ?></span>
          <strong id="detailStatus"></strong>
          <div class="status-breakdown-pills" id="detailStatusBreakdown"></div>
        </div>
        <div class="request-tile" id="requestTile" hidden>
          <h3><?= e(t('Reserve & Borrow in Advance')) ?></h3>
          <div class="modal-req-form">
            <input type="number" name="quantity" id="requestQuantity" aria-label="<?= e(t('Quantity')) ?>"
              placeholder="<?= e(t('Quantity')) ?>" min="1" value="1" required>
            <button type="button" class="btn-primary" id="addItemToCart"><?= e(t('Add to Request')) ?></button>
          </div>
        </div>
        <div class="request-tile unavailable-tile" id="unavailableTile" hidden>
          <strong id="unavailableTitle"><?= e(t('This unit is currently unavailable.')) ?></strong>
          <span id="unavailableDesc"><?= e(t('Check back later or select an available unit.')) ?></span>
        </div>
      </div>
    </div>
  </div>
</dialog>

<dialog class="item-dialog scanner-dialog" id="headerScannerDialog" aria-labelledby="headerScannerTitle">
  <div class="item-dialog-panel">
    <button type="button" class="dialog-close" id="closeHeaderScanner" aria-label="<?= e(t('Close QR scanner')) ?>">&times;</button>
    <div class="scanner-dialog-content">
      <h2 id="headerScannerTitle"><?= e(t('Scan Staff Approval QR Code')) ?></h2>
      <p><?= e(t('Scan the QR code generated by Technical Staff upon approval to start your borrow duration timer.')) ?></p>
      <div class="qr-camera-frame">
        <video id="headerScannerVideo" autoplay muted playsinline></video>
        <div class="qr-scan-guide" aria-hidden="true"></div>
      </div>
      <p class="qr-scanner-status" id="headerScannerStatus" role="status" aria-live="polite"></p>
      <div class="scanner-result" id="headerScannerResult" hidden>
        <span><?= e(t('Scanned Code')) ?></span>
        <strong id="headerScannerSerial"></strong>
      </div>
      <div class="qr-scanner-actions">
        <button type="button" class="btn-primary qr-retry" id="retryHeaderScanner" hidden><?= e(t('Try camera again')) ?></button>
        <label class="btn-secondary qr-photo-btn" for="qrImageFileInput">
          📷 <?= e(t('Scan from Photo / Camera App')) ?>
          <input type="file" id="qrImageFileInput" accept="image/*" capture="environment" hidden>
        </label>
      </div>

      <form class="manual-qr-form" id="manualQrForm" method="post" action="actions.php">
        <input type="hidden" name="do" value="scan_qr">
        <label for="manualQrToken"><?= e(t('Or enter the Staff QR Verification Code:')) ?></label>
        <div class="manual-qr-row">
          <input type="text" id="manualQrToken" name="qr_token" placeholder="e.g. ACLC-REQ-1-A1B2C3D4" required>
          <button type="submit" class="btn-primary"><?= e(t('Start Timer')) ?></button>
        </div>
      </form>
    </div>
  </div>
</dialog>
<script src="../assets/js/jsqr.min.js?v=1"></script>
<script src="../assets/js/student.js?v=26"></script>
</body>
</html>

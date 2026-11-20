<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');

$search = trim($_GET['q'] ?? '');
$items  = Equipment::all($search ?: null);
$availableCounts = Equipment::availableCountsByName();
$notifs = Notification::forUser((int)$user['id'], 10);
$unread = Notification::unreadCount((int)$user['id']);
$flash = $_GET['flash'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('Student Dashboard')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=30">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body class="has-student-nav">
<header class="stud-topbar">
  <div class="stud-brand">
    <img src="../assets/images/aclc_logo.png" alt="ACLC" class="brand-logo">
    <div>
      <strong>ACLC COLLEGE</strong>
      <span>MANDAUE CITY</span>
    </div>
  </div>
  <div class="notif student-top-notif">
    <button type="button" class="notif-btn" onclick="toggleNotif()" aria-label="<?= e(t('Notifications')) ?>" aria-expanded="false" title="<?= e(t('Notifications')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
      <?php if ($unread): ?><span class="dot"></span><?php endif; ?>
    </button>
  </div>
  <div class="stud-right">
    <a href="student_dashboard.php" class="mobile-nav-link home-nav-link" aria-label="<?= e(t('Home')) ?>" aria-current="page" title="<?= e(t('Home')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m3 10 9-7 9 7"></path>
        <path d="M5 9v12h14V9M9 21v-7h6v7"></path>
      </svg>
      <span class="nav-label"><?= e(t('Home')) ?></span>
    </a>
    <button type="button" class="cart-btn" id="openBorrowCart" aria-label="<?= e(t('Open borrow request')) ?>" title="<?= e(t('Borrow Request')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h8l4 4v14H7z"></path>
        <path d="M15 3v5h5M10 13h6M10 17h6"></path>
        <path d="M3 7v14h4"></path>
      </svg>
      <span class="cart-count" id="borrowCartCount" hidden>0</span>
    </button>
    <a href="history.php" class="mobile-nav-link" aria-label="<?= e(t('My Borrow History')) ?>" title="<?= e(t('My Borrow History')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
      <span class="nav-label"><?= e(t('My Borrow History')) ?></span>
    </a>
    <button type="button" class="scanner-btn" id="openHeaderScanner" aria-label="<?= e(t('Scan QR code')) ?>" title="<?= e(t('Scan QR code')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"></path>
        <path d="M8 8h3v3H8zM15 8v3M15 15h1M19 15v4M8 15h3v3H8z"></path>
      </svg>
    </button>
    <div class="profile" onclick="toggleProfile()" role="button" tabindex="0" aria-expanded="false" aria-label="<?= e(t('Account menu')) ?>">
      <!-- User Avatar Icon -->
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <div class="profile-info">
        <strong><?= e(t('USER')) ?> <?= e($user['name']) ?></strong>
        <span><?= e($user['course']) ?></span>
      </div>
      <div class="profile-panel" id="profilePanel">
        <div class="pp-head">
          <strong><?= e($user['name']) ?></strong>
          <span><?= e($user['course']) ?> · <?= e(t('Student')) ?></span>
        </div>
        <a href="../settings.php" class="pp-item">
          <span aria-hidden="true">⚙</span>
          <?= e(t('Settings')) ?>
        </a>
        <a href="../auth/student_logout.php" class="pp-logout">
          <!-- Logout Icon -->
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <?= e(t('Logout')) ?>
        </a>
      </div>
    </div>
  </div>
</header>

<main class="stud-main">
  <section class="notif-panel" id="notifPanel" aria-label="<?= e(t('Notifications')) ?>" aria-hidden="true">
    <div class="notif-head">
      <strong><?= e(t('Notifications')) ?></strong>
      <a href="actions.php?do=notif_read" class="mark-read"><?= e(t('Mark all read')) ?></a>
    </div>
    <?php if (empty($notifs)): ?>
      <div class="notif-empty"><?= e(t('No notifications yet.')) ?></div>
    <?php else: foreach ($notifs as $n): ?>
      <div class="notif-item <?= $n['is_read']?'':'unread' ?>">
        <span class="ni-type <?= e($n['type']) ?>" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </span>
        <div class="notif-copy">
          <p><?= e(t_notification($n['message'])) ?></p>
          <small><?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?></small>
        </div>
        <?php if (!$n['is_read']): ?><span class="notif-unread-dot" aria-hidden="true"></span><?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </section>
  <div class="dashboard-content">
  <?php if ($flash): ?><div class="flash"><?= e(t($flash)) ?></div><?php endif; ?>
  <form method="get" class="searchbar wide">
    <input type="text" name="q" placeholder="<?= e(t('Search')) ?>" value="<?= e($search) ?>">
    <button type="submit">
      <!-- Search Magnifying Glass Icon -->
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </button>
  </form>

  <section class="equip-grid">
    <?php foreach ($items as $it):
      $isAvailable = $it['status'] === 'available';
      $availableCount = $availableCounts[$it['name']] ?? 0; ?>
      <article class="equip-card <?= $isAvailable ? '' : 'unavailable' ?>"
        data-search="<?= e($it['name'] . ' ' . $it['serial_number']) ?>">
        <button type="button" class="equip-img item-open"
          data-id="<?= (int)$it['id'] ?>"
          data-name="<?= e($it['name']) ?>"
          data-image="<?= !empty($it['image']) ? e('../assets/upload/' . $it['image']) : '' ?>"
          data-status="<?= e($it['status']) ?>"
          data-available="<?= $availableCount ?>"
          aria-label="<?= e(t('View details for')) ?> <?= e($it['name']) ?>">
          <?php if (!empty($it['image'])): ?>
            <img src="../assets/upload/<?= e($it['image']) ?>" alt="">
          <?php else: ?>
            <div class="placeholder"></div>
          <?php endif; ?>
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
          <span class="detail-label"><?= e(t('Inventory status')) ?></span>
          <strong id="detailStatus"></strong>
        </div>
        <div class="request-tile" id="requestTile" hidden>
          <h3><?= e(t('Add this item')) ?></h3>
          <div class="modal-req-form">
            <input type="number" name="quantity" id="requestQuantity" aria-label="<?= e(t('Quantity')) ?>"
              placeholder="<?= e(t('Quantity')) ?>" min="1" value="1" required>
            <button type="button" class="btn-primary" id="addItemToCart"><?= e(t('Add to Request')) ?></button>
          </div>
        </div>
        <div class="request-tile unavailable-tile" id="unavailableTile" hidden>
          <strong><?= e(t('This item is currently unavailable.')) ?></strong>
          <span><?= e(t('Check back later to request it.')) ?></span>
        </div>
      </div>
    </div>
  </div>
</dialog>

<dialog class="item-dialog cart-dialog" id="borrowCartDialog" aria-labelledby="borrowCartTitle">
  <div class="item-dialog-panel">
    <button type="button" class="dialog-close" id="closeBorrowCart" aria-label="<?= e(t('Close borrow request')) ?>">&times;</button>
    <div class="cart-content">
      <h2 id="borrowCartTitle"><?= e(t('Borrow Request')) ?></h2>
      <p class="cart-intro"><?= e(t('Review your items and submit them together.')) ?></p>
      <p class="borrow-cart-empty" id="borrowCartEmpty"><?= e(t('No items selected. Add equipment to this request.')) ?></p>
      <ul class="borrow-cart-items" id="borrowCartItems"></ul>
      <form method="post" action="actions.php" class="cart-req-form" id="borrowCartForm">
        <input type="hidden" name="do" value="cart_request">
        <div id="borrowCartHiddenItems"></div>
        <label>
          <?= e(t('Purpose')) ?>
          <input type="text" id="borrowCartPurpose" name="purpose" placeholder="<?= e(t('e.g. Lab Work')) ?>"
            pattern="[^0-9]+" title="<?= e(t('Numbers are not allowed.')) ?>" required>
        </label>
        <label>
          <?= e(t('Classroom')) ?>
          <input type="text" name="classroom" placeholder="<?= e(t('e.g. Lab 5')) ?>" required>
        </label>
        <div class="teacher-autocomplete">
          <label for="borrowCartTeacher"><?= e(t("Teacher's Name")) ?></label>
          <input type="text" id="borrowCartTeacher" name="teacher" placeholder="<?= e(t("Teacher's Name")) ?>"
            pattern="[^0-9]+" title="<?= e(t('Numbers are not allowed.')) ?>"
            aria-autocomplete="list" aria-controls="cartTeacherSuggestions" aria-expanded="false" required>
          <div class="teacher-suggestions" id="cartTeacherSuggestions" role="listbox" hidden></div>
        </div>
        <p class="cart-status" id="borrowCartStatus" role="status" aria-live="polite"></p>
        <button type="submit" class="btn-primary" id="submitBorrowCart" disabled><?= e(t('Submit Borrow Request')) ?></button>
      </form>
    </div>
  </div>
</dialog>

<dialog class="item-dialog scanner-dialog" id="headerScannerDialog" aria-labelledby="headerScannerTitle">
  <div class="item-dialog-panel">
    <button type="button" class="dialog-close" id="closeHeaderScanner" aria-label="<?= e(t('Close QR scanner')) ?>">&times;</button>
    <div class="scanner-dialog-content">
      <h2 id="headerScannerTitle"><?= e(t('Scan QR code')) ?></h2>
      <p><?= e(t("Point the camera at an item's QR code to read its serial number.")) ?></p>
      <div class="qr-camera-frame">
        <video id="headerScannerVideo" autoplay muted playsinline></video>
        <div class="qr-scan-guide" aria-hidden="true"></div>
      </div>
      <p class="qr-scanner-status" id="headerScannerStatus" role="status" aria-live="polite"></p>
      <div class="scanner-result" id="headerScannerResult" hidden>
        <span><?= e(t('Serial number')) ?></span>
        <strong id="headerScannerSerial"></strong>
      </div>
      <button type="button" class="btn-primary qr-retry" id="retryHeaderScanner" hidden><?= e(t('Try camera again')) ?></button>
    </div>
  </div>
</dialog>
<script src="../assets/js/student.js?v=16"></script>
</body>
</html>
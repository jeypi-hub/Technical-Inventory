<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');
$notifs = Notification::forUser((int)$user['id'], 10);
$unread = Notification::unreadCount((int)$user['id']);
$flash = $_GET['flash'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('Borrow Request')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=41">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body class="has-student-nav borrow-request-fixed-form-page" data-borrow-cart-key="<?= e('aclc-borrow-cart-' . (int)$user['id']) ?>">
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
    <div class="profile" onclick="toggleProfile()" role="button" tabindex="0" aria-expanded="false" aria-label="<?= e(t('Account menu')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
      </svg>
      <div class="profile-info">
        <strong><?= e(t('USER')) ?> <?= e($user['name']) ?></strong>
        <span><?= e($user['course']) ?></span>
      </div>
      <div class="profile-panel" id="profilePanel">
        <div class="pp-head">
          <strong><?= e($user['name']) ?></strong>
          <span><?= e($user['course']) ?> · <?= e(t('Student')) ?></span>
        </div>
        <a href="../profile.php" class="pp-item">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path></svg>
          <?= e(t('Profile')) ?>
        </a>
        <a href="../settings.php" class="pp-item">
          <span aria-hidden="true">⚙</span>
          <?= e(t('Settings')) ?>
        </a>
        <a href="../auth/student_logout.php" class="pp-logout">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <?= e(t('Logout')) ?>
        </a>
      </div>
    </div>
  </div>
  <div class="stud-right">
    <a href="student_dashboard.php" class="mobile-nav-link home-nav-link" aria-label="<?= e(t('Home')) ?>" title="<?= e(t('Home')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m3 10 9-7 9 7"></path>
        <path d="M5 9v12h14V9M9 21v-7h6v7"></path>
      </svg>
      <span class="nav-label"><?= e(t('Home')) ?></span>
    </a>
    <a href="borrow_request.php" class="cart-btn" id="openBorrowCart" aria-current="page" aria-label="<?= e(t('Open borrow request')) ?>" title="<?= e(t('Borrow Request')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h8l4 4v14H7z"></path>
        <path d="M15 3v5h5M10 13h6M10 17h6"></path>
        <path d="M3 7v14h4"></path>
      </svg>
      <span class="cart-count" id="borrowCartCount" hidden>0</span>
    </a>
    <a href="history.php" class="mobile-nav-link" aria-label="<?= e(t('My Borrow History')) ?>" title="<?= e(t('My Borrow History')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
      <span class="nav-label"><?= e(t('My Borrow History')) ?></span>
    </a>
    <a href="student_dashboard.php?open=scanner" class="scanner-btn" aria-label="<?= e(t('Scan QR code')) ?>" title="<?= e(t('Scan QR code')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"></path>
        <path d="M8 8h3v3H8zM15 8v3M15 15h1M19 15v4M8 15h3v3H8z"></path>
      </svg>
    </a>
  </div>
</header>

<main class="stud-main borrow-request-main">
  <section class="notif-panel" id="notifPanel" aria-label="<?= e(t('Notifications')) ?>" aria-hidden="true">
    <div class="notif-head">
      <strong><?= e(t('Notifications')) ?></strong>
      <a href="actions.php?do=notif_read" class="mark-read"><?= e(t('Mark all read')) ?></a>
    </div>
    <?php if (empty($notifs)): ?>
      <div class="notif-empty"><?= e(t('No notifications yet.')) ?></div>
    <?php else: foreach ($notifs as $n): ?>
      <div class="notif-item <?= $n['is_read']?'':'unread' ?>">
        <span class="ni-type <?= e($n['type']) ?>" aria-hidden="true"></span>
        <div>
          <p><?= e(t_notification($n['message'])) ?></p>
          <small><?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?></small>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </section>
  <?php if ($flash): ?><div class="flash"><?= e(t($flash)) ?></div><?php endif; ?>
  <section class="borrow-request-page" aria-label="<?= e(t('Borrow request details')) ?>">
    <div class="cart-content">
      <a class="borrow-request-browse" href="student_dashboard.php"><?= e(t('Browse Equipment')) ?></a>
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
  </section>
</main>
<script src="../assets/js/student.js?v=22"></script>
<script src="../assets/js/borrow_request.js?v=2"></script>
</body>
</html>

<?php
$currentStudentPage = basename($_SERVER['PHP_SELF']);
$isScannerOnThisPage = ($currentStudentPage === 'student_dashboard.php');
$activeBorrowCount = isset($user['id']) ? count(BorrowRequest::activeForUser((int)$user['id'])) : 0;
?>
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
      <?php if (!empty($unread)): ?><span class="dot"></span><?php endif; ?>
    </button>
    <div class="profile" onclick="toggleProfile()" role="button" tabindex="0" aria-expanded="false" aria-label="<?= e(t('Account menu')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <div class="profile-info">
        <strong><?= e(t('USER')) ?> <?= e($user['name']) ?></strong>
        <span><?= e($user['course']) ?></span>
      </div>
      <div class="profile-panel" id="profilePanel">
        <div class="pp-head">
          <strong><?= e($user['name']) ?></strong>
          <span><?= e($user['course']) ?> · <?= e(t($roleLabel ?? 'Student')) ?></span>
        </div>
        <a href="../profile.php" class="pp-item">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path></svg>
          <?= e(t('Profile')) ?>
        </a>
        <a href="../settings.php" class="pp-item">
          <span aria-hidden="true">⚙</span>
          <?= e(t('Settings')) ?>
        </a>
        <a href="../auth/logout.php" class="pp-logout">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <?= e(t('Logout')) ?>
        </a>
      </div>
    </div>
  </div>
  <nav class="stud-right stud-nav-5" aria-label="<?= e(t('Student navigation')) ?>">
    <!-- Tab 1: Home -->
    <a href="student_dashboard.php"
       class="mobile-nav-link home-nav-link"
       aria-label="<?= e(t('Home')) ?>"
       <?= $currentStudentPage === 'student_dashboard.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Home')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m3 10 9-7 9 7"></path>
        <path d="M5 9v12h14V9M9 21v-7h6v7"></path>
      </svg>
      <span class="nav-label"><?= e(t('Home')) ?></span>
    </a>

    <!-- Tab 2: Borrow Request (Cart) -->
    <a href="borrow_request.php"
       class="mobile-nav-link cart-btn"
       id="openBorrowCart"
       aria-label="<?= e(t('Borrow Request')) ?>"
       <?= $currentStudentPage === 'borrow_request.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Borrow Request')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h8l4 4v14H7z"></path>
        <path d="M15 3v5h5M10 13h6M10 17h6"></path>
        <path d="M3 7v14h4"></path>
      </svg>
      <span class="cart-count" id="borrowCartCount" hidden>0</span>
      <span class="nav-label"><?= e(t('Request')) ?></span>
    </a>

    <!-- Tab 3 (CENTER STANDOUT): QR Code Scanner -->
    <?php if ($isScannerOnThisPage): ?>
      <button type="button"
              class="scanner-btn nav-qr-center"
              id="openHeaderScanner"
              aria-label="<?= e(t('Scan QR code')) ?>"
              title="<?= e(t('Scan QR code')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"></path>
          <path d="M8 8h3v3H8zM15 8v3M15 15h1M19 15v4M8 15h3v3H8z"></path>
        </svg>
      </button>
    <?php else: ?>
      <a href="student_dashboard.php?open=scanner"
         class="scanner-btn nav-qr-center"
         aria-label="<?= e(t('Scan QR code')) ?>"
         title="<?= e(t('Scan QR code')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"></path>
          <path d="M8 8h3v3H8zM15 8v3M15 15h1M19 15v4M8 15h3v3H8z"></path>
        </svg>
      </a>
    <?php endif; ?>

    <!-- Tab 4: Active Borrows (Current Ongoing Borrow Transactions) -->
    <a href="active_borrows.php"
       class="mobile-nav-link"
       aria-label="<?= e(t('Active Borrows')) ?>"
       <?= $currentStudentPage === 'active_borrows.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Active Borrows')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="4" y="4" width="16" height="17" rx="2"></rect>
        <path d="M9 4.5V3h6v1.5"></path>
        <path d="m8 13 2.5 2.5L16 10"></path>
      </svg>
      <?php if ($activeBorrowCount > 0): ?>
        <span class="cart-count active-borrow-badge"><?= (int)$activeBorrowCount ?></span>
      <?php endif; ?>
      <span class="nav-label"><?= e(t('Active')) ?></span>
    </a>

    <!-- Tab 5: Borrow History (Entire Borrowing History) -->
    <a href="history.php"
       class="mobile-nav-link"
       aria-label="<?= e(t('Borrow History')) ?>"
       <?= $currentStudentPage === 'history.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Borrow History')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
      <span class="nav-label"><?= e(t('History')) ?></span>
    </a>
  </nav>
</header>

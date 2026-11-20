<?php
require_once __DIR__ . '/../../classes/Notification.php';
$current = basename($_SERVER['PHP_SELF']);
$__notifs = Notification::forUser((int)$user['id'], 10);
$__unread = Notification::unreadCount((int)$user['id']);
$__roleLabel = $user['role'] === 'admin'
    ? 'Administrator'
    : ($user['role'] === 'staff' ? 'Technical Staff' : 'Student');
?>
<header class="stud-topbar admin-topbar">
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
      <?php if ($__unread > 0): ?><span class="dot"></span><?php endif; ?>
    </button>
    <div class="profile" onclick="toggleProfile()" role="button" tabindex="0" aria-expanded="false" aria-label="<?= e(t('Account menu')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <div class="profile-info">
        <strong><?= e($user['name']) ?></strong>
        <span><?= e(t($__roleLabel)) ?></span>
      </div>
      <div class="profile-panel" id="profilePanel">
        <div class="pp-head">
          <strong><?= e($user['name']) ?></strong>
          <span><?= !empty($user['course']) ? e($user['course']) : e(t($__roleLabel)) ?> · <?= e(t($__roleLabel)) ?></span>
        </div>
        <a href="../profile.php" class="pp-item">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path></svg>
          <?= e(t('Profile')) ?>
        </a>
        <a href="../settings.php" class="pp-item">
          <span aria-hidden="true">⚙</span>
          <?= e(t('Settings')) ?>
        </a>
        <a href="../auth/admin_logout.php" class="pp-logout">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <?= e(t('Logout')) ?>
        </a>
      </div>
    </div>
  </div>
  <div class="stud-right">
    <a href="admin_dashboard.php"
       class="mobile-nav-link home-nav-link"
       aria-label="<?= e(t('Home')) ?>"
       <?= $current === 'admin_dashboard.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Home')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m3 10 9-7 9 7"></path>
        <path d="M5 9v12h14V9M9 21v-7h6v7"></path>
      </svg>
      <span class="nav-label"><?= e(t('Home')) ?></span>
    </a>
    <a href="manage_equipment.php"
       class="mobile-nav-link"
       aria-label="<?= e(t('Manage Catalog')) ?>"
       <?= $current === 'manage_equipment.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Manage Catalog')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12" y1="22.08" x2="12" y2="12"></line>
      </svg>
      <span class="nav-label"><?= e(t('Manage Catalog')) ?></span>
    </a>
    <a href="active_checkouts.php"
       class="mobile-nav-link"
       aria-label="<?= e(t('Active Checkouts')) ?>"
       <?= $current === 'active_checkouts.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('Active Checkouts')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="4" y="4" width="16" height="17" rx="2"></rect>
        <path d="M9 4.5V3h6v1.5"></path>
        <path d="m8 13 2.5 2.5L16 10"></path>
      </svg>
      <span class="nav-label"><?= e(t('Active Checkouts')) ?></span>
    </a>
    <a href="history.php"
       class="mobile-nav-link"
       aria-label="<?= e(t('History Log')) ?>"
       <?= $current === 'history.php' ? 'aria-current="page"' : '' ?>
       title="<?= e(t('History Log')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
      <span class="nav-label"><?= e(t('History Log')) ?></span>
    </a>
    <?php if (User::can('roles.manage')): ?>
      <a href="access_control.php"
         class="mobile-nav-link"
         aria-label="<?= e(t('Access Control')) ?>"
         <?= $current === 'access_control.php' ? 'aria-current="page"' : '' ?>
         title="<?= e(t('Access Control')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          <path d="m9 12 2 2 4-4"></path>
        </svg>
        <span class="nav-label"><?= e(t('Access Control')) ?></span>
      </a>
      <a href="audit_trail.php"
         class="mobile-nav-link"
         aria-label="<?= e(t('Audit Trail')) ?>"
         <?= $current === 'audit_trail.php' ? 'aria-current="page"' : '' ?>
         title="<?= e(t('Audit Trail')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        <span class="nav-label"><?= e(t('Audit Trail')) ?></span>
      </a>
    <?php endif; ?>
  </div>
</header>

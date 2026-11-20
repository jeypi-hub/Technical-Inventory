<?php
require_once __DIR__ . '/../../classes/Notification.php';
$__notifs = Notification::forUser((int)$user['id'], 10);
$__unread = Notification::unreadCount((int)$user['id']);
$__roleLabel = $user['role'] === 'admin'
    ? 'Administrator'
    : ($user['role'] === 'staff' ? 'Staff' : 'Student');
?>
<header class="topbar">
  <h1 class="page-title"><?= e(t($pageTitle ?? 'Home')) ?></h1>
  <div class="topbar-right">
    <div class="notif" id="notif">
      <button class="notif-btn" onclick="toggleNotif()">
        <span class="bell">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </span>
        <?php if ($__unread > 0): ?><span class="dot"></span><?php endif; ?>
      </button>
      <div class="notif-panel" id="notifPanel">
        <div class="notif-head">
          <strong><?= e(t('Notifications')) ?></strong>
          <a href="actions.php?do=notif_read" class="mark-read"><?= e(t('Mark all read')) ?></a>
        </div>
        <?php if (empty($__notifs)): ?>
          <div class="notif-empty"><?= e(t('No notifications yet.')) ?></div>
        <?php else: foreach ($__notifs as $n): ?>
          <div class="notif-item <?= $n['is_read']? '' : 'unread' ?>">
            <span class="ni-type <?= e($n['type']) ?>"></span>
            <div>
              <p><?= e(t_notification($n['message'])) ?></p>
              <small><?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?></small>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="profile" onclick="toggleProfile()">
      <span class="avatar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </span>
      <div class="profile-info">
        <strong><?= e($user['name']) ?></strong>
        <span><?= e(t($__roleLabel)) ?></span>
      </div>
      <div class="profile-panel" id="profilePanel">
        <div class="pp-head">
          <strong><?= e($user['name']) ?></strong>
          <span><?= !empty($user['course']) ? e($user['course']) : e(t('Administrator')) ?></span>
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
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <?= e(t('Logout')) ?>
        </a>
      </div>
    </div>
  </div>
</header>
<?php
require_once __DIR__ . '/../../classes/Notification.php';
$__notifs = $__notifs ?? Notification::forUser((int)$user['id'], 10);
$__unread = $__unread ?? Notification::unreadCount((int)$user['id']);
?>
<section class="notif-panel" id="notifPanel" aria-label="<?= e(t('Notifications')) ?>" aria-hidden="true">
  <div class="notif-head">
    <strong><?= e(t('Notifications')) ?></strong>
    <a href="actions.php?do=notif_read" class="mark-read"><?= e(t('Mark all read')) ?></a>
  </div>
  <?php if (empty($__notifs)): ?>
    <div class="notif-empty"><?= e(t('No notifications yet.')) ?></div>
  <?php else: foreach ($__notifs as $n): ?>
    <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
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

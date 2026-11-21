<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');
$isFaculty = User::isFaculty($user);
$roleLabel = $isFaculty ? 'Faculty' : 'Student';
$durationOptions = BorrowRequest::durationOptions();

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
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=40">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body class="has-student-nav" data-borrow-cart-key="<?= e('aclc-borrow-cart-' . (int)$user['id']) ?>">
<?php include __DIR__ . '/includes/topbar.php'; ?>

<main class="stud-main borrow-request-main">
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
          <?= e(t('Borrow Duration (30-min intervals, max 4 hours)')) ?>
          <select name="duration_minutes" id="borrowCartDuration" class="duration-select" required>
            <?php foreach ($durationOptions as $mins => $label): ?>
              <option value="<?= (int)$mins ?>" <?= $mins === 60 ? 'selected' : '' ?>><?= e(t($label)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
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

<dialog class="item-dialog submit-confirm-dialog" id="requestSubmittedDialog" aria-labelledby="requestSubmittedTitle">
  <div class="item-dialog-panel submit-confirm-panel">
    <div class="submit-confirm-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
    </div>
    <h2 id="requestSubmittedTitle"><?= e(t('Borrow Request Submitted!')) ?></h2>
    <p id="requestSubmittedMessage">
      <?= e(t('Your borrow request form is complete and is now Pending for Technical Staff to either approve or decline.')) ?>
    </p>
    <div class="submit-confirm-actions">
      <a href="active_borrows.php" class="btn-primary" id="viewSubmittedRequestBtn"><?= e(t('View in Active Borrows')) ?></a>
      <button type="button" class="btn-secondary" id="closeSubmittedDialog"><?= e(t('OK')) ?></button>
    </div>
  </div>
</dialog>

<script src="../assets/js/student.js?v=27"></script>
<script src="../assets/js/borrow_request.js?v=5"></script>
</body>
</html>

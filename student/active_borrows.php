<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');
$isFaculty = User::isFaculty($user);
$roleLabel = $isFaculty ? 'Faculty' : 'Student';
$userId = (int)$user['id'];
$rows = BorrowRequest::activeForUser($userId);
$notifs = Notification::forUser($userId, 10);
$unread = Notification::unreadCount($userId);
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('Active Borrows')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=40">
<link rel="stylesheet" href="../assets/css/history.css?v=36">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body class="has-student-nav" data-borrow-cart-key="<?= e('aclc-borrow-cart-' . $userId) ?>">
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

  <?php if (!empty($_GET['flash'])): ?><div class="flash"><?= e(t($_GET['flash'])) ?></div><?php endif; ?>

  <div class="active-borrows-head">
    <div>
      <h2><?= e(t('Active Borrows')) ?></h2>
      <p class="muted"><?= e(t('Your current ongoing requests and active borrow timers. Completed transactions automatically move to Borrow History.')) ?></p>
    </div>
    <a href="history.php" class="btn-view-history"><?= e(t('View Full Borrow History')) ?> →</a>
  </div>

  <section class="active-card-stack" aria-label="<?= e(t('Active Borrows')) ?>">
    <?php if (empty($rows)): ?>
      <div class="active-borrows-empty">
        <p><?= e(t('You have no active borrow transactions right now.')) ?></p>
        <div class="active-borrows-empty-actions">
          <a href="student_dashboard.php" class="btn-primary"><?= e(t('Browse Equipment')) ?></a>
          <a href="history.php" class="btn-view-history"><?= e(t('View Borrow History')) ?></a>
        </div>
      </div>
    <?php else: foreach ($rows as $r):
      $dt = $r['borrowed_at'] ?: $r['approved_at'] ?: $r['requested_at'];
      $durLabel = BorrowRequest::formatDuration((int)($r['duration_minutes'] ?? 60));
      $st = $r['status'];
      $basketItems = $r['basket_items'] ?? [];
      $itemCount = count($basketItems);
      $totalQty = (int)($r['quantity'] ?? max(1, $itemCount));
      $focusId = (int)($_GET['focus'] ?? 0);
      $isFocused = $focusId === (int)$r['id'];
      $statusText = $st === 'approved' ? t('Ready for QR') : t(ucfirst($st));
      $statusClass = $st === 'approved' ? 'pending' : $st;
    ?>
      <details id="req-<?= (int)$r['id'] ?>" class="active-pill-card <?= $isFocused ? 'focus-highlight' : '' ?>" <?= $isFocused ? 'open' : '' ?>>
        <summary class="active-pill-summary">
          <div class="active-pill-top">
            <time datetime="<?= e(date(DATE_ATOM, strtotime($dt))) ?>">
              <?= e(t('Date & Time:')) ?> <strong><?= e(date('M d, Y h:i A', strtotime($dt))) ?></strong>
            </time>
            <span class="active-pill-top-right">
              <span class="active-pill-id">#<?= (int)$r['id'] ?></span>
              <span class="card-dropdown-indicator" aria-hidden="true">
                <svg class="items-pill-chevron" viewBox="0 0 24 24">
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </span>
            </span>
          </div>

          <div class="active-pill-row">
            <span class="info-pill">
              <?= e(t('Quantity:')) ?> <strong><?= $totalQty ?></strong>
            </span>

            <span class="info-pill duration-pill">
              <?php if ($st === 'approved'):
                $pickupExpiresTs = strtotime($r['approved_at'] ?? 'now') + BorrowRequest::PICKUP_EXPIRY_SECONDS;
              ?>
                <span data-pickup-expires-at="<?= e(date('c', $pickupExpiresTs)) ?>">
                  <?= e(t('Time Duration:')) ?> <strong><?= e($durLabel) ?></strong>
                </span>
              <?php elseif (in_array($st, ['borrowed', 'overdue'], true) && !empty($r['due_at'])): ?>
                <span data-due-at="<?= e(date('c', strtotime($r['due_at']))) ?>"
                      data-borrowed-at="<?= !empty($r['borrowed_at']) ? e(date('c', strtotime($r['borrowed_at']))) : '' ?>">
                  <?= e(t('Time Duration:')) ?> <strong><?= e($durLabel) ?></strong>
                </span>
              <?php else: ?>
                <?= e(t('Time Duration:')) ?> <strong><?= e($durLabel) ?></strong>
              <?php endif; ?>
            </span>

            <span class="info-pill items-dropdown-pill">
              <span><?= e(t('Items')) ?> (<?= $itemCount ?>)</span>
              <svg class="items-pill-chevron" viewBox="0 0 24 24" aria-hidden="true">
                <path d="m6 9 6 6 6-6"></path>
              </svg>
            </span>

            <span class="status-outline-pill status-outline-<?= e($statusClass) ?>">
              <?= e($statusText) ?>
            </span>
          </div>
        </summary>

        <div class="active-pill-body">
          <div class="active-pill-items-box">
            <span class="active-pill-section-label"><?= e($itemCount > 1 ? t('Items in Basket') : t('Borrowed Item')) ?></span>
            <ul class="active-pill-items-list">
              <?php foreach ($basketItems as $bi): ?>
                <li>
                  <strong><?= e($bi['name']) ?></strong>
                  <small><?= e($bi['serial_number']) ?></small>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="active-pill-meta-grid">
            <div><span><?= e(t('Classroom')) ?></span><strong><?= e($r['classroom']) ?></strong></div>
            <div><span><?= e(t('Teacher')) ?></span><strong><?= e($r['teacher']) ?></strong></div>
            <div><span><?= e(t('Duration')) ?></span><strong><?= e($durLabel) ?></strong></div>
            <div><span><?= e(t('Purpose')) ?></span><strong><?= e($r['purpose']) ?></strong></div>
          </div>

          <?php if ($st === 'pending'): ?>
            <div class="active-pill-actions">
              <a class="btn-cancel" href="actions.php?do=cancel&id=<?= (int)$r['id'] ?>"
                 onclick="return confirm(<?= e(json_encode(t('Cancel this pending request?'))) ?>)"><?= e(t('Cancel Request')) ?></a>
            </div>
          <?php elseif ($st === 'approved'): ?>
            <div class="active-pill-actions">
              <a class="btn-scan-action" href="student_dashboard.php?open=scanner"><?= e(t('Scan QR to Start Timer')) ?></a>
            </div>
          <?php endif; ?>
        </div>
      </details>
    <?php endforeach; endif; ?>
  </section>
</main>
<script src="../assets/js/student.js?v=28"></script>
</body>
</html>

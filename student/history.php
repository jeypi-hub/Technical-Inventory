<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');
$isFaculty = User::isFaculty($user);
$roleLabel = $isFaculty ? 'Faculty' : 'Student';
$userId = (int)$user['id'];
$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$perPage = 5;
$total = BorrowRequest::countForUser($userId, $search);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$page = min($totalPages, $page === false ? 1 : $page);
$rows = BorrowRequest::pageForUser($userId, $search, $perPage, ($page - 1) * $perPage);
$paginationQuery = $search !== '' ? ['q' => $search] : [];
$notifs = Notification::forUser((int)$user['id'], 10);
$unread = Notification::unreadCount((int)$user['id']);
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('Borrow History')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=40">
<link rel="stylesheet" href="../assets/css/history.css?v=33">
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
  <?php if (!empty($_GET['flash'])): ?><div class="flash"><?= e(t($_GET['flash'])) ?></div><?php endif; ?>
  <form method="get" class="student-history-search" role="search">
    <label class="student-history-search-label" for="historySearch"><?= e(t('Search borrow history')) ?></label>
    <input type="search" id="historySearch" name="q" value="<?= e($search) ?>"
           placeholder="<?= e(t('Search')) ?>" aria-label="<?= e(t('Search borrow history')) ?>">
  </form>
  <section class="student-history-records" aria-label="<?= e(t('My Borrow History')) ?>">
    <?php if (empty($rows)): ?>
      <p class="student-history-empty">
        <?= e($search === '' ? t('No requests yet.') : t('No history records match your search.')) ?>
      </p>
    <?php else: foreach ($rows as $r):
      $dt = $r['returned_at'] ?: $r['borrowed_at'] ?: $r['approved_at'] ?: $r['requested_at'];
      $durLabel = BorrowRequest::formatDuration((int)($r['duration_minutes'] ?? 60));
      $st = $r['status'];
      $basketItems = $r['basket_items'] ?? [];
      $itemCount = count($basketItems);
      $headerTitle = $itemCount > 1
          ? t('Basket') . " ({$itemCount} " . t('items') . '): ' . $r['equipment_name']
          : $r['equipment_name'];
      $focusId = (int)($_GET['focus'] ?? 0);
      $isFocused = $focusId === (int)$r['id'];
      $shouldOpen = $isFocused || in_array($st, ['pending', 'approved', 'borrowed', 'overdue'], true);
    ?>
      <details id="req-<?= (int)$r['id'] ?>" class="student-history-record <?= $isFocused ? 'focus-highlight' : '' ?>" <?= $shouldOpen ? 'open' : '' ?>>
        <summary class="student-history-record-summary">
          <div class="student-history-record-meta">
            <p><strong><?= e(t('ID')) ?>:</strong> #<?= (int)$r['id'] ?> · <strong><?= e($headerTitle) ?></strong></p>
            <time datetime="<?= e(date(DATE_ATOM, strtotime($dt))) ?>">
              <strong><?= e(t('Date & Time')) ?>:</strong> <?= e(date('M d, Y h:i A', strtotime($dt))) ?>
            </time>
          </div>
          <div class="student-history-record-summary-actions">
            <?php if ($st === 'approved'): ?>
              <span class="status pending"><?= e(t('Pending (Ready for QR Scan)')) ?></span>
            <?php else: ?>
              <span class="status <?= e($st) ?>"><?= e(t(ucfirst($st))) ?></span>
            <?php endif; ?>
            <svg class="student-history-record-chevron" viewBox="0 0 24 24" aria-hidden="true">
              <path d="m6 9 6 6 6-6"></path>
            </svg>
          </div>
        </summary>
        <div class="student-history-record-details">
          <div class="student-history-record-main">
            <div class="student-history-record-fields">
              <div class="student-history-record-field basket-items-field">
                <span><?= e($itemCount > 1 ? t('Items in Basket') : t('Equipment')) ?></span>
                <?php if ($itemCount > 1): ?>
                  <ul class="basket-item-list">
                    <?php foreach ($basketItems as $bi): ?>
                      <li><strong><?= e($bi['name']) ?></strong> <small>(<?= e($bi['serial_number']) ?>)</small></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <strong><?= e($r['equipment_name']) ?> (<?= e($r['serial_number'] ?? '') ?>)</strong>
                <?php endif; ?>
              </div>
              <div class="student-history-record-field">
                <span><?= e(t('Duration')) ?></span>
                <strong><?= e($durLabel) ?></strong>
              </div>
              <div class="student-history-record-field">
                <span><?= e(t('Timer / Due Time')) ?></span>
                <strong>
                  <?php if ($st === 'pending'): ?>
                    <?= e(t('Waiting for Staff Approval')) ?>
                  <?php elseif ($st === 'approved'):
                    $pickupExpiresTs = strtotime($r['approved_at'] ?? 'now') + BorrowRequest::PICKUP_EXPIRY_SECONDS;
                  ?>
                    <span class="timer-badge awaiting-scan"
                          data-pickup-expires-at="<?= e(date('c', $pickupExpiresTs)) ?>">
                      <?= e(t('Scan QR within 10m (by')) ?> <?= e(date('h:i A', $pickupExpiresTs)) ?>)
                    </span>
                  <?php elseif (in_array($st, ['borrowed', 'overdue'], true) && !empty($r['due_at'])): ?>
                    <span class="timer-badge <?= $st === 'overdue' ? 'is-overdue' : 'is-running' ?>"
                          data-due-at="<?= e(date('c', strtotime($r['due_at']))) ?>"
                          data-borrowed-at="<?= !empty($r['borrowed_at']) ? e(date('c', strtotime($r['borrowed_at']))) : '' ?>">
                      <?= e(t('Due:')) ?> <?= e(date('h:i A', strtotime($r['due_at']))) ?>
                    </span>
                  <?php elseif ($st === 'lost'): ?>
                    <span class="timer-badge is-overdue"><?= e(t('Exceeded 24 hrs (Lost)')) ?></span>
                  <?php elseif ($st === 'returned' && !empty($r['returned_at'])): ?>
                    <?= e(t('Returned')) ?> <?= e(date('h:i A', strtotime($r['returned_at']))) ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </strong>
              </div>
              <div class="student-history-record-field">
                <span><?= e(t('Classroom')) ?></span>
                <strong><?= e($r['classroom']) ?></strong>
              </div>
              <div class="student-history-record-field">
                <span><?= e(t('Quantity')) ?></span>
                <strong><?= (int)($r['quantity'] ?? 1) ?></strong>
              </div>
              <div class="student-history-record-field">
                <span><?= e(t('Teacher')) ?></span>
                <strong><?= e($r['teacher']) ?></strong>
              </div>
            </div>
          </div>
          <div class="student-history-record-purpose">
            <strong><?= e(t('Purpose')) ?></strong>
            <p><?= e($r['purpose']) ?></p>
          </div>
          <?php if ($st === 'pending'): ?>
            <div class="student-history-record-actions">
              <a class="btn-cancel" href="actions.php?do=cancel&id=<?= (int)$r['id'] ?>"
                 onclick="return confirm(<?= e(json_encode(t('Cancel this pending request?'))) ?>)"><?= e(t('Cancel')) ?></a>
            </div>
          <?php elseif ($st === 'approved'): ?>
            <div class="student-history-record-actions">
              <a class="btn-scan-action" href="student_dashboard.php?open=scanner"><?= e(t('Scan QR to Start Timer')) ?></a>
            </div>
          <?php endif; ?>
        </div>
      </details>
    <?php endforeach; endif; ?>
  </section>
  <?php if ($totalPages > 1): ?>
    <nav class="student-history-pagination" aria-label="<?= e(t('History pages')) ?>">
      <?php if ($page > 1): ?>
        <a href="?<?= e(http_build_query($paginationQuery + ['page' => $page - 1])) ?>"><?= e(t('Previous')) ?></a>
      <?php endif; ?>
      <span aria-current="page"><?= e(t_format('Page {page} of {total}', ['page' => (string)$page, 'total' => (string)$totalPages])) ?></span>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= e(http_build_query($paginationQuery + ['page' => $page + 1])) ?>"><?= e(t('Next')) ?></a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</main>
<script src="../assets/js/student.js?v=23"></script>
</body>
</html>

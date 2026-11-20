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
<title><?= e(t('My Borrow History')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css?v=37">
<link rel="stylesheet" href="../assets/css/history.css?v=31">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body data-borrow-cart-key="<?= e('aclc-borrow-cart-' . (int)$user['id']) ?>">
<header class="stud-topbar">
  <div class="stud-brand">
    <img src="../assets/images/aclc_logo.png" alt="ACLC" class="brand-logo">
    <div><strong>ACLC COLLEGE</strong><span>MANDAUE CITY</span></div>
  </div>
  <div class="notif student-top-notif">
    <button type="button" class="notif-btn" onclick="toggleNotif()" aria-label="<?= e(t('Notifications')) ?>" aria-expanded="false" title="<?= e(t('Notifications')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
      <?php if ($unread): ?><span class="dot"></span><?php endif; ?>
    </button>
    <div class="notif-panel" id="notifPanel">
      <div class="notif-head">
        <strong><?= e(t('Notifications')) ?></strong>
        <a href="actions.php?do=notif_read" class="mark-read"><?= e(t('Mark all read')) ?></a>
      </div>
      <?php if (empty($notifs)): ?>
        <div class="notif-empty"><?= e(t('No notifications yet.')) ?></div>
      <?php else: foreach ($notifs as $n): ?>
        <div class="notif-item <?= $n['is_read']?'':'unread' ?>">
          <span class="ni-type <?= e($n['type']) ?>"></span>
          <div>
            <p><?= e(t_notification($n['message'])) ?></p>
            <small><?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?></small>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
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
          <span><?= e($user['course']) ?> · <?= e(t($roleLabel)) ?></span>
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
    <a href="borrow_request.php" class="cart-btn" aria-label="<?= e(t('Open borrow request')) ?>" title="<?= e(t('Borrow Request')) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h8l4 4v14H7z"></path>
        <path d="M15 3v5h5M10 13h6M10 17h6"></path>
        <path d="M3 7v14h4"></path>
      </svg>
    </a>
    <a href="history.php" class="mobile-nav-link" aria-label="<?= e(t('My Borrow History')) ?>" aria-current="page" title="<?= e(t('My Borrow History')) ?>">
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
<main class="stud-main">
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
    ?>
      <details class="student-history-record" <?= in_array($st, ['pending', 'approved', 'borrowed', 'overdue'], true) ? 'open' : '' ?>>
        <summary class="student-history-record-summary">
          <div class="student-history-record-meta">
            <p><strong><?= e(t('ID')) ?>:</strong> #<?= (int)$r['id'] ?> · <strong><?= e($r['equipment_name']) ?></strong></p>
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
              <div class="student-history-record-field">
                <span><?= e(t('Equipment')) ?></span>
                <strong><?= e($r['equipment_name']) ?> (<?= e($r['serial_number'] ?? '') ?>)</strong>
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

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('history.view');

$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$perPage = 25;
$total = BorrowRequest::countHistory($search);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$page = min($totalPages, $page === false ? 1 : $page);
$rows = BorrowRequest::pageHistory($search, $perPage, ($page - 1) * $perPage);
$paginationQuery = $search !== '' ? ['q' => $search] : [];
$pageTitle = 'History';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('History Log')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=17">
<link rel="stylesheet" href="../assets/css/history.css?v=27">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <section class="card hist-card">
      <div class="card-head">
        <h2><?= e(t('Complete Borrowing History Log')) ?></h2>
        <div class="hist-controls">
          <form method="get" class="searchbar" role="search">
            <input type="text" name="q" placeholder="<?= e(t('Search')) ?>" value="<?= e($search) ?>" autocomplete="off" aria-label="<?= e(t('Search')) ?>">
            <?php if ($search !== ''): ?>
              <a href="history.php" class="searchbar-clear" title="<?= e(t('Clear search')) ?>" aria-label="<?= e(t('Clear search')) ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </a>
            <?php endif; ?>
            <button type="submit" title="<?= e(t('Search')) ?>" aria-label="<?= e(t('Search')) ?>">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
          </form>
          <a class="btn-export" href="actions.php?do=export_history">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <?= e(t('Export Excel')) ?>
          </a>
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table hist">
          <thead>
            <tr>
              <th><?= e(t('ID')) ?></th>
              <th><?= e(t('Date & Time')) ?></th>
              <th><?= e(t('Borrower / ID / Dept')) ?></th>
              <th><?= e(t('Equipment & Serial No.')) ?></th>
              <th><?= e(t('Quantity')) ?></th>
              <th><?= e(t('Duration')) ?></th>
              <th><?= e(t('Due / Returned')) ?></th>
              <th><?= e(t('Classroom')) ?></th>
              <th><?= e(t('Teacher')) ?></th>
              <th><?= e(t('Purpose')) ?></th>
              <th><?= e(t('Status')) ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $i => $r):
            $dt = $r['returned_at'] ?: $r['borrowed_at'] ?: $r['approved_at'] ?: $r['requested_at'];
            $durLabel = BorrowRequest::formatDuration((int)($r['duration_minutes'] ?? 60));
            $st = $r['status'];
          ?>
            <tr id="req-<?= (int)$r['id'] ?>" class="<?= ((int)($_GET['focus'] ?? 0) === (int)$r['id']) ? 'focus-highlight' : '' ?>">
              <td>#<?= (int)$r['id'] ?></td>
              <td><?= e(date('M d, Y h:i A', strtotime($dt))) ?></td>
              <td>
                <strong><?= e($r['student_name']) ?></strong><br>
                <a><?= e($r['course']) ?></a><br>
                <small>(<?= e($r['student_uid']) ?>)</small>
              </td>
              <td><?= e($r['equipment_name']) ?> [<?= e($r['serial_number']) ?>]</td>
              <td><?= (int)($r['quantity'] ?? 1) ?></td>
              <td><strong><?= e($durLabel) ?></strong></td>
              <td>
                <?php if (!empty($r['returned_at'])): ?>
                  <small><?= e(t('Returned:')) ?> <?= e(date('M d, h:i A', strtotime($r['returned_at']))) ?></small>
                <?php elseif (!empty($r['due_at'])): ?>
                  <small><?= e(t('Due:')) ?> <?= e(date('M d, h:i A', strtotime($r['due_at']))) ?></small>
                <?php elseif ($st === 'approved'): ?>
                  <small><?= e(t('Awaiting QR Scan')) ?></small>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= e($r['classroom']) ?></td>
              <td><?= e($r['teacher']) ?></td>
              <td><?= e($r['purpose']) ?></td>
              <td>
                <?php if ($st === 'approved'): ?>
                  <span class="status pending"><?= e(t('Pending (QR Ready)')) ?></span>
                <?php else: ?>
                  <span class="status <?= e($st) ?>"><?= e(t(ucfirst($st))) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
            <tr><td colspan="11" class="empty"><?= e(t('No history yet.')) ?></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages > 1): ?>
        <nav class="audit-pagination" aria-label="<?= e(t('History pages')) ?>">
          <?php if ($page > 1): ?>
            <a href="?<?= e(http_build_query($paginationQuery + ['page' => $page - 1])) ?>"><?= e(t('Previous')) ?></a>
          <?php endif; ?>
          <span><?= e(t_format('Page {page} of {total}', ['page' => (string)$page, 'total' => (string)$totalPages])) ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="?<?= e(http_build_query($paginationQuery + ['page' => $page + 1])) ?>"><?= e(t('Next')) ?></a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </section>
  </main>
</div>
<script src="../assets/js/admin.js?v=15"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('history.view');

$search = trim($_GET['q'] ?? '');
$rows = BorrowRequest::history($search ?: null);
$pageTitle = 'History';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('History Log')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<link rel="stylesheet" href="../assets/css/history.css?v=14">
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
          <form method="get" class="searchbar">
            <input type="text" name="q" placeholder="<?= e(t('Search')) ?>" value="<?= e($search) ?>">
            <button type="submit">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
          </form>
          <a class="btn-export" href="actions.php?do=export_history"><?= e(t('Export Excel')) ?></a>
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table hist">
          <thead>
            <tr>
              <th><?= e(t('ID')) ?></th><th><?= e(t('Date & Time')) ?></th><th><?= e(t('Student / ID / Course')) ?></th>
              <th><?= e(t('Equipment & Serial No.')) ?></th><th><?= e(t('Quantity')) ?></th><th><?= e(t('Classroom')) ?></th><th><?= e(t('Teacher')) ?></th>
              <th><?= e(t('Purpose')) ?></th><th><?= e(t('Status')) ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $i => $r):
            $dt = $r['returned_at'] ?: $r['approved_at'] ?: $r['requested_at']; ?>
            <tr>
              <td>#<?= (int)$r['id'] ?></td>
              <td><?= e(date('M d, Y h:i A', strtotime($dt))) ?></td>
              <td>
                <strong><?= e($r['student_name']) ?></strong><br>
                <a><?= e($r['course']) ?></a><br>
                <small>(<?= e($r['student_uid']) ?>)</small>
              </td>
              <td><?= e($r['equipment_name']) ?> [<?= e($r['serial_number']) ?>]</td>
              <td><?= (int)($r['quantity'] ?? 1) ?></td>
              <td><?= e($r['classroom']) ?></td>
              <td><?= e($r['teacher']) ?></td>
              <td><?= e($r['purpose']) ?></td>
              <td><span class="status <?= e($r['status']) ?>"><?= e(t(ucfirst($r['status']))) ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="empty"><?= e(t('No history yet.')) ?></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
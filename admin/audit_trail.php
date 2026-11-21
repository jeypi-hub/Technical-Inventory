<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/AuditLog.php';
$user = User::requirePermission('audit.view');

$pageTitle = 'Audit Trail';
$events = AuditLog::eventLabels();
$action = $_GET['action'] ?? '';
$action = is_string($action) && isset($events[$action]) ? $action : null;
$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$perPage = 50;
$total = AuditLog::count($action, $search);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$page = min($totalPages, $page === false ? 1 : $page);
$rows = AuditLog::page($action, $search, $perPage, ($page - 1) * $perPage);
$csrfToken = $_SESSION['admin_audit_csrf'] ??= bin2hex(random_bytes(32));
$flash = $_SESSION['audit_trail_flash'] ?? null;
unset($_SESSION['audit_trail_flash']);
$roleLabels = [
    'admin' => 'Administrator',
    'staff' => 'Technical Staff',
    'faculty' => 'Faculty',
    'student' => 'Student',
];
$paginationQuery = array_filter(['action' => $action, 'q' => $search], static fn($value) => $value !== null && $value !== '');
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Audit Trail')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=16">
<link rel="stylesheet" href="../assets/css/history.css?v=26">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <?php if (is_array($flash)): ?>
      <div class="flash <?= e($flash['type'] ?? 'success') ?>" role="status"><?= e(t($flash['message'] ?? 'Audit trail cleared.')) ?></div>
    <?php endif; ?>

    <section class="card hist-card">
      <div class="card-head">
        <div>
          <h2><?= e(t('Recorded activity')) ?></h2>
          <p class="muted"><?= e(t('Audit records are retained until an administrator clears them.')) ?></p>
        </div>
        <form method="post" action="audit_actions.php"
              onsubmit="return confirm(<?= e(json_encode(t('Clear all audit logs?'))) ?>)">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="btn-export audit-clear-button"><?= e(t('Clear audit trail')) ?></button>
        </form>
      </div>

      <form method="get" class="audit-filters">
        <label>
          <span><?= e(t('Event')) ?></span>
          <select name="action">
            <option value=""><?= e(t('All events')) ?></option>
            <?php foreach ($events as $eventCode => $eventLabel): ?>
              <option value="<?= e($eventCode) ?>" <?= $action === $eventCode ? 'selected' : '' ?>><?= e(t($eventLabel)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span><?= e(t('Search actor or target')) ?></span>
          <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('Search')) ?>">
        </label>
        <button type="submit" class="btn-primary"><?= e(t('Filter')) ?></button>
      </form>

      <p class="muted"><?= e(t_format('{count} audit records', ['count' => (string)$total])) ?></p>
      <div class="table-wrap">
        <table class="data-table hist">
          <thead>
            <tr>
              <th><?= e(t('Date & Time')) ?></th>
              <th><?= e(t('Actor')) ?></th>
              <th><?= e(t('Event')) ?></th>
              <th><?= e(t('Target')) ?></th>
              <th><?= e(t('Details')) ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row):
            $actorName = $row['actor_name'] ?: t('Unknown');
            $actorId = $row['actor_user_id'] ? ' (' . $row['actor_user_id'] . ')' : '';
            $actorRole = $row['actor_role'] ? t($roleLabels[$row['actor_role']] ?? $row['actor_role']) : '';
            $target = $row['entity_type'] ?? '';
            if ($row['entity_id'] !== null && $row['entity_id'] !== '') {
                $target .= ' #' . $row['entity_id'];
            }
          ?>
            <tr>
              <td><?= e(date('M d, Y h:i:s A', strtotime($row['created_at']))) ?></td>
              <td><?= e($actorName . $actorId) ?><?php if ($actorRole !== ''): ?><br><small><?= e($actorRole) ?></small><?php endif; ?></td>
              <td><?= e(t($events[$row['action']] ?? $row['action'])) ?></td>
              <td><?= e($target !== '' ? $target : '—') ?></td>
              <td>
                <?php if (!empty($row['details'])): ?>
                  <details>
                    <summary><?= e(t('View details')) ?></summary>
                    <pre><?= e($row['details']) ?></pre>
                  </details>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="empty"><?= e(t('No audit records found.')) ?></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="audit-pagination" aria-label="<?= e(t('Audit pages')) ?>">
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
<script src="../assets/js/admin.js"></script>
</body>
</html>

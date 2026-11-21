<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
$user = User::requirePermission('roles.manage');

$pageTitle = 'Access Control';
$csrfToken = $_SESSION['admin_access_csrf'] ??= bin2hex(random_bytes(32));
$flash = $_SESSION['access_control_flash'] ?? null;
unset($_SESSION['access_control_flash']);
$users = db()->query(
    'SELECT id, user_id, name, role FROM users ORDER BY name, id'
)->fetchAll();
$roles = [
    'admin' => 'Administrator',
    'staff' => 'Technical Staff',
    'faculty' => 'Faculty',
    'student' => 'Student',
];
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Access Control')) ?> — ACLC</title>
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
      <div class="flash <?= e($flash['type'] ?? 'success') ?>" role="status"><?= e(t($flash['message'] ?? 'Role updated.')) ?></div>
    <?php endif; ?>

    <section class="card hist-card">
      <div class="card-head">
        <div>
          <h2><?= e(t('Fixed role permissions')) ?></h2>
          <p class="muted"><?= e(t('Permissions are built into the application; Admin can assign these roles to accounts.')) ?></p>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data-table hist">
          <thead>
            <tr><th><?= e(t('Role')) ?></th><th><?= e(t('Permissions')) ?></th></tr>
          </thead>
          <tbody>
            <tr><td><?= e(t('Administrator')) ?></td><td><?= e(t('Full access, including role management and the audit trail.')) ?></td></tr>
            <tr><td><?= e(t('Technical Staff')) ?></td><td><?= e(t('Manage equipment and checkouts, approve requests, and view borrowing history.')) ?></td></tr>
            <tr><td><?= e(t('Faculty')) ?></td><td><?= e(t('Submit advance borrow requests, scan QR codes, and view personal borrowing history.')) ?></td></tr>
            <tr><td><?= e(t('Student')) ?></td><td><?= e(t('Submit advance borrow requests, scan QR codes, and view personal borrowing history.')) ?></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card hist-card">
      <div class="card-head">
        <div>
          <h2><?= e(t('Accounts and roles')) ?></h2>
          <p class="muted"><?= e(t('Assign one of the fixed roles to an existing account.')) ?></p>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data-table hist">
          <thead>
            <tr>
              <th><?= e(t('Name')) ?></th>
              <th><?= e(t('User ID')) ?></th>
              <th><?= e(t('Role')) ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($users as $account): ?>
            <tr>
              <td><?= e($account['name']) ?></td>
              <td><?= e($account['user_id']) ?></td>
              <td>
                <?php if ((int)$account['id'] === (int)$user['id']): ?>
                  <strong><?= e(t($roles[$account['role']] ?? $account['role'])) ?></strong><br>
                  <small><?= e(t('Your own role cannot be changed here.')) ?></small>
                <?php else: ?>
                  <form method="post" action="access_actions.php" class="role-assignment-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$account['id'] ?>">
                    <select name="role" aria-label="<?= e(t('Role for')) ?> <?= e($account['name']) ?>">
                      <?php foreach ($roles as $role => $label): ?>
                        <option value="<?= e($role) ?>" <?= $account['role'] === $role ? 'selected' : '' ?>><?= e(t($label)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary"><?= e(t('Save role')) ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr><td colspan="3" class="empty"><?= e(t('No accounts found.')) ?></td></tr>
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

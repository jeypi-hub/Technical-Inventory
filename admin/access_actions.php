<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/AuditLog.php';
$actor = User::requirePermission('roles.manage');

function access_control_redirect(string $message, string $type = 'error'): void
{
    $_SESSION['access_control_flash'] = ['message' => $message, 'type' => $type];
    header('Location: access_control.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: access_control.php');
    exit;
}

$csrfToken = $_SESSION['admin_access_csrf'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || $csrfToken === '' || !hash_equals($csrfToken, $submittedToken)) {
    access_control_redirect('Your session expired. Reload the page and try again.');
}

$accountId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$role = $_POST['role'] ?? '';
if ($accountId === false || !is_string($role) || !in_array($role, ['admin', 'staff', 'student'], true)) {
    access_control_redirect('The selected account or role is invalid.');
}
if ((int)$accountId === (int)$actor['id']) {
    access_control_redirect('Your own role cannot be changed here.');
}

$pdo = db();
$pdo->beginTransaction();
try {
    $adminIds = $pdo->query(
        "SELECT id FROM users WHERE role = 'admin' ORDER BY id FOR UPDATE"
    )->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $pdo->prepare('SELECT id, user_id, name, role FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([(int)$accountId]);
    $target = $stmt->fetch();
    if (!$target) {
        $pdo->rollBack();
        access_control_redirect('Account not found.');
    }
    if ($target['role'] === $role) {
        $pdo->commit();
        access_control_redirect('Role unchanged.', 'success');
    }
    if ($target['role'] === 'admin' && $role !== 'admin' && count($adminIds) <= 1) {
        $pdo->rollBack();
        access_control_redirect('At least one administrator must remain.');
    }

    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role, (int)$accountId]);
    AuditLog::record('user.role_changed', 'user', (string)$accountId, [
        'user_id' => $target['user_id'],
        'name' => $target['name'],
        'from' => $target['role'],
        'to' => $role,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

access_control_redirect('Role updated.', 'success');

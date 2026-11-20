<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/AuditLog.php';
User::requirePermission('audit.clear');

function audit_trail_redirect(string $message, string $type = 'error'): void
{
    $_SESSION['audit_trail_flash'] = ['message' => $message, 'type' => $type];
    header('Location: audit_trail.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'clear') {
    header('Location: audit_trail.php');
    exit;
}

$csrfToken = $_SESSION['admin_audit_csrf'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || $csrfToken === '' || !hash_equals($csrfToken, $submittedToken)) {
    audit_trail_redirect('Your session expired. Reload the page and try again.');
}

$pdo = db();
$pdo->beginTransaction();
try {
    $deletedCount = (int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $pdo->exec('DELETE FROM audit_logs');
    AuditLog::record('audit.logs_cleared', 'audit_log', null, ['deleted_count' => $deletedCount]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

audit_trail_redirect('Audit trail cleared.', 'success');

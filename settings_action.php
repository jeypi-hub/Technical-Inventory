<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuditLog.php';
$user = User::requireAnyLogin();

function settings_redirect(string $message, string $type = 'error'): void
{
    $_SESSION['settings_flash'] = ['message' => $message, 'type' => $type];
    header('Location: settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$csrfToken = $_SESSION['settings_csrf'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || $csrfToken === '' || !hash_equals($csrfToken, $submittedToken)) {
    settings_redirect('Your session expired. Reload the page and try again.');
}

$accountId = (int)$user['id'];
switch ($_POST['action'] ?? '') {
    case 'preferences':
        $language = $_POST['language'] ?? '';
        if (!is_string($language) || !in_array($language, ['en', 'tl', 'ceb'], true)) {
            settings_redirect('The selected language is invalid.');
        }

        $darkMode = isset($_POST['dark_mode']) ? 1 : 0;
        $compactMode = isset($_POST['compact_mode']) ? 1 : 0;
        $before = [
            'language' => (string)($user['language'] ?? 'en'),
            'dark_mode' => empty($user['dark_mode']) ? 0 : 1,
            'compact_mode' => empty($user['compact_mode']) ? 0 : 1,
        ];
        $after = [
            'language' => $language,
            'dark_mode' => $darkMode,
            'compact_mode' => $compactMode,
        ];
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE users SET language = ?, dark_mode = ?, compact_mode = ? WHERE id = ?'
            );
            $stmt->execute([$language, $darkMode, $compactMode, $accountId]);
            if ($before !== $after) {
                AuditLog::record('user.preferences_changed', 'user', (string)$accountId, [
                    'before' => $before,
                    'after' => $after,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $_SESSION['user']['language'] = $language;
        $_SESSION['user']['dark_mode'] = $darkMode;
        $_SESSION['user']['compact_mode'] = $compactMode;
        settings_redirect('Settings saved.', 'success');

    case 'password':
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmPassword) ||
            $currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            settings_redirect('Please complete all password fields.');
        }

        $stmt = db()->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$accountId]);
        $storedPassword = $stmt->fetchColumn();
        if (!is_string($storedPassword) || !password_verify($currentPassword, $storedPassword)) {
            settings_redirect('Current password is incorrect.');
        }
        if (strlen($newPassword) < 8) {
            settings_redirect('New password must be at least 8 characters.');
        }
        if (!hash_equals($newPassword, $confirmPassword)) {
            settings_redirect('New passwords do not match.');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$passwordHash, $accountId]);
            AuditLog::record('user.password_changed', 'user', (string)$accountId);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $_SESSION['user']['password'] = $passwordHash;
        session_regenerate_id(true);
        settings_redirect('Password changed successfully.', 'success');

    default:
        settings_redirect('Your settings could not be saved.');
}

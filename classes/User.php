<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    public static function findByUserId(string $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public static function authenticate(string $userId, string $password, string $role): ?array
    {
        $u = self::findByUserId($userId);
        if (!$u || $u['role'] !== $role) return null;
        if (!password_verify($password, $u['password'])) return null;
        return $u;
    }

    public static function authenticateStudentOrStaff(string $userId, string $password): ?array
    {
        $u = self::findByUserId($userId);
        if (!$u || !in_array($u['role'], ['student', 'staff'], true)) return null;
        if (!password_verify($password, $u['password'])) return null;
        return $u;
    }

    /** Strict student ID format: e.g. C24-01-1524-MAN121  */
    public static function isValidStudentId(string $id): bool
    {
        return (bool) preg_match('/^C\d{2}-\d{2}-\d{4}-MAN\d{3}$/', $id);
    }

    public static function requireLogin(string $role): array
    {
        start_session_once();
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
            $redirect = $role === 'admin' ? '../auth/admin_login.php'
                                          : '../auth/student_login.php';
            header("Location: $redirect");
            exit;
        }
        return $_SESSION['user'];
    }

    public static function requireStaffOrAdmin(): array
    {
        start_session_once();
        if (empty($_SESSION['user']) ||
            !in_array($_SESSION['user']['role'], ['admin', 'staff'], true)) {
            header('Location: ../auth/student_login.php');
            exit;
        }
        return $_SESSION['user'];
    }
}

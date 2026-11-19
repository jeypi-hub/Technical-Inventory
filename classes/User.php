<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'operations.manage',
            'equipment.manage',
            'checkout.manage',
            'history.view',
            'roles.manage',
            'audit.view',
            'audit.clear',
        ],
        'staff' => [
            'operations.manage',
            'equipment.manage',
            'checkout.manage',
            'history.view',
        ],
        'student' => [
            'borrow.request',
            'borrow.history.own',
        ],
    ];

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
        $user = self::refreshSessionUser();
        if ($user === null || $user['role'] !== $role) {
            $redirect = $role === 'admin' ? '../auth/admin_login.php'
                                          : '../auth/student_login.php';
            header("Location: $redirect");
            exit;
        }
        return $user;
    }

    public static function requireStaffOrAdmin(): array
    {
        return self::requirePermission('operations.manage');
    }

    public static function permissionsForRole(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    public static function can(string $permission): bool
    {
        start_session_once();
        $role = $_SESSION['user']['role'] ?? '';
        return self::hasPermissionForRole($role, $permission);
    }

    public static function hasPermissionForRole(string $role, string $permission): bool
    {
        return in_array($permission, self::permissionsForRole($role), true);
    }

    public static function requirePermission(string $permission): array
    {
        start_session_once();
        $user = self::refreshSessionUser();
        if ($user === null) {
            header('Location: ../auth/student_login.php');
            exit;
        }
        if (!self::can($permission)) {
            http_response_code(403);
            exit(e(t('You do not have permission to access this page.')));
        }
        return $user;
    }

    public static function requireAnyLogin(): array
    {
        start_session_once();
        $user = self::refreshSessionUser();
        if (!is_array($user) || !in_array($user['role'] ?? '', ['admin', 'staff', 'student'], true)) {
            header('Location: auth/student_login.php');
            exit;
        }
        return $user;
    }

    private static function refreshSessionUser(): ?array
    {
        $currentUser = $_SESSION['user'] ?? null;
        if (!is_array($currentUser) || !isset($currentUser['id'])) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, user_id, name, email, course, role, language, dark_mode, compact_mode
             FROM users WHERE id = ?'
        );
        $stmt->execute([(int)$currentUser['id']]);
        $user = $stmt->fetch();
        if (!$user) {
            unset($_SESSION['user']);
            return null;
        }

        $_SESSION['user'] = $user;
        return $user;
    }
}

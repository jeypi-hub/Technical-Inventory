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
        'faculty' => [
            'borrow.request',
            'borrow.history.own',
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
        if (!$u || !in_array($u['role'], ['student', 'faculty', 'staff'], true)) return null;
        if (!password_verify($password, $u['password'])) return null;
        return $u;
    }

    /**
     * Unified authentication for all 4 roles (admin, staff, faculty, student).
     */
    public static function authenticateAny(string $userId, string $password): ?array
    {
        $u = self::findByUserId($userId);
        if (!$u || !in_array($u['role'], ['admin', 'staff', 'faculty', 'student'], true)) {
            return null;
        }
        if (!password_verify($password, $u['password'])) {
            return null;
        }
        return $u;
    }

    public static function dashboardPathForRole(string $role, string $prefix = '../'): string
    {
        return in_array($role, ['student', 'faculty'], true)
            ? $prefix . 'student/student_dashboard.php'
            : $prefix . 'admin/admin_dashboard.php';
    }

    public static function profileForUser(int $userId): ?array
    {
        $stmt = db()->prepare(
            'SELECT user_id, name, email, profile_picture
             FROM users WHERE id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public static function updateProfile(
        int $userId,
        string $name,
        ?string $email,
        ?string $profilePicture
    ): bool {
        if ($profilePicture === null) {
            $stmt = db()->prepare(
                'UPDATE users
                 SET name = ?, email = ?
                 WHERE id = ?'
            );
            return $stmt->execute([$name, $email, $userId]);
        }

        $stmt = db()->prepare(
            'UPDATE users
             SET name = ?, email = ?, profile_picture = ?
             WHERE id = ?'
        );
        return $stmt->execute([$name, $email, $profilePicture, $userId]);
    }

    /**
     * Validates Student ID (e.g. C24-01-1524-MAN121) or Faculty ID (e.g. FAC-001).
     */
    public static function isValidBorrowerId(string $id): bool
    {
        if (preg_match('/^C\d{2}-\d{2}-\d{4}-MAN\d{3}$/i', $id)) {
            return true;
        }
        if (preg_match('/^(FAC|FACULTY|PROF|INST)-[A-Z0-9-]{2,20}$/i', $id)) {
            return true;
        }
        return false;
    }

    public static function isValidStudentId(string $id): bool
    {
        return self::isValidBorrowerId($id);
    }

    public static function isFaculty(array $user): bool
    {
        if (($user['role'] ?? '') === 'faculty') {
            return true;
        }
        $uid = strtoupper((string)($user['user_id'] ?? ''));
        $course = strtoupper((string)($user['course'] ?? ''));
        return str_starts_with($uid, 'FAC') || str_contains($course, 'FACULTY');
    }

    public static function requireBorrower(): array
    {
        start_session_once();
        $user = self::refreshSessionUser();
        if ($user === null || !in_array($user['role'], ['student', 'faculty'], true)) {
            header('Location: ../auth/login.php');
            exit;
        }
        return $user;
    }

    public static function requireLogin(string $role): array
    {
        if ($role === 'student') {
            return self::requireBorrower();
        }
        start_session_once();
        $user = self::refreshSessionUser();
        if ($user === null || $user['role'] !== $role) {
            header('Location: ../auth/login.php');
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
            header('Location: ../auth/login.php');
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
        if (!is_array($user) || !in_array($user['role'] ?? '', ['admin', 'staff', 'faculty', 'student'], true)) {
            header('Location: auth/login.php');
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

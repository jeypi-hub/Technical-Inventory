<?php
require_once __DIR__ . '/../config/database.php';

class AuditLog
{
    private const EVENT_LABELS = [
        'auth.login.success' => 'Successful sign-in',
        'auth.login.failed' => 'Failed sign-in',
        'auth.logout' => 'Signed out',
        'equipment.created' => 'Equipment added',
        'equipment.updated' => 'Equipment updated',
        'equipment.deleted' => 'Equipment deleted',
        'borrow.requested' => 'Borrow request submitted',
        'borrow.cancelled' => 'Borrow request cancelled',
        'borrow.approved' => 'Borrow request approved',
        'borrow.rejected' => 'Borrow request rejected',
        'borrow.returned' => 'Borrowed equipment returned',
        'user.role_changed' => 'User role changed',
        'user.preferences_changed' => 'Account preferences changed',
        'user.password_changed' => 'Password changed',
        'audit.logs_cleared' => 'Audit logs cleared',
    ];

    public static function eventLabels(): array
    {
        return self::EVENT_LABELS;
    }

    public static function record(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        array $details = [],
        ?array $actor = null
    ): void {
        $actor ??= $_SESSION['user'] ?? null;
        $actor = is_array($actor) ? $actor : [];
        $encodedDetails = $details === []
            ? null
            : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $stmt = db()->prepare(
            'INSERT INTO audit_logs
                (actor_id, actor_user_id, actor_name, actor_role, action, entity_type, entity_id, details)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            isset($actor['id']) ? (int)$actor['id'] : null,
            isset($actor['user_id']) ? (string)$actor['user_id'] : null,
            isset($actor['name']) ? (string)$actor['name'] : null,
            isset($actor['role']) ? (string)$actor['role'] : null,
            $action,
            $entityType,
            $entityId,
            $encodedDetails,
        ]);
    }

    public static function count(?string $action = null, ?string $search = null): int
    {
        [$where, $params] = self::filters($action, $search);
        $stmt = db()->prepare("SELECT COUNT(*) FROM audit_logs $where");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function page(?string $action, ?string $search, int $limit, int $offset): array
    {
        [$where, $params] = self::filters($action, $search);
        $stmt = db()->prepare(
            "SELECT id, actor_user_id, actor_name, actor_role, action, entity_type, entity_id, details, created_at
             FROM audit_logs $where
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function filters(?string $action, ?string $search): array
    {
        $conditions = [];
        $params = [];
        if ($action !== null && isset(self::EVENT_LABELS[$action])) {
            $conditions[] = 'action = :action';
            $params[':action'] = $action;
        }
        if ($search !== null && $search !== '') {
            $conditions[] = '(actor_user_id LIKE :search_id OR actor_name LIKE :search_name
                              OR entity_type LIKE :search_type OR entity_id LIKE :search_entity
                              OR details LIKE :search_details)';
            $pattern = '%' . $search . '%';
            $params[':search_id'] = $pattern;
            $params[':search_name'] = $pattern;
            $params[':search_type'] = $pattern;
            $params[':search_entity'] = $pattern;
            $params[':search_details'] = $pattern;
        }
        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}

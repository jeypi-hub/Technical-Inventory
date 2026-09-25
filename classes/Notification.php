<?php
require_once __DIR__ . '/../config/database.php';

class Notification
{
    public static function push(int $userId, string $type, string $message, ?int $relatedId = null): void
    {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, type, message, related_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $message, $relatedId]);
    }

    public static function forUser(int $userId, int $limit = 20): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int)$limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAllRead(int $userId): void
    {
        $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}

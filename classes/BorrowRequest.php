<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Equipment.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Mailer.php';

class BorrowRequest
{
    public static function create(int $userId, int $equipmentId, int $quantity, string $purpose, string $classroom, string $teacher): int
    {
        $quantity = max(1, $quantity);
        $stmt = db()->prepare(
            'INSERT INTO borrow_requests (user_id, equipment_id, purpose, classroom, teacher, quantity, status)
             VALUES (?, ?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([$userId, $equipmentId, $purpose, $classroom, $teacher, $quantity]);
        $reqId = (int) db()->lastInsertId();

        // Notify all admins
        $admins = db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $a) {
            Notification::push((int)$a['id'], 'request',
                "New borrow request submitted for {$quantity} item(s) (Request #{$reqId})", $reqId);
        }
        return $reqId;
    }

    public static function pending(): array
    {
        return self::listByStatus(['pending']);
    }

    public static function active(): array
    {
        return self::listByStatus(['approved']);
    }

    public static function history(?string $search = null): array
    {
        $sql = "SELECT br.*, u.user_id AS student_uid, u.name AS student_name, u.course,
                       e.name AS equipment_name, e.serial_number
                FROM borrow_requests br
                JOIN users u ON u.id = br.user_id
                JOIN equipment e ON e.id = br.equipment_id
                WHERE br.status IN ('approved','rejected','returned','lost','broken')";
        $params = [];
        if ($search) {
            $sql .= " AND (u.name LIKE ? OR u.user_id LIKE ? OR e.name LIKE ? OR e.serial_number LIKE ?
                          OR br.classroom LIKE ? OR br.teacher LIKE ?)";
            $s = "%$search%";
            $params = [$s,$s,$s,$s,$s,$s];
        }
        $sql .= " ORDER BY br.requested_at DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT br.*, e.name AS equipment_name, e.serial_number
             FROM borrow_requests br
             JOIN equipment e ON e.id = br.equipment_id
             WHERE br.user_id = ?
             ORDER BY br.requested_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    private static function listByStatus(array $statuses): array
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = db()->prepare(
            "SELECT br.*, u.user_id AS student_uid, u.name AS student_name, u.course,
                    e.name AS equipment_name, e.serial_number
             FROM borrow_requests br
             JOIN users u ON u.id = br.user_id
             JOIN equipment e ON e.id = br.equipment_id
             WHERE br.status IN ($placeholders)
             ORDER BY br.requested_at DESC"
        );
        $stmt->execute($statuses);
        return $stmt->fetchAll();
    }

    public static function approve(int $id): void
    {
        $req = self::find($id);
        if (!$req) return;
        $quantity = max(1, (int)($req['quantity'] ?? 1));
        if (!Equipment::setStatusByNameQuantity((int)$req['equipment_id'], $quantity, 'borrowed')) return;
        db()->prepare("UPDATE borrow_requests SET status='approved', approved_at=NOW() WHERE id=?")
            ->execute([$id]);
        Notification::push((int)$req['user_id'], 'approved',
            "Your request for {$quantity} {$req['equipment_name']} item(s) was approved.", $id);
        Mailer::notifyDecision((int)$req['user_id'], 'approved', $req);
    }

    public static function reject(int $id): void
    {
        $req = self::find($id);
        if (!$req) return;
        db()->prepare("UPDATE borrow_requests SET status='rejected' WHERE id=?")->execute([$id]);
        Notification::push((int)$req['user_id'], 'rejected',
            "Your request for {$req['equipment_name']} was rejected.", $id);
        Mailer::notifyDecision((int)$req['user_id'], 'rejected', $req);
    }

    /** Student-initiated cancel — only allowed while status is still 'pending'. */
    public static function cancel(int $id, int $userId): bool
    {
        $stmt = db()->prepare(
            "DELETE FROM borrow_requests WHERE id = ? AND user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    /** condition: good | damaged | lost */
    public static function markReturn(int $id, string $condition): void
    {
        $req = self::find($id);
        if (!$req) return;

        $statusMap = ['good' => 'returned', 'damaged' => 'broken', 'lost' => 'lost'];
        $equipMap  = ['good' => 'available', 'damaged' => 'maintenance', 'lost' => 'lost'];
        $status = $statusMap[$condition] ?? 'returned';
        $eqStatus = $equipMap[$condition] ?? 'available';

        db()->prepare(
            "UPDATE borrow_requests
             SET status=?, condition_on_return=?, returned_at=NOW()
             WHERE id=?"
        )->execute([$status, $condition, $id]);
        Equipment::setStatusForBorrowedQuantity(
            (int)$req['equipment_id'],
            max(1, (int)($req['quantity'] ?? 1)),
            $eqStatus
        );

        Notification::push((int)$req['user_id'], 'returned',
            "Your borrowed item {$req['equipment_name']} was marked as {$status}.", $id);
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            "SELECT br.*, e.name AS equipment_name, e.serial_number
             FROM borrow_requests br
             JOIN equipment e ON e.id = br.equipment_id
             WHERE br.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Monthly borrowed vs returned counts for a given year */
    public static function monthlyActivity(int $year): array
    {
        $stmt = db()->prepare(
            "SELECT MONTH(approved_at) AS m,
                    SUM(CASE WHEN status IN ('approved','returned','broken','lost') THEN 1 ELSE 0 END) AS borrowed,
                    SUM(CASE WHEN status IN ('returned','broken','lost') THEN 1 ELSE 0 END) AS returned
             FROM borrow_requests
             WHERE YEAR(approved_at) = ?
             GROUP BY MONTH(approved_at)"
        );
        $stmt->execute([$year]);
        $data = array_fill(1, 12, ['borrowed' => 0, 'returned' => 0]);
        foreach ($stmt->fetchAll() as $r) {
            $data[(int)$r['m']] = [
                'borrowed' => (int) $r['borrowed'],
                'returned' => (int) $r['returned'],
            ];
        }
        return $data;
    }

    public static function countPending(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM borrow_requests WHERE status='pending'")
                        ->fetchColumn();
    }

    public static function countBorrowing(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM borrow_requests WHERE status='approved'")
                        ->fetchColumn();
    }
}

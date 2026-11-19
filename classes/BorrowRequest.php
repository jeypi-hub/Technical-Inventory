<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Equipment.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/AuditLog.php';

class BorrowRequest
{
    public static function create(int $userId, int $equipmentId, int $quantity, string $purpose, string $classroom, string $teacher): int
    {
        $quantity = max(1, $quantity);
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO borrow_requests (user_id, equipment_id, purpose, classroom, teacher, quantity, status)
                 VALUES (?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->execute([$userId, $equipmentId, $purpose, $classroom, $teacher, $quantity]);
            $reqId = (int) $pdo->lastInsertId();
            AuditLog::record('borrow.requested', 'borrow_request', (string)$reqId, [
                'equipment_id' => $equipmentId,
                'quantity' => $quantity,
            ]);
            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // Notify all administrators and staff
        $managementUsers = $pdo->query(
            "SELECT id FROM users WHERE role IN ('admin', 'staff')"
        )->fetchAll();
        foreach ($managementUsers as $managementUser) {
            Notification::push((int)$managementUser['id'], 'request',
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

    public static function approve(int $id): bool
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $req = self::findForUpdate($pdo, $id);
            if (!$req || $req['status'] !== 'pending') {
                $pdo->rollBack();
                return false;
            }
            $quantity = max(1, (int)($req['quantity'] ?? 1));
            if (!Equipment::setStatusByNameQuantity((int)$req['equipment_id'], $quantity, 'borrowed')) {
                $pdo->rollBack();
                return false;
            }
            $stmt = $pdo->prepare(
                "UPDATE borrow_requests SET status='approved', approved_at=NOW() WHERE id=? AND status='pending'"
            );
            $stmt->execute([$id]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            AuditLog::record('borrow.approved', 'borrow_request', (string)$id, [
                'equipment' => $req['equipment_name'],
                'quantity' => $quantity,
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        Notification::push((int)$req['user_id'], 'approved',
            "Your request for {$quantity} {$req['equipment_name']} item(s) was approved.", $id);
        Mailer::notifyDecision((int)$req['user_id'], 'approved', $req);
        return true;
    }

    public static function reject(int $id): bool
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $req = self::findForUpdate($pdo, $id);
            if (!$req || $req['status'] !== 'pending') {
                $pdo->rollBack();
                return false;
            }
            $stmt = $pdo->prepare(
                "UPDATE borrow_requests SET status='rejected' WHERE id=? AND status='pending'"
            );
            $stmt->execute([$id]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            AuditLog::record('borrow.rejected', 'borrow_request', (string)$id, [
                'equipment' => $req['equipment_name'],
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        Notification::push((int)$req['user_id'], 'rejected',
            "Your request for {$req['equipment_name']} was rejected.", $id);
        Mailer::notifyDecision((int)$req['user_id'], 'rejected', $req);
        return true;
    }

    public static function cancel(int $id, int $userId): bool
    {
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $stmt = $pdo->prepare(
                "SELECT br.quantity, e.name AS equipment_name
                 FROM borrow_requests br
                 JOIN equipment e ON e.id = br.equipment_id
                 WHERE br.id = ? AND br.user_id = ? AND br.status = 'pending'
                 FOR UPDATE"
            );
            $stmt->execute([$id, $userId]);
            $request = $stmt->fetch();
            if (!$request) {
                if ($ownsTransaction) {
                    $pdo->rollBack();
                }
                return false;
            }

            $stmt = $pdo->prepare(
                "DELETE FROM borrow_requests WHERE id = ? AND user_id = ? AND status = 'pending'"
            );
            $stmt->execute([$id, $userId]);
            if ($stmt->rowCount() !== 1) {
                if ($ownsTransaction) {
                    $pdo->rollBack();
                }
                return false;
            }
            AuditLog::record('borrow.cancelled', 'borrow_request', (string)$id, [
                'equipment' => $request['equipment_name'],
                'quantity' => (int)$request['quantity'],
            ]);
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function markReturn(int $id, string $condition): bool
    {
        $statusMap = ['good' => 'returned', 'damaged' => 'broken', 'lost' => 'lost'];
        $equipMap  = ['good' => 'available', 'damaged' => 'maintenance', 'lost' => 'lost'];
        if (!isset($statusMap[$condition])) {
            return false;
        }
        $status = $statusMap[$condition];
        $eqStatus = $equipMap[$condition];
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $req = self::findForUpdate($pdo, $id);
            if (!$req || $req['status'] !== 'approved') {
                $pdo->rollBack();
                return false;
            }
            $stmt = $pdo->prepare(
                "UPDATE borrow_requests
                 SET status=?, condition_on_return=?, returned_at=NOW()
                 WHERE id=? AND status='approved'"
            );
            $stmt->execute([$status, $condition, $id]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            Equipment::setStatusForBorrowedQuantity(
                (int)$req['equipment_id'],
                max(1, (int)($req['quantity'] ?? 1)),
                $eqStatus
            );
            AuditLog::record('borrow.returned', 'borrow_request', (string)$id, [
                'equipment' => $req['equipment_name'],
                'condition' => $condition,
                'status' => $status,
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        Notification::push((int)$req['user_id'], 'returned',
            "Your borrowed item {$req['equipment_name']} was marked as {$status}.", $id);
        return true;
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

    private static function findForUpdate(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT br.*, e.name AS equipment_name, e.serial_number
             FROM borrow_requests br
             JOIN equipment e ON e.id = br.equipment_id
             WHERE br.id = ? FOR UPDATE'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

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

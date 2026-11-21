<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuditLog.php';

class Equipment
{
    public static function generateSerial(): string
    {
        do {
            $serial = 'SN-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = db()->prepare('SELECT 1 FROM equipment WHERE serial_number = ?');
            $stmt->execute([$serial]);
        } while ($stmt->fetch());
        return $serial;
    }

    public static function all(?string $search = null): array
    {
        if (class_exists('BorrowRequest')) {
            BorrowRequest::syncOverdueAndLost();
        }
        $sql = 'SELECT * FROM equipment';
        $params = [];
        if ($search) {
            $sql .= ' WHERE name LIKE ? OR serial_number LIKE ? OR status LIKE ?';
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function availableCountsByName(): array
    {
        if (class_exists('BorrowRequest')) {
            BorrowRequest::syncOverdueAndLost();
        }
        $rows = db()->query(
            "SELECT name, COUNT(*) AS available_count
             FROM equipment
             WHERE status = 'available'
             GROUP BY name"
        )->fetchAll();
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['name']] = (int) $row['available_count'];
        }
        return $counts;
    }

    public static function statusBreakdownByName(): array
    {
        if (class_exists('BorrowRequest')) {
            BorrowRequest::syncOverdueAndLost();
        }
        $rows = db()->query(
            "SELECT name, status, COUNT(*) AS c
             FROM equipment
             GROUP BY name, status"
        )->fetchAll();

        $breakdown = [];
        foreach ($rows as $row) {
            $name = $row['name'];
            if (!isset($breakdown[$name])) {
                $breakdown[$name] = [
                    'total' => 0,
                    'available' => 0,
                    'pending' => 0,
                    'borrowed' => 0,
                    'overdue' => 0,
                    'maintenance' => 0,
                    'lost' => 0,
                ];
            }
            $st = $row['status'];
            $cnt = (int) $row['c'];
            if (isset($breakdown[$name][$st])) {
                $breakdown[$name][$st] += $cnt;
            }
            $breakdown[$name]['total'] += $cnt;
        }
        return $breakdown;
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM equipment WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, ?string $imagePath): int
    {
        $serial = self::generateSerial();
        $stmt = db()->prepare(
            'INSERT INTO equipment (name, serial_number, image, status) VALUES (?, ?, ?, "available")'
        );
        $stmt->execute([$name, $serial, $imagePath]);
        return (int) db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM equipment WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT status FROM equipment WHERE id = ? FOR UPDATE');
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $stmt->execute([$id]);
            $previousStatus = $stmt->fetchColumn();
            if (!is_string($previousStatus) || $previousStatus === $status) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return;
            }
            $pdo->prepare('UPDATE equipment SET status = ? WHERE id = ?')->execute([$status, $id]);
            AuditLog::record('equipment.updated', 'equipment', (string)$id, [
                'status_from' => $previousStatus,
                'status_to' => $status,
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
    }

    /**
     * Reserves $quantity available units of the equipment's name by marking them 'pending'.
     * Returns an array of reserved equipment IDs, or null if insufficient available units.
     */
    public static function reserveUnits(int $equipmentId, int $quantity): ?array
    {
        $equipment = self::find($equipmentId);
        if (!$equipment || $quantity < 1) {
            return null;
        }

        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM equipment
                 WHERE name = ? AND status = 'available'
                 ORDER BY (id = ?) DESC, id ASC
                 LIMIT " . (int)$quantity . " FOR UPDATE"
            );
            $stmt->execute([$equipment['name'], $equipmentId]);
            $rows = $stmt->fetchAll();
            if (count($rows) < $quantity) {
                if ($ownsTransaction) {
                    $pdo->rollBack();
                }
                return null;
            }

            $ids = array_map('intval', array_column($rows, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = $pdo->prepare("UPDATE equipment SET status = 'pending' WHERE id IN ($placeholders)");
            $update->execute($ids);

            foreach ($ids as $id) {
                AuditLog::record('equipment.updated', 'equipment', (string)$id, [
                    'status_from' => 'available',
                    'status_to' => 'pending',
                ]);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $ids;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates the status of specific reserved equipment IDs (with fallback by name/quantity for legacy rows).
     */
    public static function updateUnitsStatus(
        array $equipmentIds,
        string $newStatus,
        ?int $fallbackEquipmentId = null,
        int $fallbackQuantity = 1,
        array $fromStatuses = ['pending', 'borrowed', 'overdue']
    ): bool {
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $targetRows = [];
            $cleanIds = array_values(array_filter(array_map('intval', $equipmentIds), static fn($v) => $v > 0));

            if (!empty($cleanIds)) {
                $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
                $stmt = $pdo->prepare("SELECT id, status FROM equipment WHERE id IN ($placeholders) FOR UPDATE");
                $stmt->execute($cleanIds);
                $targetRows = $stmt->fetchAll();
            } elseif ($fallbackEquipmentId !== null && $fallbackQuantity > 0 && !empty($fromStatuses)) {
                $equipment = self::find($fallbackEquipmentId);
                if ($equipment) {
                    $statusPlaceholders = implode(',', array_fill(0, count($fromStatuses), '?'));
                    $stmt = $pdo->prepare(
                        "SELECT id, status FROM equipment
                         WHERE name = ? AND status IN ($statusPlaceholders)
                         ORDER BY (id = ?) DESC, id ASC
                         LIMIT " . (int)$fallbackQuantity . " FOR UPDATE"
                    );
                    $stmt->execute(array_merge([$equipment['name']], $fromStatuses, [$fallbackEquipmentId]));
                    $targetRows = $stmt->fetchAll();
                }
            }

            if (empty($targetRows)) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return false;
            }

            $idsToUpdate = array_map('intval', array_column($targetRows, 'id'));
            $placeholders = implode(',', array_fill(0, count($idsToUpdate), '?'));
            $pdo->prepare("UPDATE equipment SET status = ? WHERE id IN ($placeholders)")
                ->execute(array_merge([$newStatus], $idsToUpdate));

            foreach ($targetRows as $row) {
                if ($row['status'] !== $newStatus) {
                    AuditLog::record('equipment.updated', 'equipment', (string)$row['id'], [
                        'status_from' => $row['status'],
                        'status_to' => $newStatus,
                    ]);
                }
            }

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

    public static function setStatusByNameQuantity(int $equipmentId, int $quantity, string $status): bool
    {
        return self::updateUnitsStatus([], $status, $equipmentId, $quantity, ['available', 'pending']);
    }

    public static function setStatusForBorrowedQuantity(int $equipmentId, int $quantity, string $status): void
    {
        self::updateUnitsStatus([], $status, $equipmentId, $quantity, ['borrowed', 'overdue']);
    }

    public static function counts(): array
    {
        if (class_exists('BorrowRequest')) {
            BorrowRequest::syncOverdueAndLost();
        }
        $rows = db()->query(
            "SELECT status, COUNT(*) AS c FROM equipment GROUP BY status"
        )->fetchAll();
        $out = [
            'total' => 0,
            'available' => 0,
            'pending' => 0,
            'borrowed' => 0,
            'overdue' => 0,
            'maintenance' => 0,
            'lost' => 0,
        ];
        foreach ($rows as $r) {
            $st = $r['status'];
            if (isset($out[$st])) {
                $out[$st] = (int) $r['c'];
            }
            $out['total'] += (int) $r['c'];
        }
        return $out;
    }
}

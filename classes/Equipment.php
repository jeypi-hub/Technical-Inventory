<?php
require_once __DIR__ . '/../config/database.php';

class Equipment
{
    /** Auto-generate a unique serial number like SN-A1B2C3D4 */
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
        $sql = 'SELECT * FROM equipment';
        $params = [];
        if ($search) {
            $sql .= ' WHERE name LIKE ? OR serial_number LIKE ?';
            $params = ["%$search%", "%$search%"];
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
        $stmt = db()->prepare('UPDATE equipment SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function counts(): array
    {
        $rows = db()->query(
            "SELECT status, COUNT(*) AS c FROM equipment GROUP BY status"
        )->fetchAll();
        $out = ['total' => 0, 'available' => 0, 'borrowed' => 0, 'maintenance' => 0, 'lost' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['total'] += (int) $r['c'];
        }
        return $out;
    }
}

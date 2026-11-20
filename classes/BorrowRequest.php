<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Equipment.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/AuditLog.php';

class BorrowRequest
{
    /**
     * Allowed borrow durations in minutes (30-min intervals up to 4 hours max).
     */
    public const VALID_DURATIONS = [30, 60, 90, 120, 150, 180, 210, 240];

    private static bool $syncedInRequest = false;

    public static function durationOptions(): array
    {
        return [
            30  => '30 Minutes',
            60  => '1 Hour',
            90  => '1 Hour 30 Minutes',
            120 => '2 Hours',
            150 => '2 Hours 30 Minutes',
            180 => '3 Hours',
            210 => '3 Hours 30 Minutes',
            240 => '4 Hours (Max)',
        ];
    }

    public static function formatDuration(int $minutes): string
    {
        $minutes = max(30, $minutes);
        $hrs = intdiv($minutes, 60);
        $mins = $minutes % 60;
        $parts = [];
        if ($hrs > 0) {
            $parts[] = $hrs . ' ' . ($hrs === 1 ? 'hr' : 'hrs');
        }
        if ($mins > 0) {
            $parts[] = $mins . ' mins';
        }
        return implode(' ', $parts);
    }

    public static function parseReservedIds(?string $raw, int $fallbackEquipmentId = 0): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('intval', $decoded), static fn($id) => $id > 0));
            }
        }
        return $fallbackEquipmentId > 0 ? [$fallbackEquipmentId] : [];
    }

    /**
     * Automatically escalates active borrowed requests:
     * 1. Borrowed > selected duration (due_at < NOW()) -> 'overdue'
     * 2. Borrowed or Overdue > 24 hours since borrowed_at -> 'lost'
     */
    public static function syncOverdueAndLost(bool $force = false): void
    {
        if (self::$syncedInRequest && !$force) {
            return;
        }
        self::$syncedInRequest = true;

        $pdo = db();
        try {
            // 1. Check for items borrowed for more than 24 hours (86400 seconds) -> mark as LOST
            $lostStmt = $pdo->query(
                "SELECT br.*, e.name AS equipment_name
                 FROM borrow_requests br
                 JOIN equipment e ON e.id = br.equipment_id
                 WHERE br.status IN ('borrowed', 'overdue')
                   AND br.borrowed_at IS NOT NULL
                   AND TIMESTAMPDIFF(SECOND, br.borrowed_at, NOW()) >= 86400"
            );
            $lostCandidates = $lostStmt ? $lostStmt->fetchAll() : [];

            foreach ($lostCandidates as $req) {
                $reqId = (int)$req['id'];
                $ownsTx = !$pdo->inTransaction();
                if ($ownsTx) {
                    $pdo->beginTransaction();
                }
                try {
                    $locked = self::findForUpdate($pdo, $reqId);
                    if (!$locked || !in_array($locked['status'], ['borrowed', 'overdue'], true)) {
                        if ($ownsTx) $pdo->commit();
                        continue;
                    }
                    $upd = $pdo->prepare(
                        "UPDATE borrow_requests
                         SET status = 'lost', condition_on_return = 'lost', returned_at = NOW()
                         WHERE id = ? AND status IN ('borrowed', 'overdue')"
                    );
                    $upd->execute([$reqId]);
                    if ($upd->rowCount() === 1) {
                        $reservedIds = self::parseReservedIds($locked['reserved_equipment_ids'] ?? null, (int)$locked['equipment_id']);
                        Equipment::updateUnitsStatus(
                            $reservedIds,
                            'lost',
                            (int)$locked['equipment_id'],
                            max(1, (int)($locked['quantity'] ?? 1)),
                            ['borrowed', 'overdue', 'pending']
                        );
                        AuditLog::record('borrow.lost_auto', 'borrow_request', (string)$reqId, [
                            'equipment' => $locked['equipment_name'],
                            'reason' => 'Exceeded 24 hours borrow duration',
                        ]);
                    }
                    if ($ownsTx) {
                        $pdo->commit();
                    }
                    Notification::push(
                        (int)$locked['user_id'],
                        'lost',
                        "Your borrowed item {$locked['equipment_name']} (Request #{$reqId}) exceeded 24 hours and is now marked as LOST.",
                        $reqId
                    );
                    self::notifyManagement(
                        'lost',
                        "Borrowed item {$locked['equipment_name']} (Request #{$reqId}) exceeded 24 hours and was automatically marked as LOST.",
                        $reqId
                    );
                } catch (Throwable $inner) {
                    if ($ownsTx && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                }
            }

            // 2. Check for items that exceeded their selected borrow duration (NOW() > due_at) -> mark as OVERDUE
            $overdueStmt = $pdo->query(
                "SELECT br.*, e.name AS equipment_name
                 FROM borrow_requests br
                 JOIN equipment e ON e.id = br.equipment_id
                 WHERE br.status = 'borrowed'
                   AND br.due_at IS NOT NULL
                   AND br.due_at < NOW()
                   AND (br.borrowed_at IS NULL OR TIMESTAMPDIFF(SECOND, br.borrowed_at, NOW()) < 86400)"
            );
            $overdueCandidates = $overdueStmt ? $overdueStmt->fetchAll() : [];

            foreach ($overdueCandidates as $req) {
                $reqId = (int)$req['id'];
                $ownsTx = !$pdo->inTransaction();
                if ($ownsTx) {
                    $pdo->beginTransaction();
                }
                try {
                    $locked = self::findForUpdate($pdo, $reqId);
                    if (!$locked || $locked['status'] !== 'borrowed') {
                        if ($ownsTx) $pdo->commit();
                        continue;
                    }
                    $upd = $pdo->prepare(
                        "UPDATE borrow_requests SET status = 'overdue' WHERE id = ? AND status = 'borrowed'"
                    );
                    $upd->execute([$reqId]);
                    if ($upd->rowCount() === 1) {
                        $reservedIds = self::parseReservedIds($locked['reserved_equipment_ids'] ?? null, (int)$locked['equipment_id']);
                        Equipment::updateUnitsStatus(
                            $reservedIds,
                            'overdue',
                            (int)$locked['equipment_id'],
                            max(1, (int)($locked['quantity'] ?? 1)),
                            ['borrowed']
                        );
                        AuditLog::record('borrow.overdue', 'borrow_request', (string)$reqId, [
                            'equipment' => $locked['equipment_name'],
                            'due_at' => $locked['due_at'],
                        ]);
                    }
                    if ($ownsTx) {
                        $pdo->commit();
                    }
                    Notification::push(
                        (int)$locked['user_id'],
                        'overdue',
                        "Your borrowed item {$locked['equipment_name']} (Request #{$reqId}) has exceeded its borrow duration and is now OVERDUE.",
                        $reqId
                    );
                    self::notifyManagement(
                        'overdue',
                        "Borrowed item {$locked['equipment_name']} (Request #{$reqId}) is now OVERDUE.",
                        $reqId
                    );
                } catch (Throwable $inner) {
                    if ($ownsTx && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                }
            }
        } catch (Throwable $e) {
            // Silently ignore if migration_v7 columns are not yet present
        }
    }

    private static function notifyManagement(string $type, string $message, ?int $relatedId = null): void
    {
        $managementUsers = db()->query(
            "SELECT id FROM users WHERE role IN ('admin', 'staff')"
        )->fetchAll();
        foreach ($managementUsers as $managementUser) {
            Notification::push((int)$managementUser['id'], $type, $message, $relatedId);
        }
    }

    public static function create(
        int $userId,
        int $equipmentId,
        int $quantity,
        string $purpose,
        string $classroom,
        string $teacher,
        int $durationMinutes = 60
    ): int {
        $quantity = max(1, $quantity);
        if (!in_array($durationMinutes, self::VALID_DURATIONS, true)) {
            $durationMinutes = 60;
        }

        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $reservedIds = Equipment::reserveUnits($equipmentId, $quantity);
            if (!$reservedIds) {
                throw new RuntimeException('Insufficient available equipment units to reserve.');
            }
            $primaryEquipmentId = (int)$reservedIds[0];
            $stmt = $pdo->prepare(
                'INSERT INTO borrow_requests
                 (user_id, equipment_id, reserved_equipment_ids, purpose, classroom, teacher, quantity, duration_minutes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->execute([
                $userId,
                $primaryEquipmentId,
                json_encode($reservedIds),
                $purpose,
                $classroom,
                $teacher,
                $quantity,
                $durationMinutes,
            ]);
            $reqId = (int) $pdo->lastInsertId();
            AuditLog::record('borrow.requested', 'borrow_request', (string)$reqId, [
                'equipment_id' => $primaryEquipmentId,
                'reserved_equipment_ids' => $reservedIds,
                'quantity' => $quantity,
                'duration_minutes' => $durationMinutes,
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

        $durLabel = self::formatDuration($durationMinutes);
        self::notifyManagement(
            'request',
            "New borrow request #{$reqId} for {$quantity} item(s) ({$durLabel}) is pending approval.",
            $reqId
        );
        return $reqId;
    }

    public static function pending(): array
    {
        self::syncOverdueAndLost();
        return self::listByStatus(['pending']);
    }

    public static function approvedAwaitingScan(): array
    {
        self::syncOverdueAndLost();
        return self::listByStatus(['approved']);
    }

    public static function active(): array
    {
        self::syncOverdueAndLost();
        return self::listByStatus(['approved', 'borrowed', 'overdue']);
    }

    public static function history(?string $search = null): array
    {
        self::syncOverdueAndLost();
        $sql = "SELECT br.*, u.user_id AS student_uid, u.name AS student_name, u.course,
                       e.name AS equipment_name, e.serial_number
                FROM borrow_requests br
                JOIN users u ON u.id = br.user_id
                JOIN equipment e ON e.id = br.equipment_id
                WHERE br.status IN ('pending','approved','borrowed','overdue','rejected','returned','lost','broken')";
        $params = [];
        if ($search) {
            $sql .= " AND (u.name LIKE ? OR u.user_id LIKE ? OR e.name LIKE ? OR e.serial_number LIKE ?
                          OR br.classroom LIKE ? OR br.teacher LIKE ? OR br.status LIKE ?)";
            $s = "%$search%";
            $params = [$s,$s,$s,$s,$s,$s,$s];
        }
        $sql .= " ORDER BY br.requested_at DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        self::syncOverdueAndLost();
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

    /**
     * Technical Staff approves the request and generates the QR code token.
     * Equipment status remains 'pending' (reserved) until the student scans the QR code.
     */
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
            $qrToken = 'ACLC-REQ-' . $id . '-' . strtoupper(bin2hex(random_bytes(4)));

            $stmt = $pdo->prepare(
                "UPDATE borrow_requests
                 SET status = 'approved', approved_at = NOW(), qr_token = ?
                 WHERE id = ? AND status = 'pending'"
            );
            $stmt->execute([$qrToken, $id]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            AuditLog::record('borrow.approved', 'borrow_request', (string)$id, [
                'equipment' => $req['equipment_name'],
                'quantity' => $quantity,
                'qr_token' => $qrToken,
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        Notification::push(
            (int)$req['user_id'],
            'approved',
            "Your request #{$id} for {$quantity} {$req['equipment_name']} item(s) was approved. Go to the Technical Department and scan the QR code to start your borrow timer.",
            $id
        );
        Mailer::notifyDecision((int)$req['user_id'], 'approved', $req);
        return true;
    }

    /**
     * Student scans the staff-generated QR code at the Technical Department to start the borrow timer.
     */
    public static function activateByQrToken(string $rawCode, int $userId): array
    {
        self::syncOverdueAndLost();
        $code = trim($rawCode);
        if ($code === '') {
            return ['ok' => false, 'message' => 'Please scan a valid QR code.'];
        }

        // Support JSON payload or direct token string
        if (str_starts_with($code, '{')) {
            $decoded = json_decode($code, true);
            if (is_array($decoded) && !empty($decoded['qr_token'])) {
                $code = trim((string)$decoded['qr_token']);
            }
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "SELECT br.*, e.name AS equipment_name, e.serial_number
                 FROM borrow_requests br
                 JOIN equipment e ON e.id = br.equipment_id
                 WHERE UPPER(br.qr_token) = UPPER(?)
                 FOR UPDATE"
            );
            $stmt->execute([$code]);
            $req = $stmt->fetch();

            // Fallback: if the student scanned an equipment serial number for an approved request they own
            if (!$req) {
                $stmtSerial = $pdo->prepare(
                    "SELECT br.*, e.name AS equipment_name, e.serial_number
                     FROM borrow_requests br
                     JOIN equipment e ON e.id = br.equipment_id
                     WHERE br.user_id = ? AND br.status = 'approved' AND UPPER(e.serial_number) = UPPER(?)
                     ORDER BY br.approved_at DESC LIMIT 1
                     FOR UPDATE"
                );
                $stmtSerial->execute([$userId, $code]);
                $req = $stmtSerial->fetch();
            }

            if (!$req) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'Invalid QR code or request not found.'];
            }

            if ((int)$req['user_id'] !== $userId) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'This QR code belongs to another borrower\'s request.'];
            }

            if ($req['status'] === 'borrowed' || $req['status'] === 'overdue') {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'This request has already been scanned and its timer is running.'];
            }

            if ($req['status'] !== 'approved') {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'This request is currently "' . $req['status'] . '" and cannot be activated.'];
            }

            $reqId = (int)$req['id'];
            $durationMinutes = max(30, min(240, (int)($req['duration_minutes'] ?? 60)));
            $quantity = max(1, (int)($req['quantity'] ?? 1));

            $upd = $pdo->prepare(
                "UPDATE borrow_requests
                 SET status = 'borrowed',
                     borrowed_at = NOW(),
                     due_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                 WHERE id = ? AND status = 'approved'"
            );
            $upd->execute([$durationMinutes, $reqId]);
            if ($upd->rowCount() !== 1) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'Could not activate borrow timer.'];
            }

            $reservedIds = self::parseReservedIds($req['reserved_equipment_ids'] ?? null, (int)$req['equipment_id']);
            Equipment::updateUnitsStatus(
                $reservedIds,
                'borrowed',
                (int)$req['equipment_id'],
                $quantity,
                ['pending', 'available']
            );

            AuditLog::record('borrow.qr_scanned', 'borrow_request', (string)$reqId, [
                'equipment' => $req['equipment_name'],
                'quantity' => $quantity,
                'duration_minutes' => $durationMinutes,
            ]);

            $updatedReq = self::findForUpdate($pdo, $reqId);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $durLabel = self::formatDuration($durationMinutes);
        $dueFormatted = !empty($updatedReq['due_at']) ? date('h:i A', strtotime($updatedReq['due_at'])) : '';
        Notification::push(
            $userId,
            'approved',
            "QR code scanned for {$req['equipment_name']} (Request #{$reqId}). Your {$durLabel} timer has started (Due at {$dueFormatted}).",
            $reqId
        );
        self::notifyManagement(
            'approved',
            "QR code scanned for Request #{$reqId} ({$req['equipment_name']}). Borrow duration ({$durLabel}) is now active.",
            $reqId
        );

        return [
            'ok' => true,
            'request_id' => $reqId,
            'equipment_name' => $req['equipment_name'],
            'serial_number' => $req['serial_number'],
            'duration_label' => $durLabel,
            'borrowed_at' => $updatedReq['borrowed_at'] ?? null,
            'due_at' => $updatedReq['due_at'] ?? null,
            'message' => "QR verified! Borrow timer ({$durLabel}) started for {$req['equipment_name']} — due at {$dueFormatted}.",
        ];
    }

    public static function reject(int $id): bool
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $req = self::findForUpdate($pdo, $id);
            if (!$req || !in_array($req['status'], ['pending', 'approved'], true)) {
                $pdo->rollBack();
                return false;
            }
            $stmt = $pdo->prepare(
                "UPDATE borrow_requests SET status='rejected' WHERE id=? AND status IN ('pending', 'approved')"
            );
            $stmt->execute([$id]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            $reservedIds = self::parseReservedIds($req['reserved_equipment_ids'] ?? null, (int)$req['equipment_id']);
            Equipment::updateUnitsStatus(
                $reservedIds,
                'available',
                (int)$req['equipment_id'],
                max(1, (int)($req['quantity'] ?? 1)),
                ['pending']
            );
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
                "SELECT br.id, br.equipment_id, br.reserved_equipment_ids, br.quantity, e.name AS equipment_name
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

            $reservedIds = self::parseReservedIds($request['reserved_equipment_ids'] ?? null, (int)$request['equipment_id']);
            Equipment::updateUnitsStatus(
                $reservedIds,
                'available',
                (int)$request['equipment_id'],
                max(1, (int)$request['quantity']),
                ['pending']
            );

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
            if (!$req || !in_array($req['status'], ['approved', 'borrowed', 'overdue'], true)) {
                $pdo->rollBack();
                return false;
            }
            $stmt = $pdo->prepare(
                "UPDATE borrow_requests
                 SET status=?, condition_on_return=?, returned_at=NOW()
                 WHERE id=? AND status IN ('approved', 'borrowed', 'overdue')"
            );
            $stmt->execute([$status, $condition, $id]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            $reservedIds = self::parseReservedIds($req['reserved_equipment_ids'] ?? null, (int)$req['equipment_id']);
            Equipment::updateUnitsStatus(
                $reservedIds,
                $eqStatus,
                (int)$req['equipment_id'],
                max(1, (int)($req['quantity'] ?? 1)),
                ['pending', 'borrowed', 'overdue']
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
        self::syncOverdueAndLost();
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
        self::syncOverdueAndLost();
        $stmt = db()->prepare(
            "SELECT MONTH(COALESCE(borrowed_at, approved_at, requested_at)) AS m,
                    SUM(CASE WHEN status IN ('approved','borrowed','overdue','returned','broken','lost') THEN 1 ELSE 0 END) AS borrowed,
                    SUM(CASE WHEN status IN ('returned','broken','lost') THEN 1 ELSE 0 END) AS returned
             FROM borrow_requests
             WHERE YEAR(COALESCE(borrowed_at, approved_at, requested_at)) = ?
             GROUP BY MONTH(COALESCE(borrowed_at, approved_at, requested_at))"
        );
        $stmt->execute([$year]);
        $data = array_fill(1, 12, ['borrowed' => 0, 'returned' => 0]);
        foreach ($stmt->fetchAll() as $r) {
            $m = (int)$r['m'];
            if ($m >= 1 && $m <= 12) {
                $data[$m] = [
                    'borrowed' => (int) $r['borrowed'],
                    'returned' => (int) $r['returned'],
                ];
            }
        }
        return $data;
    }

    public static function countPending(): int
    {
        self::syncOverdueAndLost();
        return (int) db()->query("SELECT COUNT(*) FROM borrow_requests WHERE status='pending'")
                        ->fetchColumn();
    }

    public static function countBorrowing(): int
    {
        self::syncOverdueAndLost();
        return (int) db()->query("SELECT COUNT(*) FROM borrow_requests WHERE status IN ('borrowed','overdue')")
                        ->fetchColumn();
    }

    public static function countOverdue(): int
    {
        self::syncOverdueAndLost();
        return (int) db()->query("SELECT COUNT(*) FROM borrow_requests WHERE status='overdue'")
                        ->fetchColumn();
    }
}

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/AuditLog.php';
$user = User::requirePermission('operations.manage');

$do = $_GET['do'] ?? $_POST['do'] ?? '';

switch ($do) {

    case 'approve':
        $id = (int)($_GET['id'] ?? 0);
        BorrowRequest::approve($id);
        header('Location: admin_dashboard.php'); exit;

    case 'reject':
        $id = (int)($_GET['id'] ?? 0);
        BorrowRequest::reject($id);
        header('Location: admin_dashboard.php'); exit;

    case 'mark_return':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $condition = $_POST['condition'] ?? $_GET['condition'] ?? 'good';
        $updated = BorrowRequest::markReturn($id, is_string($condition) ? $condition : '');
        if (!empty($_POST['id'])) json_out(['ok' => $updated], $updated ? 200 : 409);
        header('Location: admin_dashboard.php'); exit;

    case 'add_equipment':
        $name = trim($_POST['name'] ?? '');
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($name === '' || $quantity === false) {
            header('Location: manage_equipment.php?flash=' . urlencode(t('Name and a quantity of at least 1 are required')));
            exit;
        }
        $imageName = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed, true)) {
                $imageName = uniqid('eq_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'],
                    __DIR__ . '/../assets/upload/' . $imageName);
            }
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $equipmentCount = 0;
            for ($i = 0; $i < $quantity; $i++) {
                Equipment::create($name, $imageName);
                $equipmentCount++;
            }
            AuditLog::record('equipment.created', 'equipment_batch', null, [
                'name' => $name,
                'quantity' => $equipmentCount,
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $message = $quantity === 1 ? t('Equipment added') : $quantity . ' ' . t('equipment items added');
        header('Location: manage_equipment.php?flash=' . urlencode($message)); exit;

    case 'delete_equipment':
        $id = (int)($_GET['id'] ?? 0);
        $equipment = Equipment::find($id);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            Equipment::delete($id);
            if ($equipment) {
                AuditLog::record('equipment.deleted', 'equipment', (string)$id, [
                    'name' => $equipment['name'],
                    'serial_number' => $equipment['serial_number'],
                    'status' => $equipment['status'],
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        header('Location: manage_equipment.php?flash=' . urlencode(t('Equipment deleted'))); exit;

    case 'notif_read':
        Notification::markAllRead((int)$user['id']);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php')); exit;

    case 'export_history':
        $rows = BorrowRequest::history();
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="history_' . date('Ymd_His') . '.xls"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "<table border='1'><tr>";
        foreach (['ID', 'Date & Time', 'Student', 'Student ID', 'Course', 'Equipment', 'Serial No.', 'Quantity', 'Classroom', 'Teacher', 'Purpose', 'Status'] as $heading) {
            echo '<th>' . e(t($heading)) . '</th>';
        }
        echo '</tr>';
        foreach ($rows as $r) {
            $dt = $r['returned_at'] ?: $r['approved_at'] ?: $r['requested_at'];
            echo '<tr>';
            echo '<td>' . (int)$r['id'] . '</td>';
            echo '<td>' . e(date('M d, Y h:i A', strtotime($dt))) . '</td>';
            echo '<td>' . e($r['student_name']) . '</td>';
            echo '<td>' . e($r['student_uid']) . '</td>';
            echo '<td>' . e($r['course']) . '</td>';
            echo '<td>' . e($r['equipment_name']) . '</td>';
            echo '<td>' . e($r['serial_number']) . '</td>';
            echo '<td>' . (int)($r['quantity'] ?? 1) . '</td>';
            echo '<td>' . e($r['classroom']) . '</td>';
            echo '<td>' . e($r['teacher']) . '</td>';
            echo '<td>' . e($r['purpose']) . '</td>';
            echo '<td>' . e(t(ucfirst($r['status']))) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;

    default:
        header('Location: admin_dashboard.php');
}

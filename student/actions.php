<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');

$do = $_GET['do'] ?? $_POST['do'] ?? '';

switch ($do) {
    case 'cart_request':
        $purpose = trim($_POST['purpose'] ?? '');
        $classroom = trim($_POST['classroom'] ?? '');
        $teacher = trim($_POST['teacher'] ?? '');
        $durationMinutes = (int)($_POST['duration_minutes'] ?? 60);
        $equipmentIds = $_POST['equipment_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        if (!in_array($durationMinutes, BorrowRequest::VALID_DURATIONS, true)) {
            header('Location: borrow_request.php?flash=' . urlencode(t('Please select a valid borrow duration (30 mins up to 4 hours).'))); exit;
        }
        if ($purpose === '' || $classroom === '' || $teacher === '' ||
            !is_array($equipmentIds) || !is_array($quantities) ||
            count($equipmentIds) === 0 || count($equipmentIds) !== count($quantities)) {
            header('Location: borrow_request.php?flash=' . urlencode(t('Add items and complete all request details'))); exit;
        }
        if (preg_match('/[0-9]/', $purpose) || preg_match('/[0-9]/', $teacher)) {
            header('Location: borrow_request.php?flash=' . urlencode('Numbers are not allowed in purpose or teacher name')); exit;
        }

        $requestedItems = [];
        foreach (array_values($equipmentIds) as $index => $equipmentId) {
            $equipmentId = (int)$equipmentId;
            $quantity = (int)($quantities[$index] ?? 0);
            if ($equipmentId < 1 || $quantity < 1) {
                header('Location: borrow_request.php?flash=' . urlencode(t('Borrow request contains an invalid item or quantity'))); exit;
            }

            $equipment = Equipment::find($equipmentId);
            if (!$equipment || $equipment['status'] !== 'available') {
                header('Location: borrow_request.php?flash=' . urlencode(t('An item in your request is no longer available'))); exit;
            }

            $name = $equipment['name'];
            if (!isset($requestedItems[$name])) {
                $requestedItems[$name] = ['equipment_id' => $equipmentId, 'quantity' => 0];
            }
            $requestedItems[$name]['quantity'] += $quantity;
        }

        $availableCounts = Equipment::availableCountsByName();
        foreach ($requestedItems as $name => $item) {
            if ($item['quantity'] > ($availableCounts[$name] ?? 0)) {
                header('Location: borrow_request.php?flash=' . urlencode(t_format('Requested quantity for {name} exceeds available stock', ['name' => $name]))); exit;
            }
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            foreach ($requestedItems as $item) {
                BorrowRequest::create(
                    (int)$user['id'],
                    $item['equipment_id'],
                    $item['quantity'],
                    $purpose,
                    $classroom,
                    $teacher,
                    $durationMinutes
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            header('Location: borrow_request.php?flash=' . urlencode(t('Could not reserve items: insufficient available stock'))); exit;
        }
        header('Location: borrow_request.php?submitted=1'); exit;

    case 'request':
        $equipId    = (int)($_POST['equipment_id'] ?? 0);
        $purpose    = trim($_POST['purpose']   ?? '');
        $classroom  = trim($_POST['classroom'] ?? '');
        $teacher    = trim($_POST['teacher']   ?? '');
        $quantity   = (int)($_POST['quantity'] ?? 0);
        $durationMinutes = (int)($_POST['duration_minutes'] ?? 60);
        if (!in_array($durationMinutes, BorrowRequest::VALID_DURATIONS, true)) {
            $durationMinutes = 60;
        }
        if (!$equipId || !$purpose || !$classroom || !$teacher || $quantity < 1) {
            header('Location: student_dashboard.php?flash=' . urlencode(t('All fields are required'))); exit;
        }
        if (preg_match('/[0-9]/', $purpose) || preg_match('/[0-9]/', $teacher)) {
            header('Location: student_dashboard.php?flash=' . urlencode('Numbers are not allowed in purpose or teacher name')); exit;
        }
        $equipment = Equipment::find($equipId);
        if (!$equipment || $equipment['status'] !== 'available') {
            header('Location: student_dashboard.php?flash=' . urlencode(t('Selected item is no longer available'))); exit;
        }
        $availableCount = Equipment::availableCountsByName()[$equipment['name']] ?? 0;
        if ($quantity > $availableCount) {
            header('Location: student_dashboard.php?flash=' . urlencode(t('Requested quantity exceeds available stock'))); exit;
        }
        BorrowRequest::create((int)$user['id'], $equipId, $quantity, $purpose, $classroom, $teacher, $durationMinutes);
        header('Location: history.php'); exit;

    case 'scan_qr':
        $qrToken = trim($_POST['qr_token'] ?? $_GET['qr_token'] ?? '');
        $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        $result = BorrowRequest::activateByQrToken($qrToken, (int)$user['id']);
        if ($isAjax) {
            json_out($result, $result['ok'] ? 200 : 422);
        }
        if ($result['ok']) {
            header('Location: history.php');
        } else {
            header('Location: student_dashboard.php?flash=' . urlencode($result['message']));
        }
        exit;

    case 'notif_read':
        Notification::markAllRead((int)$user['id']);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'student_dashboard.php')); exit;

    case 'cancel':
        $reqId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $ok    = BorrowRequest::cancel($reqId, (int)$user['id']);
        $back  = $_SERVER['HTTP_REFERER'] ?? 'history.php';
        if ($ok) {
            Notification::push((int)$user['id'], 'system', "Borrow request #{$reqId} was cancelled and the item was returned to Available.", $reqId);
            header('Location: ' . $back);
            exit;
        }
        $msg   = t('Cannot cancel — request may already be processed.');
        $sep   = str_contains($back, '?') ? '&' : '?';
        header('Location: ' . $back . $sep . 'flash=' . urlencode($msg)); exit;

    default:
        header('Location: student_dashboard.php');
}

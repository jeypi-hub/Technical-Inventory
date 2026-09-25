<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');

$do = $_GET['do'] ?? $_POST['do'] ?? '';

switch ($do) {
    case 'request':
        $equipId    = (int)($_POST['equipment_id'] ?? 0);
        $purpose    = trim($_POST['purpose']   ?? '');
        $classroom  = trim($_POST['classroom'] ?? '');
        $teacher    = trim($_POST['teacher']   ?? '');
        if (!$equipId || !$purpose || !$classroom || !$teacher) {
            header('Location: student_dashboard.php?flash=All fields are required'); exit;
        }
        BorrowRequest::create((int)$user['id'], $equipId, $purpose, $classroom, $teacher);
        header('Location: student_dashboard.php?flash=Request submitted — pending approval'); exit;

    case 'notif_read':
        Notification::markAllRead((int)$user['id']);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'student_dashboard.php')); exit;

    case 'cancel':
        $reqId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $ok    = BorrowRequest::cancel($reqId, (int)$user['id']);
        $back  = $_SERVER['HTTP_REFERER'] ?? 'history.php';
        $msg   = $ok ? 'Request cancelled.' : 'Cannot cancel — request may already be processed.';
        $sep   = str_contains($back, '?') ? '&' : '?';
        header('Location: ' . $back . $sep . 'flash=' . urlencode($msg)); exit;

    default:
        header('Location: student_dashboard.php');
}

<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();

// Works for both student and admin
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redirect = currentUserRole() === 'admin'
        ? BASE_URL . '/admin/notifications.php'
        : BASE_URL . '/student/notifications.php';
    header('Location: ' . $redirect);
    exit;
}

requireCsrf();

$userId = currentUserId();
$markAll = !empty($_POST['mark_all']);

try {
    $db = getDB();

    if ($markAll) {
        // Mark all notifications as read for this user
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        setFlash('success', 'All notifications marked as read.');
    } else {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            setFlash('error', 'Invalid notification.');
        } else {
            // Mark single notification as read — with ownership check
            $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->execute([$notificationId, $userId]);

            if ($stmt->rowCount() === 0) {
                setFlash('error', 'Notification not found.');
            } else {
                setFlash('success', 'Notification marked as read.');
            }
        }
    }
} catch (Exception $e) {
    error_log('Mark notification error: ' . $e->getMessage());
    setFlash('error', 'Failed to update notification.');
}

// Redirect based on role
$redirect = currentUserRole() === 'admin'
    ? BASE_URL . '/admin/notifications.php'
    : BASE_URL . '/student/notifications.php';
header('Location: ' . $redirect);
exit;

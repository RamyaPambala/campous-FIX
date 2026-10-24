<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

requireCsrf();

$complaintId = (int) ($_POST['complaint_id'] ?? 0);
if ($complaintId <= 0) {
    setFlash('error', 'Invalid complaint.');
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

// Verify complaint exists and belongs to this student
$stmt = getDB()->prepare('SELECT * FROM complaints WHERE id = ? AND user_id = ?');
$stmt->execute([$complaintId, currentUserId()]);
$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$complaint) {
    setFlash('error', 'Complaint not found.');
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

// Verify complaint is in a valid status for feedback
if (!in_array($complaint['status'], ['Resolved', 'Closed'], true)) {
    setFlash('error', 'Feedback can only be submitted for resolved or closed complaints.');
    header('Location: ' . BASE_URL . '/student/complaint_details.php?id=' . $complaintId);
    exit;
}

// Check for existing feedback
$stmt = getDB()->prepare('SELECT COUNT(*) FROM feedback WHERE complaint_id = ?');
$stmt->execute([$complaintId]);
if ($stmt->fetchColumn() > 0) {
    setFlash('error', 'You have already submitted feedback for this complaint.');
    header('Location: ' . BASE_URL . '/student/complaint_details.php?id=' . $complaintId);
    exit;
}

// Validate feedback
$errors = validateFeedback($_POST);

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = [
        'rating' => $_POST['rating'] ?? '',
        'comment' => $_POST['comment'] ?? '',
    ];
    header('Location: ' . BASE_URL . '/student/complaint_details.php?id=' . $complaintId);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare(
        'INSERT INTO feedback (complaint_id, student_id, rating, comment)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $complaintId,
        currentUserId(),
        (int) $_POST['rating'],
        sanitize($_POST['comment'] ?? '')
    ]);

    // Notify the student confirming feedback
    createNotification(
        currentUserId(),
        $complaintId,
        'Feedback Received',
        'Thank you! Your feedback for complaint "' . $complaint['title'] . '" (' . $complaint['reference_number'] . ') has been recorded.'
    );

    setFlash('success', 'Thank you for your feedback!');
    header('Location: ' . BASE_URL . '/student/complaint_details.php?id=' . $complaintId);
    exit;

} catch (Exception $e) {
    error_log('Feedback submission error: ' . $e->getMessage());
    setFlash('error', 'Failed to submit feedback. Please try again.');
    header('Location: ' . BASE_URL . '/student/complaint_details.php?id=' . $complaintId);
    exit;
}

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
    header('Location: ' . BASE_URL . '/student/create_complaint.php');
    exit;
}

requireCsrf();

// Validate complaint data
$errors = validateComplaint($_POST);

// Validate upload if present
$uploadResult = ['path' => null, 'error' => null];
if (!empty($_FILES['attachment']['name'])) {
    $uploadResult = validateUpload($_FILES['attachment']);
    if ($uploadResult['error']) {
        $errors['attachment'] = $uploadResult['error'];
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'category' => $_POST['category'] ?? '',
        'building' => $_POST['building'] ?? '',
        'location_details' => $_POST['location_details'] ?? '',
        'priority' => $_POST['priority'] ?? 'Medium',
    ];
    header('Location: ' . BASE_URL . '/student/create_complaint.php');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    $refNumber = generateReferenceNumber();
    $userId = currentUserId();

    $stmt = $db->prepare(
        'INSERT INTO complaints (reference_number, user_id, title, description, category, building, location_details, priority, status, attachment_path)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Submitted", ?)'
    );
    $stmt->execute([
        $refNumber,
        $userId,
        sanitize($_POST['title']),
        sanitize($_POST['description']),
        sanitize($_POST['category']),
        sanitize($_POST['building']),
        sanitize($_POST['location_details'] ?? ''),
        sanitize($_POST['priority']),
        $uploadResult['path']
    ]);

    $complaintId = (int) $db->lastInsertId();

    // Record initial status in history
    $stmt = $db->prepare(
        'INSERT INTO complaint_status_history (complaint_id, changed_by, old_status, new_status, comment, visibility)
         VALUES (?, ?, NULL, "Submitted", "Complaint submitted", "public")'
    );
    $stmt->execute([$complaintId, $userId]);

    // Create notification for the student
    createNotification(
        $userId,
        $complaintId,
        'Complaint Submitted',
        'Your complaint "' . sanitize($_POST['title']) . '" has been submitted successfully. Reference: ' . $refNumber
    );

    $db->commit();

    setFlash('success', 'Complaint submitted successfully! Reference number: ' . $refNumber);
    header('Location: ' . BASE_URL . '/student/complaint_details.php?id=' . $complaintId);
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Complaint creation error: ' . $e->getMessage());
    setFlash('error', 'Failed to submit complaint. Please try again.');
    header('Location: ' . BASE_URL . '/student/create_complaint.php');
    exit;
}

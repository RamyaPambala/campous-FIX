<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

requireCsrf();

$complaintId = (int) ($_POST['complaint_id'] ?? 0);
if ($complaintId <= 0) {
    setFlash('error', 'Invalid complaint.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

// Fetch current complaint
$stmt = getDB()->prepare('SELECT * FROM complaints WHERE id = ?');
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$complaint) {
    setFlash('error', 'Complaint not found.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$currentStatus = $complaint['status'];
$sameStatus = !empty($_POST['same_status']);
$comment = sanitize($_POST['comment'] ?? '');
$visibility = ($_POST['visibility'] ?? 'public') === 'internal' ? 'internal' : 'public';
$assignedTo = sanitize($_POST['assigned_to'] ?? '');
$resolutionComment = sanitize($_POST['resolution_comment'] ?? '');

if ($sameStatus) {
    // Add timeline note only
    if (empty(trim($comment))) {
        setFlash('error', 'A comment is required to post a timeline note.');
        header('Location: ' . BASE_URL . '/admin/complaint_details.php?id=' . $complaintId);
        exit;
    }
} else {
    // Status change
    $newStatus = sanitize($_POST['new_status'] ?? '');
    if (empty($newStatus)) {
        setFlash('error', 'Please select a status to update.');
        header('Location: ' . BASE_URL . '/admin/complaint_details.php?id=' . $complaintId);
        exit;
    }

    // Validate the transition
    $allowedTransitions = STATUS_TRANSITIONS[$currentStatus] ?? [];
    if (!in_array($newStatus, $allowedTransitions, true)) {
        setFlash('error', 'Invalid status transition from "' . $currentStatus . '" to "' . $newStatus . '".');
        header('Location: ' . BASE_URL . '/admin/complaint_details.php?id=' . $complaintId);
        exit;
    }

    // For direct status changes, default comment if none was provided
    if (empty(trim($comment))) {
        $comment = 'Status updated to ' . $newStatus . '.';
    }

    // For Resolved status, default resolution details if not specified
    if ($newStatus === 'Resolved' && empty(trim($resolutionComment))) {
        $resolutionComment = $comment;
    }
}

try {
    $db = getDB();
    $db->beginTransaction();

    if ($sameStatus) {
        // Just add a note without changing status
        $stmt = $db->prepare(
            'INSERT INTO complaint_status_history (complaint_id, changed_by, old_status, new_status, comment, visibility)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $complaintId,
            currentUserId(),
            $currentStatus,
            $currentStatus,
            $comment,
            $visibility
        ]);

        // Update the complaint's updated_at timestamp
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare('UPDATE complaints SET updated_at = ? WHERE id = ?');
        $stmt->execute([$now, $complaintId]);

        // Notify student if public note
        if ($visibility === 'public') {
            createNotification(
                $complaint['user_id'],
                $complaintId,
                'Note Added',
                'A note has been added to your complaint "' . $complaint['title'] . '" (' . $complaint['reference_number'] . ').'
            );
        }

        $db->commit();
        setFlash('success', 'Note added successfully.');

    } else {
        // Status change
        // Update complaint status
        $now = date('Y-m-d H:i:s');
        $updateSql = 'UPDATE complaints SET status = ?, updated_at = ?';
        $updateParams = [$newStatus, $now];

        if ($newStatus === 'Resolved') {
            $updateSql .= ', resolved_at = ?, resolution_comment = ?';
            $updateParams[] = $now;
            $updateParams[] = $resolutionComment ?: $comment;
        }

        if (!empty($assignedTo)) {
            $updateSql .= ', assigned_to = ?';
            $updateParams[] = $assignedTo;
        }

        $updateSql .= ' WHERE id = ?';
        $updateParams[] = $complaintId;

        $stmt = $db->prepare($updateSql);
        $stmt->execute($updateParams);

        // Record status history
        $stmt = $db->prepare(
            'INSERT INTO complaint_status_history (complaint_id, changed_by, old_status, new_status, comment, visibility)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $complaintId,
            currentUserId(),
            $currentStatus,
            $newStatus,
            $comment,
            $visibility
        ]);

        // Notify student if visibility is public
        if ($visibility === 'public') {
            $notifTitle = 'Status Updated to ' . $newStatus;
            $notifMessage = 'Your complaint "' . $complaint['title'] . '" (' . $complaint['reference_number'] . ') has been updated to "' . $newStatus . '".';
            if (!empty($comment)) {
                $notifMessage .= ' Comment: ' . $comment;
            }
            createNotification(
                $complaint['user_id'],
                $complaintId,
                $notifTitle,
                $notifMessage
            );
        }

        $db->commit();
        setFlash('success', 'Complaint status updated to "' . $newStatus . '" successfully.');
    }

    header('Location: ' . BASE_URL . '/admin/complaint_details.php?id=' . $complaintId);
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Status update error: ' . $e->getMessage());
    setFlash('error', 'Failed to update complaint. Please try again.');
    header('Location: ' . BASE_URL . '/admin/complaint_details.php?id=' . $complaintId);
    exit;
}

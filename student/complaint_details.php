<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'Complaint Details';
$pageType = 'student';
$currentPage = 'complaints';

// Validate complaint ID
$complaintId = $_GET['id'] ?? null;
if (!$complaintId || !is_numeric($complaintId)) {
    setFlash('error', 'Invalid complaint ID.');
    header('Location: ' . BASE_URL . '/student/complaints.php');
    exit;
}
$complaintId = (int)$complaintId;

$db = getDB();

// Fetch complaint (only if owned by current student)
$stmt = $db->prepare('SELECT c.*, u.full_name as student_name FROM complaints c JOIN users u ON c.user_id = u.id WHERE c.id = ? AND c.user_id = ?');
$stmt->execute([$complaintId, currentUserId()]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $pageTitle = 'Complaint Not Found';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header">
        <h1 class="page-title">Complaint Not Found</h1>
        <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-secondary">← Back to Complaints</a>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div style="font-size: 48px;">🔍</div>
                <h3>Not Found</h3>
                <p>The complaint you're looking for doesn't exist or you don't have permission to view it.</p>
                <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-primary">View My Complaints</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = 'Complaint #' . $complaint['reference_number'];

// Fetch status history
$stmt = $db->prepare('SELECT h.*, u.full_name as changed_by_name FROM complaint_status_history h JOIN users u ON h.changed_by = u.id WHERE h.complaint_id = ? ORDER BY h.created_at ASC');
$stmt->execute([$complaintId]);
$statusHistory = $stmt->fetchAll();

// Fetch existing feedback
$stmt = $db->prepare('SELECT * FROM feedback WHERE complaint_id = ? AND student_id = ?');
$stmt->execute([$complaintId, currentUserId()]);
$existingFeedback = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <h1 class="page-title">Complaint #<?= e($complaint['reference_number']) ?></h1>
        <span class="badge <?= statusBadgeClass($complaint['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $complaint['status']))) ?></span>
    </div>
    <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-secondary">← Back to Complaints</a>
</div>

<?php renderFlashMessages(); ?>

<!-- Complaint Details Card -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2>Complaint Details</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <!-- Left: Main Content -->
            <div>
                <h3 style="margin-bottom: 12px; color: var(--text);"><?= e($complaint['title']) ?></h3>
                <div style="color: var(--text-light); line-height: 1.7; white-space: pre-wrap; margin-bottom: 16px;"><?= e($complaint['description']) ?></div>

                <?php 
                $attachmentFile = $complaint['attachment_path'] ?? $complaint['attachment'] ?? null;
                if (!empty($attachmentFile)): 
                ?>
                    <div style="margin-top: 16px;">
                        <label class="form-label">&#128206; Attachment</label>
                        <div style="margin-top: 8px;">
                            <a href="<?= e(BASE_URL) ?>/uploads/<?= e($attachmentFile) ?>" target="_blank" style="display: inline-block;">
                                <img src="<?= e(BASE_URL) ?>/uploads/<?= e($attachmentFile) ?>" alt="Complaint attachment"
                                     style="max-width: 400px; max-height: 300px; border-radius: var(--radius-md); border: 1px solid var(--border); object-fit: contain;">
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Metadata -->
            <div style="background: var(--bg); padding: 20px; border-radius: var(--radius-lg);">
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Reference Number</label>
                    <p style="font-weight: 600; margin-top: 4px;"><?= e($complaint['reference_number']) ?></p>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Category</label>
                    <p style="margin-top: 4px;"><?= categoryIcon($complaint['category']) ?> <?= e($complaint['category']) ?></p>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Building</label>
                    <p style="margin-top: 4px;">📍 <?= e($complaint['building']) ?></p>
                </div>
                <?php if (!empty($complaint['location_details'])): ?>
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Location Details</label>
                        <p style="margin-top: 4px;"><?= e($complaint['location_details']) ?></p>
                    </div>
                <?php endif; ?>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Priority</label>
                    <p style="margin-top: 4px;"><span class="badge <?= priorityBadgeClass($complaint['priority']) ?>"><?= e(ucfirst($complaint['priority'])) ?></span></p>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Submitted</label>
                    <p style="margin-top: 4px;"><?= formatDateTime($complaint['created_at']) ?></p>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Last Updated</label>
                    <p style="margin-top: 4px;"><?= formatDateTime($complaint['updated_at']) ?></p>
                </div>
                <?php if (!empty($complaint['resolved_at'])): ?>
                    <div>
                        <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Resolved At</label>
                        <p style="margin-top: 4px; color: var(--success);">✅ <?= formatDateTime($complaint['resolved_at']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resolution Comment -->
<?php if (!empty($complaint['resolution_comment'])): ?>
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2>💬 Resolution Comment</h2>
        </div>
        <div class="card-body">
            <div style="background: var(--bg); padding: 16px; border-radius: var(--radius); border-left: 4px solid var(--success);">
                <p style="line-height: 1.7;"><?= e($complaint['resolution_comment']) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Status Timeline -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2>📋 Status Timeline</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($statusHistory)): ?>
            <div class="timeline">
                <?php foreach ($statusHistory as $i => $entry): ?>
                    <?php
                    $isLast = ($i === count($statusHistory) - 1);
                    $statusClass = $isLast ? 'success' : '';
                    ?>
                    <div class="timeline-item <?= $statusClass ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong>
                                    <?= statusIcon($entry['old_status'] ?? '') ?>
                                    <?= e(ucwords(str_replace('_', ' ', $entry['old_status'] ?? 'Created'))) ?>
                                    →
                                    <?= statusIcon($entry['new_status']) ?>
                                    <?= e(ucwords(str_replace('_', ' ', $entry['new_status']))) ?>
                                </strong>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 4px;">
                                    Changed by <?= e($entry['changed_by_name']) ?>
                                </p>
                                <?php if (!empty($entry['comment']) && (!isset($entry['visibility']) || $entry['visibility'] === 'public')): ?>
                                    <div style="margin-top: 8px; padding: 8px 12px; background: var(--bg); border-radius: var(--radius); font-size: 0.9rem;">
                                        💬 <?= e($entry['comment']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span style="color: var(--text-lighter); font-size: 0.8rem; white-space: nowrap; margin-left: 16px;">
                                <?= formatDateTime($entry['created_at']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 36px;">📋</div>
                <p>No status changes recorded yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Feedback Section -->
<?php if (in_array($complaint['status'], ['resolved', 'closed'])): ?>
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2>⭐ Feedback</h2>
        </div>
        <div class="card-body">
            <?php if ($existingFeedback): ?>
                <!-- Display existing feedback -->
                <div style="text-align: center; padding: 20px 0;">
                    <p style="color: var(--text-light); margin-bottom: 8px;">Your Rating</p>
                    <div style="font-size: 32px; margin-bottom: 12px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= (int)$existingFeedback['rating'] ? '⭐' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                    <?php if (!empty($existingFeedback['comment'])): ?>
                        <div style="background: var(--bg); padding: 16px; border-radius: var(--radius); margin-top: 12px; max-width: 500px; margin-left: auto; margin-right: auto;">
                            <p style="font-style: italic; color: var(--text-light);">"<?= e($existingFeedback['comment']) ?>"</p>
                        </div>
                    <?php endif; ?>
                    <p style="color: var(--text-lighter); font-size: 0.85rem; margin-top: 12px;">
                        Submitted <?= formatDateTime($existingFeedback['created_at']) ?>
                    </p>
                </div>
            <?php elseif ($complaint['status'] === 'resolved'): ?>
                <!-- Feedback form -->
                <form method="POST" action="<?= BASE_URL ?>/actions/submit_feedback.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id'] ?>">

                    <div style="text-align: center; margin-bottom: 20px;">
                        <p style="color: var(--text-light); margin-bottom: 12px;">How would you rate the resolution of this complaint?</p>

                        <div class="star-rating" style="display: inline-flex; flex-direction: row-reverse; gap: 4px; font-size: 32px;">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star-<?= $i ?>" value="<?= $i ?>" required
                                       style="display: none;">
                                <label for="star-<?= $i ?>" style="cursor: pointer; color: var(--border); transition: color 0.2s;"
                                       class="star-label" data-rating="<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="feedback-comment">Comments (Optional)</label>
                        <textarea id="feedback-comment" name="comment" class="form-control" rows="3"
                                  placeholder="Share your thoughts on how the issue was resolved..."></textarea>
                    </div>

                    <div style="text-align: center; margin-top: 16px;">
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                    </div>
                </form>

                <style>
                    .star-rating input:checked ~ label,
                    .star-rating label:hover,
                    .star-rating label:hover ~ label {
                        color: #f59e0b !important;
                    }
                    .star-rating:hover label {
                        color: var(--border) !important;
                    }
                    .star-rating label:hover,
                    .star-rating label:hover ~ label {
                        color: #f59e0b !important;
                    }
                </style>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'My Feedback';
$pageType = 'student';
$currentPage = 'complaints';

$db = getDB();

// Fetch all feedback submitted by this student
$stmt = $db->prepare('SELECT f.*, c.reference_number, c.title as complaint_title, c.category FROM feedback f JOIN complaints c ON f.complaint_id = c.id WHERE f.student_id = ? ORDER BY f.created_at DESC');
$stmt->execute([currentUserId()]);
$feedbackList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">My Feedback</h1>
    <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-secondary">← Back to Complaints</a>
</div>

<?php renderFlashMessages(); ?>

<?php if (!empty($feedbackList)): ?>
    <div style="display: grid; gap: 16px;">
        <?php foreach ($feedbackList as $feedback): ?>
            <div class="card">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span style="color: var(--text-lighter); font-size: 0.85rem;"><?= e($feedback['reference_number']) ?></span>
                                <span style="color: var(--border);">•</span>
                                <span style="font-size: 0.85rem;"><?= categoryIcon($feedback['category']) ?> <?= e($feedback['category']) ?></span>
                            </div>

                            <h3 style="margin-bottom: 8px;">
                                <a href="<?= BASE_URL ?>/student/complaint_details.php?id=<?= (int)$feedback['complaint_id'] ?>" style="color: var(--text); text-decoration: none;">
                                    <?= e($feedback['complaint_title']) ?>
                                </a>
                            </h3>

                            <!-- Star Rating -->
                            <div style="margin-bottom: 8px; font-size: 20px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?= $i <= (int)$feedback['rating'] ? '⭐' : '☆' ?>
                                <?php endfor; ?>
                                <span style="font-size: 0.85rem; color: var(--text-light); margin-left: 8px;">(<?= (int)$feedback['rating'] ?>/5)</span>
                            </div>

                            <?php if (!empty($feedback['comment'])): ?>
                                <div style="background: var(--bg); padding: 12px 16px; border-radius: var(--radius); margin-top: 8px;">
                                    <p style="font-style: italic; color: var(--text-light); line-height: 1.6;">"<?= e($feedback['comment']) ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="text-align: right; flex-shrink: 0;">
                            <span style="color: var(--text-lighter); font-size: 0.8rem;"><?= formatDate($feedback['created_at']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div style="font-size: 48px;">⭐</div>
                <h3>No Feedback Yet</h3>
                <p>You haven't submitted any feedback yet. Once your complaints are resolved, you can rate the resolution quality.</p>
                <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-primary">View My Complaints</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

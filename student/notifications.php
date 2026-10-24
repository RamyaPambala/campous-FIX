<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'Notifications';
$pageType = 'student';
$currentPage = 'notifications';

$db = getDB();

// Count total notifications
$countStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$countStmt->execute([currentUserId()]);
$totalRecords = (int)$countStmt->fetchColumn();

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$pagination = paginate($totalRecords, $perPage, $page);
$offset = ($page - 1) * $perPage;

// Fetch notifications with complaint reference
$stmt = $db->prepare("SELECT n.*, c.reference_number FROM notifications n LEFT JOIN complaints c ON n.complaint_id = c.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([currentUserId()]);
$notifications = $stmt->fetchAll();

// Count unread
$unreadStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$unreadStmt->execute([currentUserId()]);
$unreadCount = (int)$unreadStmt->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <?php if ($unreadCount > 0): ?>
            <p style="color: var(--text-light); margin-top: 4px;"><?= $unreadCount ?> unread notification<?= $unreadCount !== 1 ? 's' : '' ?></p>
        <?php endif; ?>
    </div>
    <?php if ($unreadCount > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>/actions/mark_notification_read.php" style="display: inline;">
            <?= csrfField() ?>
            <input type="hidden" name="mark_all" value="1">
            <button type="submit" class="btn btn-secondary">✅ Mark All as Read</button>
        </form>
    <?php endif; ?>
</div>

<?php renderFlashMessages(); ?>

<div class="card">
    <div class="card-header">
        <span>Showing <?= count($notifications) ?> of <?= $totalRecords ?> notification<?= $totalRecords !== 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($notifications)): ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>"
                         style="padding: 16px; margin-bottom: 8px; border-radius: var(--radius);
                                <?= !$notification['is_read'] ? 'background: #f0f7ff; border: 1px solid #bfdbfe;' : 'background: var(--bg); border: 1px solid var(--border-light);' ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <?php if (!$notification['is_read']): ?>
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary); display: inline-block;"></span>
                                    <?php endif; ?>
                                    <strong style="<?= !$notification['is_read'] ? 'color: var(--primary);' : '' ?>"><?= e($notification['title']) ?></strong>
                                </div>
                                <p style="color: var(--text-light); margin-top: 4px; font-size: 0.9rem; line-height: 1.5;">
                                    <?= e($notification['message']) ?>
                                </p>
                                <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                                    <span style="color: var(--text-lighter); font-size: 0.8rem;">🕐 <?= timeAgo($notification['created_at']) ?></span>
                                    <?php if (!empty($notification['complaint_id'])): ?>
                                        <a href="<?= BASE_URL ?>/student/complaint_details.php?id=<?= (int)$notification['complaint_id'] ?>" class="btn btn-sm btn-secondary" style="font-size: 0.8rem;">
                                            View Complaint <?= $notification['reference_number'] ? '#' . e($notification['reference_number']) : '' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" action="<?= BASE_URL ?>/actions/mark_notification_read.php" style="flex-shrink: 0;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Mark as Read">✓</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
            echo renderPagination($pagination, BASE_URL . '/student/notifications.php');
            ?>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 48px;">🔔</div>
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet. You'll be notified when your complaints are updated.</p>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

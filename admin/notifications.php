<?php
/**
 * CampusFix — Administrator Notifications Center
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireAdmin();

$adminId = currentUserId();
$db = getDB();

// Count total notifications for admin
$cStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$cStmt->execute([$adminId]);
$totalNotifications = (int) $cStmt->fetchColumn();

// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pg = paginate($totalNotifications, $page, 15);

// Fetch paginated notifications
$stmt = $db->prepare(
    'SELECT n.*, c.reference_number
     FROM notifications n
     LEFT JOIN complaints c ON n.complaint_id = c.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $adminId, PDO::PARAM_INT);
$stmt->bindValue(2, $pg['per_page'], PDO::PARAM_INT);
$stmt->bindValue(3, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Count unread
$uStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$uStmt->execute([$adminId]);
$unreadCount = (int) $uStmt->fetchColumn();

$pageTitle = 'Admin Notifications';
$pageType = 'admin';
$currentPage = 'notifications';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">System &amp; Issue Notifications</h1>
        <p class="page-subtitle">You have <?= $unreadCount ?> unread notification<?= $unreadCount === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <form action="<?= e(BASE_URL) ?>/actions/mark_notification_read.php" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="mark_all" value="1">
            <button type="submit" class="btn btn-outline btn-sm">
                &#9989; Mark All as Read
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128276;</div>
                <h3 class="empty-state-title">No Notifications</h3>
                <p class="empty-state-text">You're completely up to date. Relevant administrative alerts and updates will appear here.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $notif): ?>
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background-color: <?= $notif['is_read'] ? '#ffffff' : 'var(--info-light)' ?>; gap: 1rem;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem; flex: 1;">
                            <div style="font-size: 1.35rem; line-height: 1; margin-top: 0.15rem;">
                                <?= $notif['is_read'] ? '&#9898;' : '&#128309;' ?>
                            </div>
                            <div>
                                <div style="font-weight: <?= $notif['is_read'] ? '600' : '700' ?>; font-size: 1rem; color: var(--primary-dark); margin-bottom: 0.25rem;">
                                    <?= e($notif['title']) ?>
                                </div>
                                <div style="font-size: 0.925rem; color: var(--text-main); margin-bottom: 0.5rem; line-height: 1.5;">
                                    <?= e($notif['message']) ?>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.8rem; color: var(--text-muted);">
                                    <span><?= formatDateTime($notif['created_at']) ?> (<?= timeAgo($notif['created_at']) ?>)</span>
                                    <?php if ($notif['complaint_id']): ?>
                                        &middot;
                                        <a href="<?= e(BASE_URL) ?>/admin/complaint_details.php?id=<?= e($notif['complaint_id']) ?>" style="font-weight: 600;">
                                            View Complaint #<?= e($notif['reference_number'] ?: $notif['complaint_id']) ?> &rarr;
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!$notif['is_read']): ?>
                            <form action="<?= e(BASE_URL) ?>/actions/mark_notification_read.php" method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="notification_id" value="<?= e($notif['id']) ?>">
                                <button type="submit" class="btn btn-outline btn-sm" title="Mark as read" style="font-size: 0.75rem;">
                                    Mark Read
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= renderPagination($pg, BASE_URL . '/admin/notifications.php') ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'Dashboard';
$pageType = 'student';
$currentPage = 'dashboard';

$db = getDB();
$user = getCurrentUser();

// Handle profile update from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        requireCsrf();

        $errors = validateProfileUpdate($_POST);
        if (empty($errors)) {
            $newName = sanitize($_POST['full_name']);
            $stmt = $db->prepare('UPDATE users SET full_name = ?, department = ?, year_of_study = ?, section = ? WHERE id = ?');
            $stmt->execute([
                $newName,
                sanitize($_POST['department']),
                (int)$_POST['year_of_study'],
                sanitize($_POST['section']),
                currentUserId()
            ]);
            $_SESSION['user_name'] = $newName;
            setFlash('success', 'Profile details updated successfully.');
        } else {
            setFlash('error', implode(' ', $errors));
        }

        header('Location: ' . BASE_URL . '/student/dashboard.php');
        exit;
    }
}

// Refresh user details
$user = getCurrentUser();
$stats = getStudentComplaintStats(currentUserId());

// Recent complaints
$stmt = $db->prepare('SELECT * FROM complaints WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5');
$stmt->execute([currentUserId()]);
$recentComplaints = $stmt->fetchAll();

// Recent notifications
$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([currentUserId()]);
$recentNotifications = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, <?= e($user['full_name']) ?></h1>
        <p style="color: var(--text-light); margin-top: 4px;"><?= date('l, F j, Y') ?></p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline" id="btn-edit-details" onclick="toggleDashboardProfileEdit()">✏️ Edit Details</button>
        <a href="<?= BASE_URL ?>/student/create_complaint.php" class="btn btn-primary">📝 Report New Issue</a>
    </div>
</div>

<?php renderFlashMessages(); ?>

<!-- Student Academic Profile Card with Edit Workflow -->
<div class="card" id="student-profile-card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">🎓 Student Academic Profile</h3>
        <button type="button" class="btn btn-sm btn-outline" id="btn-toggle-edit" onclick="toggleDashboardProfileEdit()">✏️ Edit Details</button>
    </div>
    <div class="card-body">
        <!-- Display View -->
        <div id="dashboard-profile-view">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Full Name</span>
                    <div style="font-weight: 600; font-size: 1rem;"><?= e($user['full_name']) ?></div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Enrollment Number</span>
                    <div style="font-weight: 600; font-size: 1rem; font-family: monospace;"><?= e($user['enrollment_number'] ?? '—') ?></div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Department</span>
                    <div style="font-weight: 500;"><?= e($user['department'] ?? '—') ?></div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Year &amp; Section</span>
                    <div style="font-weight: 500;">Year <?= e($user['year_of_study'] ?? '—') ?><?= !empty($user['section']) ? ' · Sec ' . e($user['section']) : '' ?></div>
                </div>
            </div>
        </div>

        <!-- Edit Form View (toggleable) -->
        <div id="dashboard-profile-edit" style="display: none; padding-top: 8px;">
            <form method="POST" action="<?= BASE_URL ?>/student/dashboard.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="dash_full_name">Full Name *</label>
                        <input type="text" id="dash_full_name" name="full_name" class="form-control" required
                               value="<?= e($user['full_name']) ?>" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dash_enrollment">Enrollment Number</label>
                        <input type="text" id="dash_enrollment" class="form-control" value="<?= e($user['enrollment_number'] ?? '') ?>" disabled
                               style="background: var(--bg-muted); cursor: not-allowed;" title="Enrollment number cannot be changed">
                        <span class="form-text">Institutional ID (Read-only)</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dash_department">Department *</label>
                        <select id="dash_department" name="department" class="form-select" required>
                            <?php foreach (DEPARTMENTS as $dept): ?>
                                <option value="<?= e($dept) ?>" <?= ($user['department'] ?? '') === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dash_year_of_study">Year of Study *</label>
                        <select id="dash_year_of_study" name="year_of_study" class="form-select" required>
                            <?php for ($y = 1; $y <= 6; $y++): ?>
                                <option value="<?= $y ?>" <?= ((int)($user['year_of_study'] ?? 0)) === $y ? 'selected' : '' ?>>Year <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dash_section">Section (Optional)</label>
                        <input type="text" id="dash_section" name="section" class="form-control"
                               value="<?= e($user['section'] ?? '') ?>" maxlength="10" placeholder="e.g., A, B, C">
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <button type="submit" class="btn btn-primary" id="btn-save-profile">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleDashboardProfileEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDashboardProfileEdit() {
    var view = document.getElementById('dashboard-profile-view');
    var edit = document.getElementById('dashboard-profile-edit');
    var btn = document.getElementById('btn-toggle-edit');
    var headerBtn = document.getElementById('btn-edit-details');
    if (!view || !edit) return;

    if (edit.style.display === 'none' || edit.style.display === '') {
        edit.style.display = 'block';
        view.style.display = 'none';
        if (btn) btn.innerHTML = '✕ Cancel';
        if (headerBtn) headerBtn.innerHTML = '✕ Cancel';
    } else {
        edit.style.display = 'none';
        view.style.display = 'block';
        if (btn) btn.innerHTML = '✏️ Edit Details';
        if (headerBtn) headerBtn.innerHTML = '✏️ Edit Details';
    }
}
</script>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--primary-light); color: #fff;">📊</div>
        <div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div>
        <div class="stat-label">Total Complaints</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning); color: #fff;">⏳</div>
        <div class="stat-number"><?= (int)($stats['submitted'] ?? 0) ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--info); color: #fff;">🔧</div>
        <div class="stat-number"><?= (int)($stats['in_progress'] ?? 0) ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--success); color: #fff;">✅</div>
        <div class="stat-number"><?= (int)($stats['resolved'] ?? 0) ?></div>
        <div class="stat-label">Resolved</div>
    </div>
</div>

<!-- Recent Complaints -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2>Recent Complaints</h2>
        <?php if (!empty($recentComplaints)): ?>
            <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-sm btn-secondary">View All</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($recentComplaints)): ?>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentComplaints as $complaint): ?>
                            <tr>
                                <td><strong><?= e($complaint['reference_number']) ?></strong></td>
                                <td title="<?= e($complaint['title']) ?>"><?= e(mb_strimwidth($complaint['title'], 0, 40, '...')) ?></td>
                                <td><?= categoryIcon($complaint['category']) ?> <?= e($complaint['category']) ?></td>
                                <td><span class="badge <?= priorityBadgeClass($complaint['priority']) ?>"><?= e($complaint['priority']) ?></span></td>
                                <td><span class="badge <?= statusBadgeClass($complaint['status']) ?>"><?= e($complaint['status']) ?></span></td>
                                <td><?= timeAgo($complaint['updated_at']) ?></td>
                                <td><a href="<?= BASE_URL ?>/student/complaint_details.php?id=<?= (int)$complaint['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 48px;">📋</div>
                <h3>No Complaints Yet</h3>
                <p>You haven't submitted any complaints. Click the button above to report your first campus issue.</p>
                <a href="<?= BASE_URL ?>/student/create_complaint.php" class="btn btn-primary">Report an Issue</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Notifications -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2>Recent Notifications</h2>
        <?php if (!empty($recentNotifications)): ?>
            <a href="<?= BASE_URL ?>/student/notifications.php" class="btn btn-sm btn-secondary">View All Notifications</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($recentNotifications)): ?>
            <div class="notification-list">
                <?php foreach ($recentNotifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" style="padding: 12px 0; border-bottom: 1px solid var(--border-light);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong style="<?= $notification['is_read'] ? '' : 'color: var(--primary);' ?>"><?= e($notification['title']) ?></strong>
                                <p style="color: var(--text-light); margin-top: 4px; font-size: 0.9rem;"><?= e(mb_strimwidth($notification['message'], 0, 100, '...')) ?></p>
                            </div>
                            <span style="color: var(--text-lighter); font-size: 0.8rem; white-space: nowrap; margin-left: 12px;"><?= timeAgo($notification['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 16px; text-align: center;">
                <a href="<?= BASE_URL ?>/student/notifications.php">View All Notifications →</a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 48px;">🔔</div>
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet. You'll be notified when your complaints are updated.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

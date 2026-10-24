<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'My Complaints';
$pageType = 'student';
$currentPage = 'complaints';

$db = getDB();

// Build filters
$where = ['c.user_id = ?'];
$params = [currentUserId()];

$search = sanitize($_GET['search'] ?? '');
if ($search) {
    $where[] = '(c.reference_number LIKE ? OR c.title LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$statusFilter = sanitize($_GET['status'] ?? '');
if ($statusFilter && in_array($statusFilter, COMPLAINT_STATUSES)) {
    $where[] = 'c.status = ?';
    $params[] = $statusFilter;
}

$categoryFilter = sanitize($_GET['category'] ?? '');
if ($categoryFilter && in_array($categoryFilter, COMPLAINT_CATEGORIES)) {
    $where[] = 'c.category = ?';
    $params[] = $categoryFilter;
}

$priorityFilter = sanitize($_GET['priority'] ?? '');
if ($priorityFilter && in_array($priorityFilter, COMPLAINT_PRIORITIES)) {
    $where[] = 'c.priority = ?';
    $params[] = $priorityFilter;
}

$whereClause = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM complaints c WHERE $whereClause");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$pagination = paginate($totalRecords, $perPage, $page);
$offset = ($page - 1) * $perPage;

// Fetch complaints
$stmt = $db->prepare("SELECT c.* FROM complaints c WHERE $whereClause ORDER BY c.updated_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$complaints = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">My Complaints</h1>
    <a href="<?= BASE_URL ?>/student/create_complaint.php" class="btn btn-primary">📝 New Complaint</a>
</div>

<?php renderFlashMessages(); ?>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="form-label" for="search">Search</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Reference # or title..." value="<?= e($search) ?>">
            </div>
            <div class="form-group" style="min-width: 150px; margin-bottom: 0;">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (COMPLAINT_STATUSES as $s): ?>
                        <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width: 150px; margin-bottom: 0;">
                <label class="form-label" for="category">Category</label>
                <select id="category" name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach (COMPLAINT_CATEGORIES as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width: 130px; margin-bottom: 0;">
                <label class="form-label" for="priority">Priority</label>
                <select id="priority" name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <?php foreach (COMPLAINT_PRIORITIES as $p): ?>
                        <option value="<?= e($p) ?>" <?= $priorityFilter === $p ? 'selected' : '' ?>><?= e(ucfirst($p)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 8px; margin-bottom: 0;">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="card">
    <div class="card-header">
        <span>Showing <?= count($complaints) ?> of <?= $totalRecords ?> complaint<?= $totalRecords !== 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($complaints)): ?>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference #</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><strong><?= e($complaint['reference_number']) ?></strong></td>
                                <td title="<?= e($complaint['title']) ?>"><?= e(mb_strimwidth($complaint['title'], 0, 40, '...')) ?></td>
                                <td><?= categoryIcon($complaint['category']) ?> <?= e($complaint['category']) ?></td>
                                <td><span class="badge <?= priorityBadgeClass($complaint['priority']) ?>"><?= e(ucfirst($complaint['priority'])) ?></span></td>
                                <td><span class="badge <?= statusBadgeClass($complaint['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $complaint['status']))) ?></span></td>
                                <td><?= formatDate($complaint['created_at']) ?></td>
                                <td><?= timeAgo($complaint['updated_at']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/student/complaint_details.php?id=<?= (int)$complaint['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            // Build query string for pagination links
            $queryParams = [];
            if ($search) $queryParams['search'] = $search;
            if ($statusFilter) $queryParams['status'] = $statusFilter;
            if ($categoryFilter) $queryParams['category'] = $categoryFilter;
            if ($priorityFilter) $queryParams['priority'] = $priorityFilter;
            echo renderPagination($pagination, BASE_URL . '/student/complaints.php', $queryParams);
            ?>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 48px;">🔍</div>
                <h3>No Complaints Found</h3>
                <p>No complaints match your current filters. Try adjusting your search criteria or submit a new complaint.</p>
                <a href="<?= BASE_URL ?>/student/create_complaint.php" class="btn btn-primary">Report an Issue</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

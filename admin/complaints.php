<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Manage Complaints - ' . APP_NAME;
$currentPage = 'complaints';
$pageType = 'admin';
$notifCount = getUnreadNotificationCount(currentUserId());

// ─── Filters ───
$search   = sanitize($_GET['search'] ?? '');
$status   = sanitize($_GET['status'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$priority = sanitize($_GET['priority'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo   = sanitize($_GET['date_to'] ?? '');

$sql = 'SELECT c.*, u.full_name as student_name, u.enrollment_number 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id';
$where  = [];
$params = [];

if ($search) {
    $where[]  = '(c.reference_number LIKE ? OR c.title LIKE ? OR u.full_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status && in_array($status, COMPLAINT_STATUSES, true)) {
    $where[]  = 'c.status = ?';
    $params[] = $status;
}
if ($category && in_array($category, COMPLAINT_CATEGORIES, true)) {
    $where[]  = 'c.category = ?';
    $params[] = $category;
}
if ($priority && in_array($priority, COMPLAINT_PRIORITIES, true)) {
    $where[]  = 'c.priority = ?';
    $params[] = $priority;
}
if ($dateFrom) {
    $where[]  = 'DATE(c.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[]  = 'DATE(c.created_at) <= ?';
    $params[] = $dateTo;
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

// Count total
$countSql  = 'SELECT COUNT(*) ' . substr($sql, strpos($sql, 'FROM'));
$countStmt = getDB()->prepare($countSql);
$countStmt->execute($params);
$totalComplaints = (int) $countStmt->fetchColumn();

// Pagination
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$pagination = paginate($totalComplaints, $page, $perPage);

$sql .= ' ORDER BY c.created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $pagination['offset'];

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate display range
$showFrom = $totalComplaints > 0 ? $pagination['offset'] + 1 : 0;
$showTo   = min($pagination['offset'] + $perPage, $totalComplaints);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🏫 <?php echo e(APP_NAME); ?></h2>
                <p class="sidebar-subtitle">Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo e(BASE_URL); ?>/admin/dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
                <a href="<?php echo e(BASE_URL); ?>/admin/complaints.php" class="sidebar-link <?php echo $currentPage === 'complaints' ? 'active' : ''; ?>">
                    📋 Manage Complaints
                </a>
                <a href="<?php echo e(BASE_URL); ?>/admin/notifications.php" class="sidebar-link <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>">
                    🔔 Notifications
                    <?php if ($notifCount > 0): ?>
                        <span class="badge badge-danger"><?php echo (int) $notifCount; ?></span>
                    <?php endif; ?>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <span>👤 <?php echo e(currentUserName()); ?></span>
                    <small>Administrator</small>
                </div>
                <a href="<?php echo e(BASE_URL); ?>/logout.php" class="sidebar-link">🚪 Logout</a>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Mobile Header -->
            <header class="mobile-header">
                <button class="mobile-menu-btn" aria-label="Open sidebar">&#9776;</button>
                <a href="<?php echo e(BASE_URL); ?>/admin/dashboard.php" class="navbar-brand" style="font-size: 1.15rem;">
                    <span class="brand-icon">🏫</span>
                    <span><?php echo e(APP_NAME); ?></span>
                </a>
                <a href="<?php echo e(BASE_URL); ?>/admin/notifications.php" style="position: relative; font-size: 1.3rem;">
                    🔔
                    <?php if ($notifCount > 0): ?>
                        <span class="notif-pill"><?php echo (int) $notifCount; ?></span>
                    <?php endif; ?>
                </a>
            </header>

            <!-- Main Content -->
            <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">📋 Manage Complaints</h1>
            </div>

            <?php renderFlashMessages(); ?>

            <!-- Filter Bar -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div class="grid-3">
                            <div class="form-group">
                                <label class="form-label" for="search">Search</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Reference, title, or student name..." value="<?php echo e($search); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="status">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <?php foreach (COMPLAINT_STATUSES as $s): ?>
                                        <option value="<?php echo e($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="category">Category</label>
                                <select id="category" name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach (COMPLAINT_CATEGORIES as $cat): ?>
                                        <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="priority">Priority</label>
                                <select id="priority" name="priority" class="form-select">
                                    <option value="">All Priorities</option>
                                    <?php foreach (COMPLAINT_PRIORITIES as $p): ?>
                                        <option value="<?php echo e($p); ?>" <?php echo $priority === $p ? 'selected' : ''; ?>><?php echo e($p); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="date_from">From Date</label>
                                <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo e($dateFrom); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="date_to">To Date</label>
                                <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo e($dateTo); ?>">
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">🔍 Filter</button>
                            <a href="<?php echo e(BASE_URL); ?>/admin/complaints.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="card">
                <div class="card-header">
                    <h3>Complaints</h3>
                    <span class="text-light">Showing <?php echo $showFrom; ?>–<?php echo $showTo; ?> of <?php echo $totalComplaints; ?> complaints</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($complaints)): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Reference #</th>
                                        <th>Student</th>
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
                                    <?php foreach ($complaints as $c): ?>
                                        <tr>
                                            <td><strong><?php echo e($c['reference_number']); ?></strong></td>
                                            <td>
                                                <?php echo e($c['student_name']); ?>
                                                <br><small class="text-light"><?php echo e($c['enrollment_number']); ?></small>
                                            </td>
                                            <td><?php echo e($c['title']); ?></td>
                                            <td><?php echo e(categoryIcon($c['category'])); ?> <?php echo e($c['category']); ?></td>
                                            <td><span class="badge <?php echo priorityBadgeClass($c['priority']); ?>"><?php echo e($c['priority']); ?></span></td>
                                            <td><span class="badge <?php echo statusBadgeClass($c['status']); ?>"><?php echo e(statusIcon($c['status'])); ?> <?php echo e($c['status']); ?></span></td>
                                            <td><?php echo e(timeAgo($c['created_at'])); ?></td>
                                            <td><?php echo e(timeAgo($c['updated_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo e(BASE_URL); ?>/admin/complaint_details.php?id=<?php echo (int) $c['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div style="margin-top: 1.5rem;">
                                <?php
                                // Build query string for pagination links
                                $queryParams = $_GET;
                                unset($queryParams['page']);
                                $queryString = http_build_query($queryParams);
                                echo renderPagination($pagination, BASE_URL . '/admin/complaints.php', $queryParams);
                                ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>📋</p>
                            <h3>No complaints found</h3>
                            <p>Try adjusting your filters or search terms.</p>
                            <?php if ($search || $status || $category || $priority || $dateFrom || $dateTo): ?>
                                <a href="<?php echo e(BASE_URL); ?>/admin/complaints.php" class="btn btn-secondary" style="margin-top: 1rem;">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div><!-- /.main-wrapper -->
</div><!-- /.dashboard-layout -->

<script src="<?php echo e(BASE_URL); ?>/assets/js/app.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Admin Dashboard - ' . APP_NAME;
$currentPage = 'dashboard';
$pageType = 'admin';

// Get complaint statistics
$stats = getComplaintStats();

// Get category distribution
$categoryData = getCategoryDistribution();

// Get monthly trend
$monthlyData = getMonthlyTrend();

// Recent complaints
$stmt = getDB()->prepare(
    'SELECT c.*, u.full_name, u.enrollment_number 
     FROM complaints c 
     JOIN users u ON c.user_id = u.id 
     ORDER BY c.created_at DESC 
     LIMIT 10'
);
$stmt->execute();
$recentComplaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// High priority unresolved
$stmt = getDB()->prepare(
    'SELECT c.*, u.full_name 
     FROM complaints c 
     JOIN users u ON c.user_id = u.id 
     WHERE c.priority = ? AND c.status NOT IN (?, ?) 
     ORDER BY c.created_at ASC 
     LIMIT 5'
);
$stmt->execute(['High', 'Resolved', 'Closed']);
$highPriority = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notification count
$notifCount = getUnreadNotificationCount(currentUserId());
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
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="text-light"><?php echo date('l, F j, Y'); ?></p>
                </div>

                <?php renderFlashMessages(); ?>

                <!-- Stat Cards -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(30, 58, 95, 0.1); color: var(--primary);">📊</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo (int) ($stats['total'] ?? 0); ?></div>
                            <div class="stat-label">Total Complaints</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">📩</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo (int) ($stats['submitted'] ?? 0); ?></div>
                            <div class="stat-label">New Submissions</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(217, 119, 6, 0.12); color: #d97706;">⚙️</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo (int) ($stats['in_progress'] ?? 0); ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">✅</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo (int) ($stats['resolved'] ?? 0); ?></div>
                            <div class="stat-label">Resolved</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(100, 116, 139, 0.12); color: #475569;">🔒</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo (int) ($stats['closed'] ?? 0); ?></div>
                            <div class="stat-label">Closed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">🔴</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo (int) ($stats['high_priority_open'] ?? 0); ?></div>
                            <div class="stat-label">High Priority Open</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="charts-grid">
                    <!-- Category Distribution -->
                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h3 class="card-title">📂 Complaints by Category</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trend -->
                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h3 class="card-title">📈 Monthly Trend (Last 6 Months)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Complaints -->
                <div class="card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title">🕐 Recent Complaints</h3>
                        <a href="<?php echo e(BASE_URL); ?>/admin/complaints.php" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentComplaints)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Student Name</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentComplaints as $c): ?>
                                            <tr>
                                                <td><strong><?php echo e($c['reference_number']); ?></strong></td>
                                                <td><?php echo e($c['full_name']); ?></td>
                                                <td><?php echo e($c['title']); ?></td>
                                                <td><?php echo e(categoryIcon($c['category'])); ?> <?php echo e($c['category']); ?></td>
                                                <td><span class="badge <?php echo priorityBadgeClass($c['priority']); ?>"><?php echo e($c['priority']); ?></span></td>
                                                <td><span class="badge <?php echo statusBadgeClass($c['status']); ?>"><?php echo e(statusIcon($c['status'])); ?> <?php echo e($c['status']); ?></span></td>
                                                <td><?php echo e(timeAgo($c['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo e(BASE_URL); ?>/admin/complaint_details.php?id=<?php echo (int) $c['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>📋</p>
                                <h3>No complaints yet</h3>
                                <p>Complaints will appear here once students submit them.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- High Priority Unresolved -->
                <div class="card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title">🔴 High Priority Unresolved</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($highPriority)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Student</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($highPriority as $hp): ?>
                                            <tr style="background: rgba(220,38,38,0.04);">
                                                <td><strong><?php echo e($hp['reference_number']); ?></strong></td>
                                                <td><?php echo e($hp['full_name']); ?></td>
                                                <td><?php echo e($hp['title']); ?></td>
                                                <td><span class="badge <?php echo statusBadgeClass($hp['status']); ?>"><?php echo e(statusIcon($hp['status'])); ?> <?php echo e($hp['status']); ?></span></td>
                                                <td><?php echo e(timeAgo($hp['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo e(BASE_URL); ?>/admin/complaint_details.php?id=<?php echo (int) $hp['id']; ?>" class="btn btn-sm btn-danger">Review</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>✅</p>
                                <h3>No high-priority unresolved complaints</h3>
                                <p>All high-priority complaints have been addressed.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div><!-- /.main-wrapper -->
    </div><!-- /.dashboard-layout -->

    <script src="<?php echo e(BASE_URL); ?>/assets/js/app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var categoryData = <?php echo json_encode($categoryData); ?>;
        var monthlyData = <?php echo json_encode($monthlyData); ?>;
        if (typeof initDashboardCharts === 'function') {
            initDashboardCharts(categoryData, monthlyData);
        }
    });
    </script>
</body>
</html>

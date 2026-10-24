<?php
/**
 * CampusFix — Global Header Include
 * Renders HTML head, navigation, sidebars, and flash alerts.
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle   = $pageTitle ?? APP_NAME;
$pageType    = $pageType ?? 'public';
$currentPage = $currentPage ?? '';

$unreadCount = 0;
$currentUser = null;

if (isLoggedIn()) {
    $uid = currentUserId();
    if ($uid) {
        try {
            $unreadCount = getUnreadNotificationCount($uid);
            $currentUser = getCurrentUser();
        } catch (Exception $e) {
            $unreadCount = 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body class="page-type-<?= e($pageType) ?>">

<?php if ($pageType === 'public'): ?>
    <!-- PUBLIC NAVIGATION -->
    <header class="navbar">
        <div class="container navbar-container">
            <a href="<?= e(BASE_URL) ?>/" class="navbar-brand">
                <span class="brand-icon">&#127979;</span>
                <span><?= e(APP_NAME) ?></span>
            </a>

            <button class="navbar-toggle" aria-label="Toggle navigation" aria-expanded="false">
                &#9776;
            </button>

            <ul class="navbar-links">
                <li><a href="<?= e(BASE_URL) ?>/#features" class="navbar-link">Features</a></li>
                <li><a href="<?= e(BASE_URL) ?>/#how-it-works" class="navbar-link">How It Works</a></li>
                <li><a href="<?= e(BASE_URL) ?>/#categories" class="navbar-link">Categories</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?= e(BASE_URL) ?>/admin/dashboard.php" class="btn btn-primary btn-sm">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(BASE_URL) ?>/student/dashboard.php" class="btn btn-primary btn-sm">Student Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="<?= e(BASE_URL) ?>/logout.php" class="btn btn-outline btn-sm">Log Out</a></li>
                <?php else: ?>
                    <li><a href="<?= e(BASE_URL) ?>/login.php" class="btn btn-outline btn-sm">Log In</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <main>

<?php elseif ($pageType === 'auth'): ?>
    <!-- AUTH PAGES LAYOUT (LOGIN / REGISTER) -->
    <main>

<?php elseif ($pageType === 'student' || $pageType === 'admin'): ?>
    <!-- DASHBOARD LAYOUT (STUDENT / ADMIN) -->
    <div class="dashboard-layout">
        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay"></div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="<?= e(BASE_URL) ?>/" class="navbar-brand">
                    <span class="brand-icon">&#127979;</span>
                    <span><?= e(APP_NAME) ?></span>
                </a>
                <button class="sidebar-close" aria-label="Close sidebar">&times;</button>
            </div>

            <nav class="sidebar-nav">
                <?php if ($pageType === 'student'): ?>
                    <div class="sidebar-section">Student Portal</div>
                    <a href="<?= e(BASE_URL) ?>/student/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128202;</span> Dashboard
                        </span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/student/create_complaint.php" class="sidebar-link <?= $currentPage === 'create_complaint' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#9997;</span> Report Issue
                        </span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/student/complaints.php" class="sidebar-link <?= $currentPage === 'complaints' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128221;</span> My Complaints
                        </span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/student/notifications.php" class="sidebar-link <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128276;</span> Notifications
                        </span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-pill"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/student/feedback.php" class="sidebar-link <?= $currentPage === 'feedback' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#11088;</span> My Feedback
                        </span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/student/profile.php" class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128100;</span> My Profile
                        </span>
                    </a>

                <?php elseif ($pageType === 'admin'): ?>
                    <div class="sidebar-section">Admin Portal</div>
                    <a href="<?= e(BASE_URL) ?>/admin/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128202;</span> Overview
                        </span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/admin/complaints.php" class="sidebar-link <?= $currentPage === 'complaints' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128196;</span> Manage Complaints
                        </span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/admin/notifications.php" class="sidebar-link <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                        <span class="sidebar-link-content">
                            <span>&#128276;</span> Notifications
                        </span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-pill"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <div class="sidebar-section">Account</div>
                <a href="<?= e(BASE_URL) ?>/logout.php" class="sidebar-link">
                    <span class="sidebar-link-content">
                        <span>&#128682;</span> Log Out
                    </span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?= e($currentUser['full_name'] ?? 'User') ?></div>
                        <div class="sidebar-user-role"><?= e($currentUser['role'] ?? 'Member') ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Mobile Sticky Header -->
            <header class="mobile-header">
                <button class="mobile-menu-btn" aria-label="Open sidebar">&#9776;</button>
                <a href="<?= e(BASE_URL) ?>/" class="navbar-brand" style="font-size: 1.15rem;">
                    <span class="brand-icon">&#127979;</span>
                    <span><?= e(APP_NAME) ?></span>
                </a>
                <a href="<?= e(BASE_URL) ?>/<?= $pageType ?>/notifications.php" style="position: relative; font-size: 1.3rem;">
                    &#128276;
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-pill" style="position: absolute; top: -6px; right: -8px;"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </header>

            <main class="main-content">
                <?= renderFlashMessages() ?>
<?php endif; ?>

<?php
/**
 * CampusFix — Application Configuration
 *
 * Copy this file or set values via environment variables.
 * NEVER commit real production credentials.
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

// ── Database ─────────────────────────────────────────────
define('DB_HOST',   getenv('CF_DB_HOST')   ?: 'localhost');
define('DB_NAME',   getenv('CF_DB_NAME')   ?: 'campusfix');
define('DB_USER',   getenv('CF_DB_USER')   ?: 'root');
define('DB_PASS',   getenv('CF_DB_PASS')   ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── Application ──────────────────────────────────────────
define('APP_NAME',     'CampusFix');
define('APP_TAGLINE',  'Smart Campus Facility Monitoring & Issue Resolution');
define('APP_VERSION',  '1.0.0');

// Base URL — adjust if project lives in a subdirectory
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Auto-detect base path from this file's location
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Walk up if we're inside includes/, student/, admin/, actions/
$basePath = $scriptDir;
$subDirs = ['includes', 'student', 'admin', 'actions'];
foreach ($subDirs as $sub) {
    if (preg_match('#/' . $sub . '(/|$)#', $basePath)) {
        $basePath = preg_replace('#/' . $sub . '(/.*)?$#', '', $basePath);
        break;
    }
}
define('BASE_URL', rtrim($protocol . '://' . $host . $basePath, '/'));

// ── File Uploads ─────────────────────────────────────────
define('UPLOAD_DIR',        __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE',   2 * 1024 * 1024); // 2 MB
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_ALLOWED_MIME', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp'
]);

// ── Sessions ─────────────────────────────────────────────
define('SESSION_LIFETIME', 3600); // 1 hour

// ── Admin Setup ──────────────────────────────────────────
// Set to false AFTER creating the initial admin account
define('ADMIN_SETUP_ENABLED', true);

// ── Security ─────────────────────────────────────────────
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// ── Categories & Priorities (single source of truth) ─────
define('COMPLAINT_CATEGORIES', [
    'Laboratory and Computers',
    'Internet and Wi-Fi',
    'Water Supply',
    'Washroom Maintenance',
    'Canteen Cleanliness',
    'Electrical Issues',
    'Classroom Facilities',
    'Drinking Water',
    'Other'
]);

define('COMPLAINT_PRIORITIES', ['Low', 'Medium', 'High']);

define('COMPLAINT_STATUSES', [
    'Submitted', 'Acknowledged', 'In Progress',
    'Resolved', 'Closed', 'Reopened'
]);

// Valid status transitions: current => [allowed next statuses]
define('STATUS_TRANSITIONS', [
    'Submitted'    => ['Acknowledged', 'In Progress', 'Closed'],
    'Acknowledged' => ['In Progress', 'Closed'],
    'In Progress'  => ['Resolved', 'Closed'],
    'Resolved'     => ['Closed', 'Reopened'],
    'Closed'       => ['Reopened'],
    'Reopened'     => ['Acknowledged', 'In Progress', 'Closed'],
]);

// ── Departments ──────────────────────────────────────────
define('DEPARTMENTS', [
    'Computer Science and Engineering',
    'Electronics and Communication Engineering',
    'Electrical and Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Information Technology',
    'Chemical Engineering',
    'Biotechnology',
    'Other'
]);

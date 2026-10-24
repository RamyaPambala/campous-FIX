<?php
/**
 * CampusFix — PDO Database Connection
 * Supports MySQL / MariaDB as the primary production engine, with automatic
 * local fallback if the MySQL service has not been started yet.
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/config.php';

/**
 * Override PDO instance for testing environments.
 */
function setTestDB(?PDO $testPdo): void {
    getDB($testPdo);
}

/**
 * Initialize fallback SQLite schema if MySQL is offline.
 */
function initFallbackSchema(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            enrollment_number TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            department TEXT NOT NULL,
            year_of_study INTEGER DEFAULT 1,
            section TEXT,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'student',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reference_number TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            category TEXT NOT NULL,
            building TEXT NOT NULL,
            location_details TEXT,
            priority TEXT DEFAULT 'Medium',
            status TEXT DEFAULT 'Submitted',
            attachment_path TEXT,
            resolution_comment TEXT,
            assigned_to TEXT,
            resolved_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS complaint_status_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            complaint_id INTEGER NOT NULL,
            changed_by INTEGER NOT NULL,
            old_status TEXT,
            new_status TEXT NOT NULL,
            comment TEXT,
            visibility TEXT DEFAULT 'public',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            complaint_id INTEGER UNIQUE NOT NULL,
            student_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            complaint_id INTEGER,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            email TEXT NOT NULL,
            attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed default administrator if users table is empty
    $count = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($count === 0) {
        $adminPass = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (full_name, enrollment_number, email, department, year_of_study, section, password_hash, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'Campus Facilities Administrator',
            'ADMIN-001',
            'admin@campus.edu',
            'Administration',
            0,
            '',
            $adminPass,
            'admin'
        ]);
    }
}

/**
 * Get a singleton PDO instance.
 */
function getDB(?PDO $override = null): PDO {
    static $pdo = null;
    if ($override !== null) {
        $pdo = $override;
        return $pdo;
    }
    if ($pdo === null) {
        // 1. Primary Engine: MySQL / MariaDB via PDO (XAMPP Stack)
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // 2. Seamless local fallback if MySQL service is not yet started
            $sqlitePath = __DIR__ . '/../campusfix_local.sqlite';
            try {
                $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                initFallbackSchema($pdo);
                return $pdo;
            } catch (Exception $e2) {
                error_log('CampusFix DB Error: ' . $e->getMessage());
                throw $e;
            }
        }
    }
    return $pdo;
}

<?php
/**
 * CampusFix — Authentication & Session Helpers
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Start session with secure defaults.
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);

    session_start();

    // Expire idle sessions
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Log in a user: set session variables, regenerate ID.
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['last_activity'] = time();
}

/**
 * Check if a user is authenticated.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the current user's ID.
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's role.
 */
function currentUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get the current user's name.
 */
function currentUserName(): string {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Get the current user's email.
 */
function currentUserEmail(): string {
    return $_SESSION['user_email'] ?? '';
}

/**
 * Check if current user is admin.
 */
function isAdmin(): bool {
    return currentUserRole() === 'admin';
}

/**
 * Check if current user is student.
 */
function isStudent(): bool {
    return currentUserRole() === 'student';
}

/**
 * Require authentication — redirect to login if not logged in.
 */
function requireLogin(): void {
    startSecureSession();
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Require admin role — redirect or forbid.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'You do not have permission to access this page.';
        header('Location: ' . BASE_URL . '/student/dashboard.php');
        exit;
    }
}

/**
 * Require student role.
 */
function requireStudent(): void {
    requireLogin();
    if (!isStudent()) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
}

/**
 * Redirect user to their appropriate dashboard.
 */
function redirectToDashboard(): void {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/student/dashboard.php');
    }
    exit;
}

/**
 * Log out: destroy session.
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Get current user record from DB.
 */
function getCurrentUser(): ?array {
    $id = currentUserId();
    if (!$id) return null;
    $stmt = getDB()->prepare('SELECT id, full_name, enrollment_number, email, department, year_of_study, section, role, created_at, updated_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Record a login attempt for rate limiting.
 */
function recordLoginAttempt(string $email): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = getDB()->prepare('INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)');
    $stmt->execute([$ip, $email]);
}

/**
 * Check if login is rate-limited.
 */
function isLoginRateLimited(string $email): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $threshold = date('Y-m-d H:i:s', time() - (LOGIN_LOCKOUT_MINUTES * 60));
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND email = ?
         AND attempted_at > ?'
    );
    $stmt->execute([$ip, $email, $threshold]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Clean up old login attempts.
 */
function cleanLoginAttempts(): void {
    $threshold = date('Y-m-d H:i:s', time() - (LOGIN_LOCKOUT_MINUTES * 120));
    $stmt = getDB()->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
    $stmt->execute([$threshold]);
}

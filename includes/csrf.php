<?php
/**
 * CampusFix — CSRF Protection
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

/**
 * Generate or retrieve CSRF token for the current session.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the CSRF token from a POST request.
 * Call on every state-changing POST handler.
 */
function validateCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        return false;
    }
    return true;
}

/**
 * Validate CSRF and abort with error if invalid.
 */
function requireCsrf(): void {
    if (!validateCsrf()) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'Invalid security token. Please try again.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
        exit;
    }
}

<?php
/**
 * CampusFix — Input Validation Helpers
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

/**
 * Sanitize a string: trim + strip null bytes.
 */
function sanitize(string $value): string {
    return trim(str_replace("\0", '', $value));
}

/**
 * Validate registration data; returns array of errors (empty = valid).
 */
function validateRegistration(array $data): array {
    $errors = [];

    // Full name
    $name = sanitize($data['full_name'] ?? '');
    if (empty($name)) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors['full_name'] = 'Full name must be between 2 and 120 characters.';
    }

    // Enrollment number
    $enrollment = sanitize($data['enrollment_number'] ?? '');
    if (empty($enrollment)) {
        $errors['enrollment_number'] = 'Enrollment number is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\-\/]+$/', $enrollment)) {
        $errors['enrollment_number'] = 'Enrollment number can only contain letters, numbers, hyphens, and slashes.';
    } elseif (mb_strlen($enrollment) > 30) {
        $errors['enrollment_number'] = 'Enrollment number must not exceed 30 characters.';
    }

    // Email
    $email = sanitize($data['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 180) {
        $errors['email'] = 'Email must not exceed 180 characters.';
    }

    // Department
    $dept = sanitize($data['department'] ?? '');
    if (empty($dept)) {
        $errors['department'] = 'Department is required.';
    } elseif (!in_array($dept, DEPARTMENTS, true)) {
        $errors['department'] = 'Please select a valid department.';
    }

    // Year of study
    $year = (int) ($data['year_of_study'] ?? 0);
    if ($year < 1 || $year > 6) {
        $errors['year_of_study'] = 'Year of study must be between 1 and 6.';
    }

    // Section (optional)
    $section = sanitize($data['section'] ?? '');
    if (!empty($section) && mb_strlen($section) > 10) {
        $errors['section'] = 'Section must not exceed 10 characters.';
    }

    // Password
    $password = $data['password'] ?? '';
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (mb_strlen($password) > 72) {
        $errors['password'] = 'Password must not exceed 72 characters.';
    }

    // Confirm password
    $confirm = $data['confirm_password'] ?? '';
    if ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    return $errors;
}

/**
 * Validate complaint data; returns array of errors.
 */
function validateComplaint(array $data): array {
    $errors = [];

    $title = sanitize($data['title'] ?? '');
    if (empty($title)) {
        $errors['title'] = 'Complaint title is required.';
    } elseif (mb_strlen($title) < 5 || mb_strlen($title) > 200) {
        $errors['title'] = 'Title must be between 5 and 200 characters.';
    }

    $desc = sanitize($data['description'] ?? '');
    if (empty($desc)) {
        $errors['description'] = 'Description is required.';
    } elseif (mb_strlen($desc) < 10) {
        $errors['description'] = 'Please provide at least 10 characters of detail.';
    } elseif (mb_strlen($desc) > 5000) {
        $errors['description'] = 'Description must not exceed 5000 characters.';
    }

    $category = sanitize($data['category'] ?? '');
    if (empty($category) || !in_array($category, COMPLAINT_CATEGORIES, true)) {
        $errors['category'] = 'Please select a valid category.';
    }

    $building = sanitize($data['building'] ?? '');
    if (empty($building)) {
        $errors['building'] = 'Building / campus location is required.';
    } elseif (mb_strlen($building) > 100) {
        $errors['building'] = 'Building name must not exceed 100 characters.';
    }

    $location = sanitize($data['location_details'] ?? '');
    if (!empty($location) && mb_strlen($location) > 200) {
        $errors['location_details'] = 'Location details must not exceed 200 characters.';
    }

    $priority = sanitize($data['priority'] ?? '');
    if (empty($priority) || !in_array($priority, COMPLAINT_PRIORITIES, true)) {
        $errors['priority'] = 'Please select a valid priority.';
    }

    return $errors;
}

/**
 * Validate profile update data.
 */
function validateProfileUpdate(array $data): array {
    $errors = [];

    $name = sanitize($data['full_name'] ?? '');
    if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors['full_name'] = 'Full name must be between 2 and 120 characters.';
    }

    $dept = sanitize($data['department'] ?? '');
    if (empty($dept) || !in_array($dept, DEPARTMENTS, true)) {
        $errors['department'] = 'Please select a valid department.';
    }

    $year = (int) ($data['year_of_study'] ?? 0);
    if ($year < 1 || $year > 6) {
        $errors['year_of_study'] = 'Year of study must be between 1 and 6.';
    }

    $section = sanitize($data['section'] ?? '');
    if (!empty($section) && mb_strlen($section) > 10) {
        $errors['section'] = 'Section must not exceed 10 characters.';
    }

    return $errors;
}

/**
 * Validate feedback.
 */
function validateFeedback(array $data): array {
    $errors = [];

    $rating = (int) ($data['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Rating must be between 1 and 5.';
    }

    $comment = sanitize($data['comment'] ?? '');
    if (!empty($comment) && mb_strlen($comment) > 2000) {
        $errors['comment'] = 'Feedback comment must not exceed 2000 characters.';
    }

    return $errors;
}

/**
 * Validate and process uploaded file.
 * Returns ['path' => ..., 'error' => null] or ['path' => null, 'error' => '...']
 */
function validateUpload(array $file): array {
    // No file uploaded
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'File upload failed. Please try again.'];
    }

    // Size check
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['path' => null, 'error' => 'File size must not exceed 2 MB.'];
    }

    // Extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT, true)) {
        return ['path' => null, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
    }

    // MIME check
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, UPLOAD_ALLOWED_MIME, true)) {
        return ['path' => null, 'error' => 'Invalid file type detected.'];
    }

    // Generate safe filename
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = rtrim(UPLOAD_DIR, '/\\');

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $destPath = $destDir . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['path' => null, 'error' => 'Failed to save uploaded file.'];
    }

    return ['path' => $newName, 'error' => null];
}

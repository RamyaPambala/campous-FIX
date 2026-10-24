<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();

if (isLoggedIn()) {
    redirectToDashboard();
}

$errors = [];
$formData = [
    'full_name' => '',
    'enrollment_number' => '',
    'email' => '',
    'department' => '',
    'year_of_study' => '',
    'section' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    // Collect form data
    $formData = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'enrollment_number' => sanitize($_POST['enrollment_number'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'department' => sanitize($_POST['department'] ?? ''),
        'year_of_study' => sanitize($_POST['year_of_study'] ?? ''),
        'section' => sanitize($_POST['section'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
    ];
    $password = $formData['password'];
    $confirmPassword = $formData['confirm_password'];

    // Validate using the validation helper
    $errors = validateRegistration($formData);

    // Check for duplicate email
    if (empty($errors)) {
        try {
            $stmt = getDB()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$formData['email']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'An account with this email address already exists.';
            }
        } catch (PDOException $e) {
            error_log('Registration check error: ' . $e->getMessage());
            $errors['general'] = 'A system error occurred. Please try again later.';
        }
    }

    // Check for duplicate enrollment number
    if (empty($errors)) {
        try {
            $stmt = getDB()->prepare('SELECT COUNT(*) FROM users WHERE enrollment_number = ?');
            $stmt->execute([$formData['enrollment_number']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['enrollment_number'] = 'An account with this enrollment number already exists.';
            }
        } catch (PDOException $e) {
            error_log('Registration check error: ' . $e->getMessage());
            $errors['general'] = 'A system error occurred. Please try again later.';
        }
    }

    // Create user if no errors
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = getDB()->prepare('
                INSERT INTO users (full_name, enrollment_number, email, password_hash, department, year_of_study, section, role)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $formData['full_name'],
                $formData['enrollment_number'],
                $formData['email'],
                $passwordHash,
                $formData['department'],
                $formData['year_of_study'],
                $formData['section'],
                'student',
            ]);
            
            setFlash('success', 'Your account has been created successfully! Please sign in.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'A system error occurred. Please try again later.';
        }
    }
}

$pageTitle = 'Register';
$pageType = 'auth';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-form-wrapper">
            <a href="<?php echo e(BASE_URL); ?>/" class="auth-logo">🏫 CampusFix</a>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-subtitle">Join CampusFix and start reporting campus issues</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error"><?php echo e($errors['general']); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(BASE_URL); ?>/register.php" class="auth-form">
                <?= csrfField(); ?>

                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter your full name"
                        value="<?php echo e($formData['full_name']); ?>"
                        required
                    >
                    <?php if (isset($errors['full_name'])): ?>
                        <span class="form-error"><?php echo e($errors['full_name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="enrollment_number" class="form-label">Enrollment Number</label>
                    <input
                        type="text"
                        id="enrollment_number"
                        name="enrollment_number"
                        class="form-control <?php echo isset($errors['enrollment_number']) ? 'is-invalid' : ''; ?>"
                        placeholder="e.g., 22071A0501"
                        value="<?php echo e($formData['enrollment_number']); ?>"
                        required
                    >
                    <?php if (isset($errors['enrollment_number'])): ?>
                        <span class="form-error"><?php echo e($errors['enrollment_number']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        placeholder="you@university.edu"
                        value="<?php echo e($formData['email']); ?>"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="form-error"><?php echo e($errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="department" class="form-label">Department</label>
                        <select
                            id="department"
                            name="department"
                            class="form-select <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>"
                            required
                        >
                            <option value="">Select Department</option>
                            <?php foreach (DEPARTMENTS as $dept): ?>
                                <option value="<?php echo e($dept); ?>" <?php echo $formData['department'] === $dept ? 'selected' : ''; ?>>
                                    <?php echo e($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['department'])): ?>
                            <span class="form-error"><?php echo e($errors['department']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="year_of_study" class="form-label">Year of Study</label>
                        <select
                            id="year_of_study"
                            name="year_of_study"
                            class="form-select <?php echo isset($errors['year_of_study']) ? 'is-invalid' : ''; ?>"
                            required
                        >
                            <option value="">Select Year</option>
                            <?php for ($y = 1; $y <= 6; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $formData['year_of_study'] == $y ? 'selected' : ''; ?>>
                                    Year <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($errors['year_of_study'])): ?>
                            <span class="form-error"><?php echo e($errors['year_of_study']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="section" class="form-label">Section <span class="form-text">(Optional)</span></label>
                    <input
                        type="text"
                        id="section"
                        name="section"
                        class="form-control"
                        placeholder="e.g., A, B, C"
                        value="<?php echo e($formData['section']); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                        placeholder="Minimum 8 characters"
                        required
                        minlength="8"
                    >
                    <?php if (isset($errors['password'])): ?>
                        <span class="form-error"><?php echo e($errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                        placeholder="Re-enter your password"
                        required
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="form-error"><?php echo e($errors['confirm_password']); ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <p class="auth-footer-text">
                Already have an account? <a href="<?php echo e(BASE_URL); ?>/login.php">Sign in</a>
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-right-content">
            <div class="auth-right-icon">📋</div>
            <h2 class="auth-right-title">Join Your Campus Community</h2>
            <p class="auth-right-subtitle">Help identify and resolve infrastructure issues. Your feedback makes the campus better for everyone.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

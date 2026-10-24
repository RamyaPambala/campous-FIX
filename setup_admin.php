<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();

// Check if admin setup is enabled
if (!defined('ADMIN_SETUP_ENABLED') || !ADMIN_SETUP_ENABLED) {
    $pageTitle = 'Setup Disabled';
    $pageType = 'auth';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-form-wrapper">
                <a href="<?php echo e(BASE_URL); ?>/" class="auth-logo">🏫 CampusFix</a>
                <div class="alert alert-error">
                    <strong>Admin Setup is Disabled</strong><br>
                    The admin setup page is currently disabled. To enable it, set <code>ADMIN_SETUP_ENABLED</code> to <code>true</code> in <code>includes/config.php</code>.
                </div>
                <a href="<?php echo e(BASE_URL); ?>/" class="btn btn-secondary btn-block">← Back to Home</a>
            </div>
        </div>
        <div class="auth-right">
            <div class="auth-right-content">
                <div class="auth-right-icon">🔒</div>
                <h2 class="auth-right-title">Setup Disabled</h2>
                <p class="auth-right-subtitle">Admin setup has been disabled for security. Modify config.php to re-enable.</p>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Check if admin already exists
$adminExists = false;
try {
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
} catch (PDOException $e) {
    error_log('Admin check error: ' . $e->getMessage());
}

if ($adminExists) {
    $pageTitle = 'Setup Complete';
    $pageType = 'auth';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-form-wrapper">
                <a href="<?php echo e(BASE_URL); ?>/" class="auth-logo">🏫 CampusFix</a>
                <h1 class="auth-title">Setup Already Complete</h1>
                <div class="alert alert-warning">
                    <strong>An admin account already exists.</strong><br>
                    The initial admin setup has already been completed. For security, please set <code>ADMIN_SETUP_ENABLED</code> to <code>false</code> in <code>includes/config.php</code>.
                </div>
                <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-primary btn-block">Go to Login</a>
            </div>
        </div>
        <div class="auth-right">
            <div class="auth-right-content">
                <div class="auth-right-icon">✅</div>
                <h2 class="auth-right-title">Setup Complete</h2>
                <p class="auth-right-subtitle">Admin account is configured. Disable this page in config.php for security.</p>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handle form submission
$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $formData = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate full name
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($formData['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    }

    // Validate email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    // Validate confirm password
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Check for duplicate email
    if (empty($errors)) {
        try {
            $stmt = getDB()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$formData['email']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'An account with this email already exists.';
            }
        } catch (PDOException $e) {
            error_log('Admin setup check error: ' . $e->getMessage());
            $errors['general'] = 'A system error occurred. Please try again.';
        }
    }

    // Create admin user
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = getDB()->prepare('
                INSERT INTO users (full_name, enrollment_number, email, password_hash, department, year_of_study, section, role)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $formData['full_name'],
                'ADMIN-' . strtoupper(bin2hex(random_bytes(3))),
                $formData['email'],
                $passwordHash,
                'Administration',
                0,
                '',
                'admin',
            ]);
            
            $setupSuccess = true;
        } catch (PDOException $e) {
            error_log('Admin creation error: ' . $e->getMessage());
            $errors['general'] = 'Failed to create admin account. Please try again.';
        }
    }
}

$pageTitle = 'Admin Setup';
$pageType = 'auth';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-form-wrapper">
            <a href="<?php echo e(BASE_URL); ?>/" class="auth-logo">🏫 CampusFix</a>

            <?php if (isset($setupSuccess) && $setupSuccess): ?>
                <h1 class="auth-title">Admin Account Created!</h1>
                <div class="alert alert-success">
                    <strong>Setup Complete!</strong><br>
                    The admin account has been created successfully. You can now sign in with your credentials.
                </div>
                <div class="alert alert-warning">
                    <strong>⚠️ Important Security Step</strong><br>
                    Please open <code>includes/config.php</code> and set <code>ADMIN_SETUP_ENABLED</code> to <code>false</code> to prevent unauthorized access to this setup page.
                </div>
                <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-primary btn-block">Go to Login</a>
            <?php else: ?>
                <h1 class="auth-title">Initial Admin Setup</h1>
                <p class="auth-subtitle">Create the first administrator account for CampusFix</p>

                <div class="alert alert-info">
                    <strong>🔐 Security Note</strong><br>
                    This page is only accessible because <code>ADMIN_SETUP_ENABLED</code> is set to <code>true</code> in your configuration. Disable it after creating the admin account.
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error"><?php echo e($errors['general']); ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(BASE_URL); ?>/setup_admin.php" class="auth-form">
                    <?= csrfField(); ?>

                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                            placeholder="Administrator name"
                            value="<?php echo e($formData['full_name']); ?>"
                            required
                        >
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="form-error"><?php echo e($errors['full_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                            placeholder="admin@university.edu"
                            value="<?php echo e($formData['email']); ?>"
                            required
                        >
                        <?php if (isset($errors['email'])): ?>
                            <span class="form-error"><?php echo e($errors['email']); ?></span>
                        <?php endif; ?>
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

                    <button type="submit" class="btn btn-primary btn-block">🔧 Create Admin Account</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-right-content">
            <div class="auth-right-icon">⚙️</div>
            <h2 class="auth-right-title">Administrator Setup</h2>
            <p class="auth-right-subtitle">Configure the first admin account to start managing your campus facility complaints.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

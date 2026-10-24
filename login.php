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

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Check rate limiting
        try {
            if (isLoginRateLimited($email)) {
                $error = 'Too many login attempts. Please try again in ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
            } else {
                $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    loginUser($user);
                    // Clean old attempts
                    cleanLoginAttempts();
                    redirectToDashboard();
                } else {
                    recordLoginAttempt($email);
                    $error = 'Invalid email or password.';
                }
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'A system error occurred. Please try again later.';
        }
    }
}

$pageTitle = 'Login';
$pageType = 'auth';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-form-wrapper">
            <a href="<?php echo e(BASE_URL); ?>/" class="auth-logo">🏫 CampusFix</a>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to your account to continue</p>

            <?= renderFlashMessages(); ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(BASE_URL); ?>/login.php" class="auth-form">
                <?= csrfField(); ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@university.edu"
                        value="<?php echo e($email); ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <div class="form-group form-check">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <p class="auth-footer-text">
                Don't have an account? <a href="<?php echo e(BASE_URL); ?>/register.php">Register here</a>
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-right-content">
            <div class="auth-right-icon">🏫</div>
            <h2 class="auth-right-title">Smart Campus Facility Management</h2>
            <p class="auth-right-subtitle">Report issues, track progress, and help improve your campus infrastructure.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

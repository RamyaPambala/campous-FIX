<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();

// Redirect logged-in users to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Try to get public stats
$publicStats = [];
try {
    $publicStats = getPublicStats();
} catch (Exception $e) {
    $publicStats = [];
}

$pageTitle = 'Welcome';
$pageType = 'public';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">A Better Campus Starts With Your Feedback.</h1>
            <p class="hero-subtitle">Report campus infrastructure issues, track their resolution, and help make your campus a better place. CampusFix connects students with administration for faster, transparent problem resolution.</p>
            <div class="hero-actions">
                <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-primary btn-lg">📝 Report an Issue</a>
                <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-secondary btn-lg">📊 Track Complaints</a>
            </div>
        </div>

        <!-- Decorative Dashboard Preview -->
        <div class="hero-preview">
            <div class="preview-label">Dashboard Preview</div>
            <div class="preview-dashboard">
                <div class="preview-stats">
                    <div class="preview-stat-card">
                        <span class="preview-stat-icon">📋</span>
                        <span class="preview-stat-number">--</span>
                        <span class="preview-stat-label">Total Complaints</span>
                    </div>
                    <div class="preview-stat-card">
                        <span class="preview-stat-icon">✅</span>
                        <span class="preview-stat-number">--</span>
                        <span class="preview-stat-label">Resolved</span>
                    </div>
                    <div class="preview-stat-card">
                        <span class="preview-stat-icon">⏳</span>
                        <span class="preview-stat-number">--</span>
                        <span class="preview-stat-label">In Progress</span>
                    </div>
                    <div class="preview-stat-card">
                        <span class="preview-stat-icon">⚡</span>
                        <span class="preview-stat-number">--</span>
                        <span class="preview-stat-label">Urgent</span>
                    </div>
                </div>
                <div class="preview-table">
                    <div class="preview-table-header">
                        <span>ID</span>
                        <span>Issue</span>
                        <span>Status</span>
                        <span>Priority</span>
                    </div>
                    <div class="preview-table-row">
                        <span class="preview-id">#CF-001</span>
                        <span>Projector malfunction in Room 201</span>
                        <span><span class="badge badge-warning">In Progress</span></span>
                        <span><span class="badge badge-danger">High</span></span>
                    </div>
                    <div class="preview-table-row">
                        <span class="preview-id">#CF-002</span>
                        <span>Wi-Fi connectivity issue in Library</span>
                        <span><span class="badge badge-info">Acknowledged</span></span>
                        <span><span class="badge badge-warning">Medium</span></span>
                    </div>
                    <div class="preview-table-row">
                        <span class="preview-id">#CF-003</span>
                        <span>Water leakage in Block B corridor</span>
                        <span><span class="badge badge-success">Resolved</span></span>
                        <span><span class="badge badge-danger">High</span></span>
                    </div>
                </div>
            </div>
            <div class="preview-note">✨ Illustration only — sign in to see your real dashboard</div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="landing-section" id="features">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Everything You Need to Keep Your Campus Running Smoothly</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3 class="feature-title">Easy Complaint Submission</h3>
                <p class="feature-description">Submit detailed reports about campus issues in seconds. Attach images, set priority levels, and categorize problems for faster resolution.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Transparent Status Tracking</h3>
                <p class="feature-description">Follow your complaint from submission to resolution. Get real-time updates on every status change with a detailed timeline.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Priority-Based Resolution</h3>
                <p class="feature-description">Urgent issues get addressed first. Our priority system ensures critical campus problems receive immediate attention.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏛️</div>
                <h3 class="feature-title">Administrative Oversight</h3>
                <p class="feature-description">Campus administrators get powerful tools to manage, assign, and resolve complaints efficiently with full audit trails.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3 class="feature-title">Student Feedback</h3>
                <p class="feature-description">Rate the resolution quality and provide feedback. Help improve campus maintenance processes through constructive input.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="landing-section landing-section-alt" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">How CampusFix Works</h2>
            <p class="section-subtitle">Four simple steps to a better campus</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">Report a Problem</h3>
                <p class="step-description">Spot an issue? Submit a detailed complaint with location, category, and priority. Attach a photo for clarity.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">Administration Reviews</h3>
                <p class="step-description">Campus administrators receive your complaint instantly and acknowledge it. High-priority issues are flagged for immediate action.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">Team Takes Action</h3>
                <p class="step-description">The responsible maintenance team is notified and works on resolving the issue. Track progress in real-time.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3 class="step-title">Resolved &amp; Feedback</h3>
                <p class="step-description">Once fixed, you'll be notified. Rate the resolution and help us continuously improve campus facilities.</p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="landing-section" id="categories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Campus Issues We Handle</h2>
        </div>
        <div class="categories-grid">
            <div class="category-card">
                <span class="category-card-icon">💻</span>
                <span class="category-card-name">Laboratory and Computers</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">📶</span>
                <span class="category-card-name">Internet and Wi-Fi</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">🚰</span>
                <span class="category-card-name">Water Supply</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">🚻</span>
                <span class="category-card-name">Washroom Maintenance</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">🍽️</span>
                <span class="category-card-name">Canteen Cleanliness</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">⚡</span>
                <span class="category-card-name">Electrical Issues</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">🏫</span>
                <span class="category-card-name">Classroom Facilities</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">🚰</span>
                <span class="category-card-name">Drinking Water</span>
            </div>
            <div class="category-card">
                <span class="category-card-icon">🔧</span>
                <span class="category-card-name">Other</span>
            </div>
        </div>
    </div>
</section>

<!-- Public Statistics Section -->
<section class="landing-section landing-section-alt" id="stats">
    <div class="container">
        <?php if (!empty($publicStats) && isset($publicStats['total_complaints']) && $publicStats['total_complaints'] > 0): ?>
            <div class="section-header">
                <h2 class="section-title">CampusFix in Numbers</h2>
            </div>
            <div class="public-stats-grid">
                <div class="public-stat-card">
                    <div class="public-stat-number"><?php echo e($publicStats['total_complaints']); ?></div>
                    <div class="public-stat-label">Total Complaints Filed</div>
                </div>
                <div class="public-stat-card">
                    <div class="public-stat-number"><?php echo e($publicStats['resolved_complaints'] ?? 0); ?></div>
                    <div class="public-stat-label">Issues Resolved</div>
                </div>
                <div class="public-stat-card">
                    <div class="public-stat-number"><?php echo e($publicStats['active_students'] ?? 0); ?></div>
                    <div class="public-stat-label">Active Students</div>
                </div>
                <div class="public-stat-card">
                    <div class="public-stat-number"><?php echo e($publicStats['categories_used'] ?? 0); ?></div>
                    <div class="public-stat-label">Categories Covered</div>
                </div>
            </div>
        <?php else: ?>
            <div class="section-header">
                <h2 class="section-title">Be the First to Make a Difference</h2>
                <p class="section-subtitle">No complaints have been filed yet. Sign up today and be the first to help improve your campus by reporting issues that matter.</p>
            </div>
            <div class="cta-center">
                <a href="<?php echo e(BASE_URL); ?>/register.php" class="btn btn-primary btn-lg">🚀 Get Started Now</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

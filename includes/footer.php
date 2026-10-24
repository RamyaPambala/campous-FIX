<?php
/**
 * CampusFix — Global Footer Include
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/config.php';
$pageType = $pageType ?? 'public';
?>

<?php if ($pageType === 'public'): ?>
    </main>
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="navbar-brand" style="color: #ffffff; margin-bottom: 1rem;">
                        <span class="brand-icon">&#127979;</span>
                        <span><?= e(APP_NAME) ?></span>
                    </div>
                    <p><?= e(APP_TAGLINE) ?></p>
                    <p style="margin-top: 0.75rem; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
                        Empowering students and campus administration to maintain state-of-the-art academic and residential facilities.
                    </p>
                </div>

                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?= e(BASE_URL) ?>/#features">Platform Features</a></li>
                        <li><a href="<?= e(BASE_URL) ?>/#how-it-works">How It Works</a></li>
                        <li><a href="<?= e(BASE_URL) ?>/#categories">Campus Categories</a></li>
                        <li><a href="<?= e(BASE_URL) ?>/login.php">Portal Sign In</a></li>
                        <li><a href="<?= e(BASE_URL) ?>/register.php">Student Registration</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Campus Support</h4>
                    <p>Central Facilities & Maintenance Division</p>
                    <p style="margin-top: 0.5rem;">&#128222; Helpdesk: Ext. 4400 / 4401</p>
                    <p>&#9993; facilities@campus.edu</p>
                    <p style="margin-top: 0.75rem; font-size: 0.85rem;">Hours: Mon–Sat, 8:00 AM – 6:00 PM</p>
                </div>
            </div>

            <div class="footer-bottom">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Mini Project Web Development &middot; Built with PHP, MySQL &amp; Vanilla JavaScript
            </div>
        </div>
    </footer>

<?php elseif ($pageType === 'auth'): ?>
    </main>

<?php elseif ($pageType === 'student' || $pageType === 'admin'): ?>
            </main>
        </div><!-- /.main-wrapper -->
    </div><!-- /.dashboard-layout -->
<?php endif; ?>

<script src="<?= e(BASE_URL) ?>/assets/js/app.js"></script>
</body>
</html>

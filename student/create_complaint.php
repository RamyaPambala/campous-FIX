<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'Submit New Complaint';
$pageType = 'student';
$currentPage = 'new_complaint';

// Retrieve validation errors and old form data from session
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Submit New Complaint</h1>
        <p style="color: var(--text-light); margin-top: 4px;">Report a campus facility issue that needs attention.</p>
    </div>
    <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-secondary">← Back to Complaints</a>
</div>

<?php renderFlashMessages(); ?>

<div class="card">
    <div class="card-header">
        <h2>Complaint Details</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/actions/create_complaint.php" enctype="multipart/form-data">
            <?= csrfField() ?>

            <!-- Title -->
            <div class="form-group">
                <label class="form-label" for="title">Complaint Title <span style="color: var(--danger);">*</span></label>
                <input type="text" id="title" name="title" class="form-control <?= isset($errors['title']) ? 'error' : '' ?>"
                       required maxlength="200" placeholder="Brief summary of the issue"
                       value="<?= e($old['title'] ?? '') ?>">
                <?php if (isset($errors['title'])): ?>
                    <span class="form-error"><?= e($errors['title']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="description">Description <span style="color: var(--danger);">*</span></label>
                <textarea id="description" name="description" class="form-control <?= isset($errors['description']) ? 'error' : '' ?>"
                          required maxlength="5000" rows="6" placeholder="Provide detailed information about the issue..."
                          data-char-count="5000"><?= e($old['description'] ?? '') ?></textarea>
                <span class="form-text"><span id="description-count"><?= strlen($old['description'] ?? '') ?></span> / 5000 characters</span>
                <?php if (isset($errors['description'])): ?>
                    <span class="form-error"><?= e($errors['description']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label class="form-label" for="category">Category <span style="color: var(--danger);">*</span></label>
                <select id="category" name="category" class="form-select <?= isset($errors['category']) ? 'error' : '' ?>" required>
                    <option value="">Select a category...</option>
                    <?php foreach (COMPLAINT_CATEGORIES as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= ($old['category'] ?? '') === $cat ? 'selected' : '' ?>>
                            <?= categoryIcon($cat) ?> <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category'])): ?>
                    <span class="form-error"><?= e($errors['category']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Building/Location -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="building">Building / Campus Location <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="building" name="building" class="form-control <?= isset($errors['building']) ? 'error' : '' ?>"
                           required maxlength="100" placeholder="e.g., Main Building, Library Block"
                           value="<?= e($old['building'] ?? '') ?>">
                    <?php if (isset($errors['building'])): ?>
                        <span class="form-error"><?= e($errors['building']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location_details">Room / Lab / Area Details</label>
                    <input type="text" id="location_details" name="location_details" class="form-control <?= isset($errors['location_details']) ? 'error' : '' ?>"
                           maxlength="200" placeholder="e.g., Room 301, Computer Lab 2"
                           value="<?= e($old['location_details'] ?? '') ?>">
                    <?php if (isset($errors['location_details'])): ?>
                        <span class="form-error"><?= e($errors['location_details']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Priority -->
            <div class="form-group">
                <label class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                <div style="display: flex; gap: 16px; margin-top: 4px;">
                    <?php
                    $selectedPriority = $old['priority'] ?? 'medium';
                    foreach (COMPLAINT_PRIORITIES as $p):
                        $checked = ($selectedPriority === $p) ? 'checked' : '';
                        $icon = match($p) {
                            'low' => '🟢',
                            'medium' => '🟡',
                            'high' => '🔴',
                            default => '⚪'
                        };
                    ?>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 2px solid var(--border); border-radius: var(--radius); transition: all 0.2s;">
                            <input type="radio" name="priority" value="<?= e($p) ?>" <?= $checked ?> required
                                   style="accent-color: var(--primary);">
                            <?= $icon ?> <?= e(ucfirst($p)) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['priority'])): ?>
                    <span class="form-error"><?= e($errors['priority']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Attachment -->
            <div class="form-group">
                <label class="form-label" for="attachment">Attachment (Optional)</label>
                <input type="file" id="attachment" name="attachment" class="form-control <?= isset($errors['attachment']) ? 'error' : '' ?>"
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <span class="form-text">Upload an image of the issue. Accepted formats: JPEG, PNG, GIF, WebP. Maximum file size: 2MB.</span>
                <?php if (isset($errors['attachment'])): ?>
                    <span class="form-error"><?= e($errors['attachment']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                <button type="submit" class="btn btn-primary">📤 Submit Complaint</button>
                <a href="<?= BASE_URL ?>/student/complaints.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Character counter for description
document.addEventListener('DOMContentLoaded', function() {
    const desc = document.getElementById('description');
    const counter = document.getElementById('description-count');
    if (desc && counter) {
        desc.addEventListener('input', function() {
            counter.textContent = this.value.length;
            if (this.value.length > 4800) {
                counter.style.color = 'var(--danger)';
            } else if (this.value.length > 4000) {
                counter.style.color = 'var(--warning)';
            } else {
                counter.style.color = 'inherit';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

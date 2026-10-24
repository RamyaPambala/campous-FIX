<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'My Profile';
$pageType = 'student';
$currentPage = 'profile';

$db = getDB();
$user = getCurrentUser();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        requireCsrf();

        $errors = validateProfileUpdate($_POST);
        if (empty($errors)) {
            $newName = sanitize($_POST['full_name']);
            $stmt = $db->prepare('UPDATE users SET full_name = ?, department = ?, year_of_study = ?, section = ? WHERE id = ?');
            $stmt->execute([
                $newName,
                sanitize($_POST['department']),
                (int)$_POST['year_of_study'],
                sanitize($_POST['section']),
                currentUserId()
            ]);
            $_SESSION['user_name'] = $newName;
            setFlash('success', 'Profile updated successfully.');
        } else {
            setFlash('error', implode(' ', $errors));
        }

        header('Location: ' . BASE_URL . '/student/profile.php');
        exit;
    }

    if ($action === 'change_password') {
        requireCsrf();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $passwordErrors = [];

        if (empty($currentPassword)) {
            $passwordErrors[] = 'Current password is required.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $passwordErrors[] = 'Current password is incorrect.';
        }

        if (empty($newPassword)) {
            $passwordErrors[] = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $passwordErrors[] = 'New password must be at least 8 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'New password and confirmation do not match.';
        }

        if (empty($passwordErrors)) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, currentUserId()]);
            setFlash('success', 'Password changed successfully.');
        } else {
            setFlash('error', implode(' ', $passwordErrors));
        }

        header('Location: ' . BASE_URL . '/student/profile.php');
        exit;
    }
}

// Refresh user data after potential update
$user = getCurrentUser();

// Build initials for avatar
$nameParts = explode(' ', trim($user['full_name']));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">My Profile</h1>
</div>

<?php renderFlashMessages(); ?>

<!-- Profile Info Card -->
<div class="card" style="margin-bottom: 24px;" id="profile-view">
    <div class="card-header">
        <h2>Profile Information</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="toggleProfileEdit()" id="btn-edit-details">✏️ Edit Details</button>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 32px; align-items: flex-start;">
            <!-- Avatar -->
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: #fff;
                        display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;
                        flex-shrink: 0;">
                <?= e($initials) ?>
            </div>

            <!-- Info Grid -->
            <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Full Name</label>
                    <p style="font-weight: 600; margin-top: 4px;"><?= e($user['full_name']) ?></p>
                </div>
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Enrollment Number</label>
                    <p style="font-weight: 600; margin-top: 4px;"><?= e($user['enrollment_number'] ?? '—') ?></p>
                </div>
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Email</label>
                    <p style="margin-top: 4px;"><?= e($user['email']) ?></p>
                </div>
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Department</label>
                    <p style="margin-top: 4px;"><?= e($user['department'] ?? '—') ?></p>
                </div>
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Year of Study</label>
                    <p style="margin-top: 4px;"><?= e($user['year_of_study'] ?? '—') ?></p>
                </div>
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Section</label>
                    <p style="margin-top: 4px;"><?= e($user['section'] ?? '—') ?></p>
                </div>
                <div>
                    <label style="font-size: 0.8rem; color: var(--text-lighter); text-transform: uppercase; letter-spacing: 0.5px;">Member Since</label>
                    <p style="margin-top: 4px;"><?= formatDate($user['created_at']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Form (initially hidden) -->
<div class="card" style="margin-bottom: 24px; display: none;" id="profile-edit">
    <div class="card-header">
        <h2>Edit Profile</h2>
        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleProfileEdit()">✕ Cancel</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profile">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required
                           value="<?= e($user['full_name']) ?>" maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email_display">Email</label>
                    <input type="email" id="email_display" class="form-control" value="<?= e($user['email']) ?>" disabled
                           style="background: var(--bg); cursor: not-allowed;">
                    <span class="form-text">Email cannot be changed.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="enrollment_display">Enrollment Number</label>
                    <input type="text" id="enrollment_display" class="form-control" value="<?= e($user['enrollment_number'] ?? '') ?>" disabled
                           style="background: var(--bg); cursor: not-allowed;">
                    <span class="form-text">Enrollment number cannot be changed.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="department">Department</label>
                    <select id="department" name="department" class="form-select">
                        <option value="">Select Department</option>
                        <?php foreach (DEPARTMENTS as $dept): ?>
                            <option value="<?= e($dept) ?>" <?= ($user['department'] ?? '') === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="year_of_study">Year of Study</label>
                    <select id="year_of_study" name="year_of_study" class="form-select">
                        <option value="">Select Year</option>
                        <?php for ($y = 1; $y <= 5; $y++): ?>
                            <option value="<?= $y ?>" <?= ((int)($user['year_of_study'] ?? 0)) === $y ? 'selected' : '' ?>>Year <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="section">Section</label>
                    <input type="text" id="section" name="section" class="form-control"
                           value="<?= e($user['section'] ?? '') ?>" maxlength="10" placeholder="e.g., A, B, C">
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="toggleProfileEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Password Card -->
<div class="card">
    <div class="card-header">
        <h2>🔒 Change Password</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_password">

            <div style="max-width: 500px;">
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required
                           placeholder="Enter your current password">
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required
                           minlength="8" placeholder="Minimum 8 characters">
                    <span class="form-text">Must be at least 8 characters long.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                           minlength="8" placeholder="Re-enter new password">
                </div>

                <button type="submit" class="btn btn-primary">🔑 Change Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleProfileEdit() {
    const view = document.getElementById('profile-view');
    const edit = document.getElementById('profile-edit');
    if (view.style.display === 'none') {
        view.style.display = '';
        edit.style.display = 'none';
    } else {
        view.style.display = 'none';
        edit.style.display = '';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

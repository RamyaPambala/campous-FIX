<?php
/**
 * CampusFix — Admin Complaint Details & Status Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlash('error', 'Invalid complaint identifier.');
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$db = getDB();

// Fetch complaint with submitting student details
$stmt = $db->prepare(
    'SELECT c.*, u.full_name AS student_name, u.email AS student_email, 
            u.enrollment_number, u.department, u.year_of_study, u.section
     FROM complaints c
     JOIN users u ON c.user_id = u.id
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $pageTitle = 'Complaint Not Found';
    $pageType = 'admin';
    $currentPage = 'complaints';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="empty-state">
        <div class="empty-state-icon">&#9888;</div>
        <h2 class="empty-state-title">Complaint Not Found</h2>
        <p class="empty-state-text">The requested complaint record does not exist or may have been removed.</p>
        <a href="<?= e(BASE_URL) ?>/admin/complaints.php" class="btn btn-primary">Return to Complaints</a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch status history (both public and internal for admin)
$hStmt = $db->prepare(
    'SELECT h.*, u.full_name AS changed_by_name, u.role AS changed_by_role
     FROM complaint_status_history h
     JOIN users u ON h.changed_by = u.id
     WHERE h.complaint_id = ?
     ORDER BY h.created_at ASC'
);
$hStmt->execute([$id]);
$history = $hStmt->fetchAll();

// Fetch feedback if any
$fStmt = $db->prepare('SELECT * FROM feedback WHERE complaint_id = ?');
$fStmt->execute([$id]);
$feedback = $fStmt->fetch();

// Determine allowed next statuses
$currentStatus = $complaint['status'];
$allowedTransitions = STATUS_TRANSITIONS[$currentStatus] ?? [];

$pageTitle = 'Complaint ' . $complaint['reference_number'];
$pageType = 'admin';
$currentPage = 'complaints';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= e(BASE_URL) ?>/admin/complaints.php" class="btn btn-outline btn-sm" style="margin-bottom: 0.5rem;">
            &larr; Back to Complaints
        </a>
        <h1 class="page-title">Complaint #<?= e($complaint['reference_number']) ?></h1>
        <p class="page-subtitle">Submitted on <?= formatDateTime($complaint['created_at']) ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <span class="badge <?= priorityBadgeClass($complaint['priority']) ?>" style="font-size: 0.9rem; padding: 0.4rem 0.9rem;">
            <?= e($complaint['priority']) ?> Priority
        </span>
        <span class="badge <?= statusBadgeClass($complaint['status']) ?>" style="font-size: 0.9rem; padding: 0.4rem 0.9rem;">
            <?= statusIcon($complaint['status']) ?> <?= e($complaint['status']) ?>
        </span>
    </div>
</div>

<div class="grid-2">
    <!-- LEFT COLUMN: Main Details & Timeline -->
    <div>
        <!-- Complaint Content Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= e($complaint['title']) ?></h3>
                <span class="badge badge-default"><?= categoryIcon($complaint['category']) ?> <?= e($complaint['category']) ?></span>
            </div>
            <div class="card-body">
                <h4 style="font-size: 0.95rem; color: var(--secondary); margin-bottom: 0.5rem;">Issue Description</h4>
                <div style="white-space: pre-wrap; line-height: 1.6; font-size: 0.975rem; margin-bottom: 1.5rem; background: var(--bg-page); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <?= e($complaint['description']) ?>
                </div>

                <?php if (!empty($complaint['attachment_path'])): ?>
                    <h4 style="font-size: 0.95rem; color: var(--secondary); margin-bottom: 0.5rem;">Attached Supporting Image</h4>
                    <div style="max-width: 400px; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border); margin-bottom: 1.5rem;">
                        <a href="<?= e(BASE_URL) ?>/uploads/<?= e($complaint['attachment_path']) ?>" target="_blank" title="View Full Size">
                            <img src="<?= e(BASE_URL) ?>/uploads/<?= e($complaint['attachment_path']) ?>" alt="Complaint Evidence Attachment" style="width: 100%; height: auto;">
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($complaint['resolution_comment'])): ?>
                    <div style="background-color: var(--success-light); border: 1px solid var(--success); border-radius: var(--radius-md); padding: 1rem 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--success-dark); font-weight: 700; margin-bottom: 0.35rem;">
                            <span>&#9989;</span> Official Resolution Details
                        </div>
                        <p style="color: var(--success-dark); font-size: 0.925rem; margin: 0;">
                            <?= e($complaint['resolution_comment']) ?>
                        </p>
                        <?php if ($complaint['resolved_at']): ?>
                            <div style="font-size: 0.785rem; color: var(--success-dark); margin-top: 0.5rem; opacity: 0.85;">
                                Resolved on <?= formatDateTime($complaint['resolved_at']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Feedback Card (if exists) -->
        <?php if ($feedback): ?>
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <div class="card-header">
                    <h3 class="card-title">&#11088; Student Resolution Feedback</h3>
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= $feedback['rating'] ? 'active' : '' ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.95rem; color: var(--text-main); font-style: italic;">
                        "<?= e($feedback['comment'] ?: 'No written remarks provided.') ?>"
                    </p>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                        Submitted on <?= formatDateTime($feedback['created_at']) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resolution History Timeline -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">&#128337; Status &amp; Action Timeline</h3>
                <span class="text-muted" style="font-size: 0.85rem;"><?= count($history) ?> records</span>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p class="text-muted">No timeline entries recorded yet.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $idx => $item): 
                            $isLast = ($idx === count($history) - 1);
                        ?>
                            <div class="timeline-item <?= $isLast ? 'success' : '' ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div class="timeline-title">
                                            <?php if ($item['old_status']): ?>
                                                <span class="badge <?= statusBadgeClass($item['old_status']) ?>"><?= e($item['old_status']) ?></span>
                                                &rarr;
                                            <?php endif; ?>
                                            <span class="badge <?= statusBadgeClass($item['new_status']) ?>"><?= e($item['new_status']) ?></span>
                                            
                                            <?php if ($item['visibility'] === 'internal'): ?>
                                                <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.7rem;">&#128274; Internal Only</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="timeline-date"><?= formatDateTime($item['created_at']) ?></span>
                                    </div>

                                    <div style="font-size: 0.85rem; color: var(--secondary); margin-bottom: 0.25rem;">
                                        Updated by: <strong><?= e($item['changed_by_name']) ?></strong> (<?= e($item['changed_by_role']) ?>)
                                    </div>

                                    <?php if (!empty($item['comment'])): ?>
                                        <div class="timeline-comment">
                                            <?= e($item['comment']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Metadata & Admin Actions -->
    <div>
        <!-- Student Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">&#128100; Student Info</h3>
            </div>
            <div class="card-body" style="font-size: 0.9rem;">
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <div>
                        <span class="text-muted">Full Name:</span>
                        <div class="font-semibold"><?= e($complaint['student_name']) ?></div>
                    </div>
                    <div>
                        <span class="text-muted">Enrollment No:</span>
                        <div class="font-mono"><?= e($complaint['enrollment_number']) ?></div>
                    </div>
                    <div>
                        <span class="text-muted">Department:</span>
                        <div><?= e($complaint['department']) ?></div>
                    </div>
                    <div>
                        <span class="text-muted">Year / Section:</span>
                        <div>Year <?= e($complaint['year_of_study']) ?><?= $complaint['section'] ? ' - ' . e($complaint['section']) : '' ?></div>
                    </div>
                    <div>
                        <span class="text-muted">Email:</span>
                        <div><a href="mailto:<?= e($complaint['student_email']) ?>"><?= e($complaint['student_email']) ?></a></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facility Location Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">&#128205; Location Details</h3>
            </div>
            <div class="card-body" style="font-size: 0.9rem;">
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <div>
                        <span class="text-muted">Building / Zone:</span>
                        <div class="font-semibold"><?= e($complaint['building']) ?></div>
                    </div>
                    <div>
                        <span class="text-muted">Room / Lab / Area:</span>
                        <div><?= e($complaint['location_details'] ?: 'Not specified') ?></div>
                    </div>
                    <div>
                        <span class="text-muted">Assigned Staff / Dept:</span>
                        <div class="font-semibold" style="color: var(--primary);">
                            <?= e($complaint['assigned_to'] ?: 'Unassigned') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADMIN STATUS UPDATE CONTROL -->
        <div class="card" style="border-top: 4px solid var(--primary);">
            <div class="card-header">
                <h3 class="card-title">&#9881; Update Status</h3>
            </div>
            <div class="card-body">
                <?php if (empty($allowedTransitions)): ?>
                    <div class="alert alert-info" style="margin-bottom: 0;">
                        <span class="alert-message">
                            This complaint is currently <strong><?= e($currentStatus) ?></strong>. No further status changes are permitted under standard workflow.
                        </span>
                    </div>
                <?php else: ?>
                    <form action="<?= e(BASE_URL) ?>/actions/update_status.php" method="POST" id="status-update-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="complaint_id" value="<?= e($complaint['id']) ?>">

                        <!-- Status Option Buttons: Click any status option to trigger direct update -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; margin-bottom: 8px;">
                                Click Status Option to Update Directly:
                            </label>
                            <div class="status-options-list" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                <?php foreach ($allowedTransitions as $next): ?>
                                    <button type="submit" 
                                            name="new_status" 
                                            value="<?= e($next) ?>" 
                                            class="btn btn-outline-primary status-option-btn" 
                                            onclick="setDirectStatus('<?= e($next) ?>');"
                                            style="font-weight: 600; padding: 7px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                        <span>&#10148;</span>
                                        <span><?= e($next) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Clicking any option triggers the status update directly without confirmation.</small>
                        </div>

                        <div class="form-group" style="margin-top: 1rem;">
                            <label for="new_status" class="form-label">Or Select Status Option</label>
                            <select name="new_status" id="new_status" class="form-select" onchange="onStatusDropdownChange(this)">
                                <option value="">-- Choose Status Option --</option>
                                <?php foreach ($allowedTransitions as $next): ?>
                                    <option value="<?= e($next) ?>"><?= e($next) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="assigned_to" class="form-label">Assign Technician / Staff (Optional)</label>
                            <input type="text" name="assigned_to" id="assigned_to" class="form-control" 
                                   value="<?= e($complaint['assigned_to'] ?? '') ?>" 
                                   placeholder="e.g. IT Team / Electrician Kumar">
                        </div>

                        <div class="form-group">
                            <label for="status_comment" class="form-label">Status Update Comment / Remarks (Optional)</label>
                            <textarea name="comment" id="status_comment" class="form-control" rows="2" 
                                      placeholder="Explain the update, actions taken, or schedule..."></textarea>
                        </div>

                        <!-- Specific resolution comment revealed when Resolved is selected -->
                        <div class="form-group" id="resolution-group" style="display: none;">
                            <label for="resolution_comment" class="form-label">
                                Official Resolution Details (Visible to Student)
                            </label>
                            <textarea name="resolution_comment" id="resolution_comment" class="form-control" rows="2" 
                                      placeholder="Describe what was fixed, replaced, or configured..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Comment Visibility</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="visibility" value="public" checked>
                                    <span>Public (Student notified)</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="visibility" value="internal">
                                    <span>Internal Admin Note</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Update Complaint Status
                        </button>
                    </form>

                    <script>
                    function setDirectStatus(status) {
                        var select = document.getElementById('new_status');
                        if (select) {
                            select.value = status;
                        }
                    }
                    function onStatusDropdownChange(select) {
                        if (select && select.value) {
                            var resolutionGroup = document.getElementById('resolution-group');
                            if (resolutionGroup) {
                                resolutionGroup.style.display = (select.value === 'Resolved') ? 'block' : 'none';
                            }
                            // Directly trigger status update without confirmation
                            select.form.submit();
                        }
                    }
                    </script>
                <?php endif; ?>
            </div>
        </div>

        <!-- ADD INTERNAL NOTE ONLY -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">&#128221; Add Timeline Note</h3>
            </div>
            <div class="card-body">
                <form action="<?= e(BASE_URL) ?>/actions/update_status.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="complaint_id" value="<?= e($complaint['id']) ?>">
                    <input type="hidden" name="same_status" value="1">

                    <div class="form-group">
                        <textarea name="comment" class="form-control" rows="2" required 
                                  placeholder="Add an internal observation or update without changing status..."></textarea>
                    </div>

                    <div class="form-group">
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="visibility" value="internal" checked>
                                <span>Internal Note</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="visibility" value="public">
                                <span>Student Notice</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-sm btn-block">
                        Post Note
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

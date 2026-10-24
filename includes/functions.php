<?php
/**
 * CampusFix — Utility Functions
 */

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ── Output Helpers ─────────────────────────────────────────

/**
 * Escape output for HTML context.
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format a datetime string for display.
 */
function formatDate(?string $dt, string $format = 'M j, Y'): string {
    if (!$dt) return '—';
    try {
        return (new DateTime($dt))->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Format datetime with time.
 */
function formatDateTime(?string $dt): string {
    return formatDate($dt, 'M j, Y · g:i A');
}

/**
 * Relative time (e.g. "2 hours ago").
 */
function timeAgo(?string $dt): string {
    if (!$dt) return '—';
    try {
        $now  = new DateTime();
        $then = new DateTime($dt);
        $diff = $now->diff($then);

        if ($diff->days > 30) return formatDate($dt);
        if ($diff->days > 0) return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    } catch (Exception $e) {
        return '—';
    }
}

// ── Complaint Helpers ──────────────────────────────────────

/**
 * Generate a unique complaint reference number.
 */
function generateReferenceNumber(): string {
    $prefix = 'CF';
    $date   = date('Ymd');
    $random = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
    return $prefix . '-' . $date . '-' . $random;
}

/**
 * CSS class for a status badge.
 */
function statusBadgeClass(string $status): string {
    return match($status) {
        'Submitted'   => 'badge-submitted',
        'Acknowledged' => 'badge-acknowledged',
        'In Progress' => 'badge-progress',
        'Resolved'    => 'badge-resolved',
        'Closed'      => 'badge-closed',
        'Reopened'    => 'badge-reopened',
        default       => 'badge-default',
    };
}

/**
 * CSS class for priority badge.
 */
function priorityBadgeClass(string $priority): string {
    return match($priority) {
        'High'   => 'badge-high',
        'Medium' => 'badge-medium',
        'Low'    => 'badge-low',
        default  => 'badge-default',
    };
}

/**
 * Icon class for category.
 */
function categoryIcon(string $category): string {
    return match($category) {
        'Laboratory and Computers' => '&#128187;',
        'Internet and Wi-Fi'       => '&#128246;',
        'Water Supply'             => '&#128167;',
        'Washroom Maintenance'     => '&#128701;',
        'Canteen Cleanliness'      => '&#127860;',
        'Electrical Issues'        => '&#9889;',
        'Classroom Facilities'     => '&#127979;',
        'Drinking Water'           => '&#129380;',
        'Other'                    => '&#128736;',
        default                    => '&#128204;',
    };
}

/**
 * Status icon.
 */
function statusIcon(string $status): string {
    return match($status) {
        'Submitted'    => '&#128233;',
        'Acknowledged' => '&#128065;',
        'In Progress'  => '&#9881;',
        'Resolved'     => '&#9989;',
        'Closed'       => '&#128274;',
        'Reopened'     => '&#128260;',
        default        => '&#9679;',
    };
}

// ── Flash Messages ────────────────────────────────────────

/**
 * Set a flash message.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a flash message.
 */
function getFlash(string $type): ?string {
    $key = 'flash_' . $type;
    $msg = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $msg;
}

/**
 * Render flash messages HTML.
 */
function renderFlashMessages(): string {
    $html = '';
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $msg = getFlash($type);
        if ($msg) {
            $icon = match($type) {
                'success' => '&#9989;',
                'error'   => '&#10060;',
                'warning' => '&#9888;',
                'info'    => '&#8505;',
            };
            $html .= '<div class="alert alert-' . $type . '" role="alert">'
                   . '<span class="alert-icon">' . $icon . '</span>'
                   . '<span class="alert-message">' . e($msg) . '</span>'
                   . '<button type="button" class="alert-close" aria-label="Close">&times;</button>'
                   . '</div>';
        }
    }
    return $html;
}

// ── Notification Helpers ──────────────────────────────────

/**
 * Create a notification for a user.
 */
function createNotification(int $userId, ?int $complaintId, string $title, string $message): void {
    $stmt = getDB()->prepare(
        'INSERT INTO notifications (user_id, complaint_id, title, message) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $complaintId, $title, $message]);
}

/**
 * Get unread notification count for current user.
 */
function getUnreadNotificationCount(int $userId): int {
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// ── Pagination Helper ──────────────────────────────────────

/**
 * Calculate pagination values.
 */
function paginate(int $total, int $page = 1, int $perPage = 10): array {
    $page    = max(1, $page);
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = min($page, $pages);
    $offset  = ($page - 1) * $perPage;

    return [
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => $pages,
        'offset'   => $offset,
    ];
}

/**
 * Render pagination HTML.
 */
function renderPagination(array $pg, string $baseUrl, array $params = []): string {
    if ($pg['pages'] <= 1) return '';

    $html = '<nav class="pagination" aria-label="Pagination">';

    // Build base query string without page
    $qs = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
    $sep = $qs ? '&' : '';

    // Previous
    if ($pg['page'] > 1) {
        $html .= '<a href="' . e($baseUrl . '?' . $qs . $sep . 'page=' . ($pg['page'] - 1)) . '" class="page-link">&laquo; Prev</a>';
    } else {
        $html .= '<span class="page-link disabled">&laquo; Prev</span>';
    }

    // Page numbers
    $start = max(1, $pg['page'] - 2);
    $end   = min($pg['pages'], $pg['page'] + 2);

    if ($start > 1) {
        $html .= '<a href="' . e($baseUrl . '?' . $qs . $sep . 'page=1') . '" class="page-link">1</a>';
        if ($start > 2) $html .= '<span class="page-dots">…</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pg['page'] ? ' active' : '';
        $html .= '<a href="' . e($baseUrl . '?' . $qs . $sep . 'page=' . $i) . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($end < $pg['pages']) {
        if ($end < $pg['pages'] - 1) $html .= '<span class="page-dots">…</span>';
        $html .= '<a href="' . e($baseUrl . '?' . $qs . $sep . 'page=' . $pg['pages']) . '" class="page-link">' . $pg['pages'] . '</a>';
    }

    // Next
    if ($pg['page'] < $pg['pages']) {
        $html .= '<a href="' . e($baseUrl . '?' . $qs . $sep . 'page=' . ($pg['page'] + 1)) . '" class="page-link">Next &raquo;</a>';
    } else {
        $html .= '<span class="page-link disabled">Next &raquo;</span>';
    }

    $html .= '</nav>';
    return $html;
}

// ── Statistics Helpers (Admin) ─────────────────────────────

/**
 * Get complaint statistics from the database.
 */
function getComplaintStats(): array {
    $db = getDB();
    $stats = [];

    $stmt = $db->query('SELECT COUNT(*) FROM complaints');
    $stats['total'] = (int) $stmt->fetchColumn();

    foreach (COMPLAINT_STATUSES as $status) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM complaints WHERE status = ?');
        $stmt->execute([$status]);
        $key = strtolower(str_replace(' ', '_', $status));
        $stats[$key] = (int) $stmt->fetchColumn();
    }

    $stmt = $db->query("SELECT COUNT(*) FROM complaints WHERE priority = 'High' AND status NOT IN ('Resolved','Closed')");
    $stats['high_priority_open'] = (int) $stmt->fetchColumn();

    return $stats;
}

/**
 * Get student's complaint statistics.
 */
function getStudentComplaintStats(int $userId): array {
    $db = getDB();
    $stats = [];

    $stmt = $db->prepare('SELECT COUNT(*) FROM complaints WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats['total'] = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'Submitted'");
    $stmt->execute([$userId]);
    $stats['submitted'] = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status IN ('Acknowledged','In Progress')");
    $stmt->execute([$userId]);
    $stats['in_progress'] = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'Resolved'");
    $stmt->execute([$userId]);
    $stats['resolved'] = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'Closed'");
    $stmt->execute([$userId]);
    $stats['closed'] = (int) $stmt->fetchColumn();

    return $stats;
}

/**
 * Get category distribution (admin analytics).
 */
function getCategoryDistribution(): array {
    $stmt = getDB()->query('SELECT category, COUNT(*) as count FROM complaints GROUP BY category ORDER BY count DESC');
    $results = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Ensure at least 4-5 categories are visible so the chart panel is visually balanced
    if (count($results) < 4) {
        $existing = array_column($results, 'category');
        $defaults = [
            'Laboratory and Computers',
            'Internet and Wi-Fi',
            'Electrical Issues',
            'Washroom Maintenance',
            'Classroom Facilities'
        ];
        foreach ($defaults as $cat) {
            if (!in_array($cat, $existing, true)) {
                $results[] = ['category' => $cat, 'count' => 0];
            }
            if (count($results) >= 5) break;
        }
    }

    return $results;
}

/**
 * Get monthly complaint trend (last 6 months).
 */
function getMonthlyTrend(): array {
    // Pre-populate array with last 6 calendar months in chronological order
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $timestamp = strtotime("-$i months");
        $key = date('Y-m', $timestamp);
        $label = date('M Y', $timestamp);
        $months[$key] = [
            'month' => $key,
            'label' => $label,
            'count' => 0
        ];
    }

    try {
        $db = getDB();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $db->query(
                "SELECT strftime('%Y-%m', created_at) as month,
                        COUNT(*) as count
                 FROM complaints
                 WHERE created_at >= date('now', '-6 month')
                 GROUP BY month
                 ORDER BY month ASC"
            );
        } else {
            $stmt = $db->query(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as count
                 FROM complaints
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 GROUP BY month
                 ORDER BY month ASC"
            );
        }
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($months[$row['month']])) {
                    $months[$row['month']]['count'] = (int) $row['count'];
                }
            }
        }
    } catch (Exception $e) {
        // Return pre-populated months on error
    }

    return array_values($months);
}

/**
 * Get public statistics for landing page.
 */
function getPublicStats(): array {
    try {
        $db = getDB();
        $stats = [];
        $stmt = $db->query('SELECT COUNT(*) FROM complaints');
        $stats['total_complaints'] = (int) $stmt->fetchColumn();
        $stmt = $db->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Resolved','Closed')");
        $resolved = (int) $stmt->fetchColumn();
        $stats['resolved'] = $resolved;
        $stats['resolved_complaints'] = $resolved;
        $stmt = $db->query('SELECT COUNT(DISTINCT user_id) FROM complaints');
        $stats['active_students'] = (int) $stmt->fetchColumn();
        $stmt = $db->query('SELECT COUNT(DISTINCT category) FROM complaints');
        $categories = (int) $stmt->fetchColumn();
        $stats['categories_covered'] = $categories;
        $stats['categories_used'] = $categories;
        return $stats;
    } catch (Exception $e) {
        return [];
    }
}

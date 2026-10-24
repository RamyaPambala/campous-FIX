<?php
/**
 * CampusFix — Automated Test Suite & Verification Engine
 * Runs static, logic, security, and database integration tests.
 */

declare(strict_types=1);

// Ensure CLI execution
if (php_sapi_name() !== 'cli') {
    die("This test suite must be run from the command line.\n");
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

class CampusFixTestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];
    private ?PDO $testDb = null;

    public function run(): void {
        echo "\n=======================================================\n";
        echo "   CAMPUSFIX AUTOMATED TEST SUITE & VERIFICATION\n";
        echo "=======================================================\n\n";

        $this->setupTestDatabase();

        echo "[+] Section 1: Helper, Escaping & Utility Tests\n";
        $this->testOutputEscaping();
        $this->testDateFormatting();
        $this->testRelativeTime();
        $this->testBadgeClasses();
        $this->testCategoryIcons();
        $this->testReferenceNumberGeneration();
        $this->testPaginationCalculations();

        echo "\n[+] Section 2: Input Validation & Security Tests\n";
        $this->testRegistrationValidation();
        $this->testProfileValidation();
        $this->testComplaintValidation();
        $this->testFeedbackValidation();
        $this->testCsrfVerification();
        $this->testSqlInjectionSanitization();

        echo "\n[+] Section 3: Status Transition State Machine Tests\n";
        $this->testStatusTransitions();

        echo "\n[+] Section 4: Database Integration & Workflow Tests\n";
        $this->testDatabaseUserPersistence();
        $this->testComplaintWorkflow();
        $this->testStatusHistoryAudit();
        $this->testNotificationWorkflow();
        $this->testFeedbackWorkflow();
        $this->testRateLimiting();

        echo "\n[+] Section 5: Static Include & File Integrity Tests\n";
        $this->testFileIncludes();

        echo "\n=======================================================\n";
        echo "   TEST SUMMARY\n";
        echo "=======================================================\n";
        echo "Total Tests Run : " . ($this->passed + $this->failed) . "\n";
        echo "Passed          : {$this->passed}\n";
        echo "Failed          : {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->failures as $f) {
                echo "  - $f\n";
            }
            exit(1);
        } else {
            echo "\nALL TESTS PASSED SUCCESSFULLY!\n\n";
            exit(0);
        }
    }

    private function assert(bool $condition, string $testName): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] $testName\n";
        } else {
            $this->failed++;
            $this->failures[] = $testName;
            echo "  [FAIL] $testName\n";
        }
    }

    private function setupTestDatabase(): void {
        $this->testDb = new PDO('sqlite::memory:');
        $this->testDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->testDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Create test schema compatible with SQLite
        $this->testDb->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                enrollment_number TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                department TEXT NOT NULL,
                year_of_study INTEGER DEFAULT 1,
                section TEXT,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'student',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE complaints (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference_number TEXT UNIQUE NOT NULL,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                category TEXT NOT NULL,
                building TEXT NOT NULL,
                location_details TEXT,
                priority TEXT DEFAULT 'Medium',
                status TEXT DEFAULT 'Submitted',
                attachment_path TEXT,
                resolution_comment TEXT,
                assigned_to TEXT,
                resolved_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE complaint_status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                complaint_id INTEGER NOT NULL,
                changed_by INTEGER NOT NULL,
                old_status TEXT,
                new_status TEXT NOT NULL,
                comment TEXT,
                visibility TEXT DEFAULT 'public',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE feedback (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                complaint_id INTEGER UNIQUE NOT NULL,
                student_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                comment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                complaint_id INTEGER,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                email TEXT NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Set test DB in the singleton
        setTestDB($this->testDb);
    }

    private function testOutputEscaping(): void {
        $xss = '<script>alert("XSS")</script>';
        $escaped = e($xss);
        $this->assert(
            $escaped === '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            'HTML escaping prevents script injection'
        );

        $attrXss = '"><img src=x onerror=alert(1)>';
        $this->assert(
            strpos(e($attrXss), '<') === false,
            'Attribute breakout characters properly escaped'
        );
    }

    private function testRelativeTime(): void {
        $now = date('Y-m-d H:i:s');
        $this->assert(timeAgo($now) === 'Just now', 'timeAgo returns Just now for current timestamp');

        $pastHour = date('Y-m-d H:i:s', time() - 3600);
        $this->assert(strpos(timeAgo($pastHour), 'hour') !== false, 'timeAgo calculates hours ago');

        $pastDay = date('Y-m-d H:i:s', time() - 86400 * 2);
        $this->assert(strpos(timeAgo($pastDay), 'days ago') !== false, 'timeAgo calculates days ago');
    }

    private function testCategoryIcons(): void {
        foreach (COMPLAINT_CATEGORIES as $cat) {
            $icon = categoryIcon($cat);
            $this->assert(!empty($icon), "categoryIcon defined for '$cat'");
        }
        foreach (COMPLAINT_STATUSES as $stat) {
            $icon = statusIcon($stat);
            $this->assert(!empty($icon), "statusIcon defined for '$stat'");
        }
    }

    private function testProfileValidation(): void {
        $valid = [
            'full_name' => 'Dr. Vikram Sarabhai',
            'department' => 'Mechanical Engineering',
            'year_of_study' => 3,
            'section' => 'B'
        ];
        $this->assert(empty(validateProfileUpdate($valid)), 'Valid profile update payload produces no errors');

        $invalid = $valid;
        $invalid['year_of_study'] = 7; // Only 1-6 allowed
        $this->assert(isset(validateProfileUpdate($invalid)['year_of_study']), 'Invalid year of study (> 6) rejected');
    }

    private function testSqlInjectionSanitization(): void {
        $sqliPayload = "'; DROP TABLE users; --";
        $sanitized = sanitize($sqliPayload);
        $this->assert($sanitized === "'; DROP TABLE users; --", 'sanitize preserves string without raw null bytes');

        // Test with null byte injection attack vector
        $nullByte = "test\0injection";
        $this->assert(sanitize($nullByte) === 'testinjection', 'sanitize strips dangerous null bytes');
    }

    private function testDateFormatting(): void {
        $dt = '2026-10-24 14:30:00';
        $formatted = formatDate($dt);
        $this->assert($formatted === 'Oct 24, 2026', 'formatDate returns correct format');

        $formattedTime = formatDateTime($dt);
        $this->assert(strpos($formattedTime, 'Oct 24, 2026') !== false, 'formatDateTime returns date with time');

        $this->assert(formatDate(null) === '—', 'formatDate handles null gracefully');
    }

    private function testBadgeClasses(): void {
        $this->assert(statusBadgeClass('Submitted') === 'badge-submitted', 'statusBadgeClass for Submitted');
        $this->assert(statusBadgeClass('In Progress') === 'badge-progress', 'statusBadgeClass for In Progress');
        $this->assert(statusBadgeClass('Resolved') === 'badge-resolved', 'statusBadgeClass for Resolved');
        $this->assert(priorityBadgeClass('High') === 'badge-high', 'priorityBadgeClass for High');
        $this->assert(priorityBadgeClass('Low') === 'badge-low', 'priorityBadgeClass for Low');
    }

    private function testReferenceNumberGeneration(): void {
        $ref1 = generateReferenceNumber();
        $ref2 = generateReferenceNumber();
        $this->assert(str_starts_with($ref1, 'CF-'), 'Reference number starts with CF-');
        $this->assert($ref1 !== $ref2, 'Sequential reference numbers are unique');
        $this->assert(strlen($ref1) >= 15, 'Reference number has sufficient entropy');
    }

    private function testPaginationCalculations(): void {
        $pg = paginate(105, 2, 10);
        $this->assert($pg['total'] === 105, 'Pagination total count matches');
        $this->assert($pg['pages'] === 11, 'Pagination page count matches (11 pages for 105 items)');
        $this->assert($pg['offset'] === 10, 'Pagination offset calculated correctly for page 2');
        $this->assert($pg['page'] === 2, 'Pagination current page matches');
    }

    private function testRegistrationValidation(): void {
        $valid = [
            'full_name' => 'Aarav Patel',
            'enrollment_number' => 'EC-2024-089',
            'email' => 'aarav@campus.edu',
            'department' => 'Electronics and Communication Engineering',
            'year_of_study' => '2',
            'section' => 'A',
            'password' => 'SecurePass123',
            'confirm_password' => 'SecurePass123',
        ];
        $errors = validateRegistration($valid);
        $this->assert(empty($errors), 'Valid registration payload produces no errors');

        // Test mismatched passwords
        $invalid = $valid;
        $invalid['confirm_password'] = 'MismatchPass';
        $errors = validateRegistration($invalid);
        $this->assert(isset($errors['confirm_password']), 'Mismatched passwords rejected');

        // Test short password
        $invalid = $valid;
        $invalid['password'] = 'short';
        $invalid['confirm_password'] = 'short';
        $errors = validateRegistration($invalid);
        $this->assert(isset($errors['password']), 'Short password rejected (< 8 chars)');

        // Test invalid email
        $invalid = $valid;
        $invalid['email'] = 'not-an-email';
        $errors = validateRegistration($invalid);
        $this->assert(isset($errors['email']), 'Malformed email address rejected');

        // Test invalid department
        $invalid = $valid;
        $invalid['department'] = 'Nonexistent Department';
        $errors = validateRegistration($invalid);
        $this->assert(isset($errors['department']), 'Invalid department rejected');
    }

    private function testComplaintValidation(): void {
        $valid = [
            'title' => 'Laboratory 2 Wi-Fi Access Point Offline',
            'description' => 'The access point near workstation 10 has been blinking orange since morning.',
            'category' => 'Internet and Wi-Fi',
            'building' => 'Engineering Block A',
            'location_details' => 'Room 204',
            'priority' => 'High',
        ];
        $errors = validateComplaint($valid);
        $this->assert(empty($errors), 'Valid complaint produces no errors');

        // Test short title
        $invalid = $valid;
        $invalid['title'] = 'Fix';
        $errors = validateComplaint($invalid);
        $this->assert(isset($errors['title']), 'Too short complaint title rejected');

        // Test invalid category
        $invalid = $valid;
        $invalid['category'] = 'Unknown Category';
        $errors = validateComplaint($invalid);
        $this->assert(isset($errors['category']), 'Unlisted complaint category rejected');

        // Test invalid priority
        $invalid = $valid;
        $invalid['priority'] = 'CriticalEmergency';
        $errors = validateComplaint($invalid);
        $this->assert(isset($errors['priority']), 'Unlisted complaint priority rejected');
    }

    private function testFeedbackValidation(): void {
        $this->assert(empty(validateFeedback(['rating' => 5, 'comment' => 'Great!'])), 'Rating 5 is valid');
        $this->assert(empty(validateFeedback(['rating' => 1, 'comment' => 'Poor'])), 'Rating 1 is valid');
        $this->assert(isset(validateFeedback(['rating' => 0])['rating']), 'Rating 0 is rejected');
        $this->assert(isset(validateFeedback(['rating' => 6])['rating']), 'Rating 6 is rejected');
    }

    private function testCsrfVerification(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = csrfToken();
        $this->assert(!empty($token) && strlen($token) === 64, 'CSRF token is 64 hex characters');

        $_POST['csrf_token'] = $token;
        $this->assert(validateCsrf(), 'Matching CSRF token validates successfully');

        $_POST['csrf_token'] = 'tampered_token';
        $this->assert(!validateCsrf(), 'Tampered CSRF token is rejected');
        unset($_POST['csrf_token']);
    }

    private function testStatusTransitions(): void {
        // Submitted can transition to Acknowledged, In Progress, Closed
        $submittedNext = STATUS_TRANSITIONS['Submitted'];
        $this->assert(in_array('Acknowledged', $submittedNext, true), 'Submitted -> Acknowledged allowed');
        $this->assert(in_array('In Progress', $submittedNext, true), 'Submitted -> In Progress allowed');
        $this->assert(!in_array('Resolved', $submittedNext, true), 'Submitted -> Resolved disallowed (must triage first)');

        // In Progress can transition to Resolved or Closed
        $inProgressNext = STATUS_TRANSITIONS['In Progress'];
        $this->assert(in_array('Resolved', $inProgressNext, true), 'In Progress -> Resolved allowed');
        $this->assert(!in_array('Submitted', $inProgressNext, true), 'In Progress -> Submitted disallowed');

        // Resolved can transition to Closed or Reopened
        $resolvedNext = STATUS_TRANSITIONS['Resolved'];
        $this->assert(in_array('Reopened', $resolvedNext, true), 'Resolved -> Reopened allowed');
    }

    private function testDatabaseUserPersistence(): void {
        $db = getDB();

        // Insert student
        $stmt = $db->prepare('
            INSERT INTO users (full_name, enrollment_number, email, department, year_of_study, section, password_hash, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $hash = password_hash('Student@123', PASSWORD_DEFAULT);
        $stmt->execute(['Rohan V', 'CS-2024-001', 'rohan@campus.edu', 'Computer Science and Engineering', 2, 'A', $hash, 'student']);
        $studentId = (int) $db->lastInsertId();

        $this->assert($studentId > 0, 'Student record inserted with ID ' . $studentId);

        // Query user back
        $q = $db->prepare('SELECT * FROM users WHERE id = ?');
        $q->execute([$studentId]);
        $user = $q->fetch();
        $this->assert($user['email'] === 'rohan@campus.edu', 'Student retrieved with correct email');
        $this->assert(password_verify('Student@123', $user['password_hash']), 'password_verify matches stored hash');
        $this->assert(!password_verify('WrongPass', $user['password_hash']), 'password_verify rejects incorrect password');

        // Insert administrator
        $adminHash = password_hash('Admin@2026', PASSWORD_DEFAULT);
        $stmt->execute(['Campus Administrator', 'ADMIN-01', 'admin@campus.edu', 'Administration', 0, '', $adminHash, 'admin']);
        $adminId = (int) $db->lastInsertId();
        $this->assert($adminId > 0, 'Admin record inserted with ID ' . $adminId);
    }

    private function testComplaintWorkflow(): void {
        $db = getDB();
        $ref = generateReferenceNumber();

        // Create complaint
        $stmt = $db->prepare('
            INSERT INTO complaints (reference_number, user_id, title, description, category, building, location_details, priority, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $ref,
            1, // Student ID 1
            'Broken Projector Cable in Room 301',
            'HDMI cable connector has bent pins and does not display projection.',
            'Classroom Facilities',
            'Main Academic Block',
            'Room 301, 3rd Floor',
            'High',
            'Submitted'
        ]);
        $complaintId = (int) $db->lastInsertId();
        $this->assert($complaintId > 0, 'Complaint created with ID ' . $complaintId . ' and Ref ' . $ref);

        // Test statistics
        $stats = getComplaintStats();
        $this->assert($stats['total'] >= 1, 'getComplaintStats returns at least 1 total');
        $this->assert($stats['submitted'] >= 1, 'getComplaintStats returns at least 1 submitted');

        $studentStats = getStudentComplaintStats(1);
        $this->assert($studentStats['total'] >= 1, 'getStudentComplaintStats calculates student complaints');
    }

    private function testStatusHistoryAudit(): void {
        $db = getDB();

        // Log initial submission status in history
        $stmt = $db->prepare('
            INSERT INTO complaint_status_history (complaint_id, changed_by, old_status, new_status, comment, visibility)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([1, 1, null, 'Submitted', 'Complaint logged', 'public']);

        // Update complaint status to In Progress
        $up = $db->prepare("UPDATE complaints SET status = 'In Progress' WHERE id = 1");
        $up->execute();

        // Log status change by admin
        $stmt->execute([1, 2, 'Submitted', 'In Progress', 'Technician deployed', 'public']);

        // Query history
        $hStmt = $db->prepare('SELECT * FROM complaint_status_history WHERE complaint_id = 1 ORDER BY id ASC');
        $hStmt->execute();
        $history = $hStmt->fetchAll();

        $this->assert(count($history) === 2, 'History records audited both status changes');
        $this->assert($history[1]['new_status'] === 'In Progress', 'Second history record shows In Progress');
    }

    private function testNotificationWorkflow(): void {
        $db = getDB();

        // Create notification
        createNotification(1, 1, 'Complaint In Progress', 'Technician Suresh is en route to Room 301.');

        $unread = getUnreadNotificationCount(1);
        $this->assert($unread === 1, 'Unread notification count is 1');

        // Mark as read
        $up = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = 1');
        $up->execute();

        $unreadAfter = getUnreadNotificationCount(1);
        $this->assert($unreadAfter === 0, 'Unread notification count drops to 0 after mark read');
    }

    private function testFeedbackWorkflow(): void {
        $db = getDB();

        // Submit feedback for complaint 1 by student 1
        $stmt = $db->prepare('
            INSERT INTO feedback (complaint_id, student_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([1, 1, 5, 'Quick response and fixed before afternoon class!']);
        $fbId = (int) $db->lastInsertId();
        $this->assert($fbId > 0, 'Feedback recorded with ID ' . $fbId);

        // Test duplicate feedback rejection via unique constraint
        $duplicateThrown = false;
        try {
            $stmt->execute([1, 1, 4, 'Duplicate attempt']);
        } catch (PDOException $e) {
            $duplicateThrown = true;
        }
        $this->assert($duplicateThrown, 'Database unique constraint prevents duplicate feedback for same complaint');
    }

    private function testRateLimiting(): void {
        $ip = '127.0.0.1';
        $testEmail = 'brute@campus.edu';

        $_SERVER['REMOTE_ADDR'] = $ip;

        $this->assert(!isLoginRateLimited($testEmail), 'New account is not rate-limited initially');

        // Simulate 5 failed attempts
        for ($i = 0; $i < LOGIN_MAX_ATTEMPTS; $i++) {
            recordLoginAttempt($testEmail);
        }

        $this->assert(isLoginRateLimited($testEmail), 'Account is locked out after ' . LOGIN_MAX_ATTEMPTS . ' attempts');
    }

    private function testFileIncludes(): void {
        $projectRoot = dirname(__DIR__);
        $phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));

        $missingFiles = [];
        $checkedFiles = 0;

        foreach ($phpFiles as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $checkedFiles++;
                $content = file_get_contents($file->getPathname());

                // Find all require/include statements
                if (preg_match_all("/require(?:_once)?\s+__DIR__\s*\.\s*'([^']+)'/", $content, $matches)) {
                    foreach ($matches[1] as $rel) {
                        $target = $file->getPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                        if (!file_exists($target)) {
                            $missingFiles[] = basename($file->getPathname()) . " -> $rel";
                        }
                    }
                }
            }
        }

        $this->assert(empty($missingFiles), "All require/include paths across $checkedFiles PHP files exist on disk");
    }
}

// Execute test suite
$runner = new CampusFixTestRunner();
$runner->run();

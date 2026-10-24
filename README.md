# CampusFix — Smart Campus Facility Monitoring and Issue Resolution System

> **B.Tech Second-Year Web Development Mini Project**  
> Built strictly with **PHP 8.2+ (PDO)**, **MySQL / MariaDB**, **HTML5**, **CSS3**, and **Vanilla JavaScript**.  
> Zero heavy frontend frameworks &middot; Zero backend Node/Python &middot; Production-oriented security architecture.

---

## 1. Project Overview & Vision

**CampusFix** is a centralized campus facility complaint and infrastructure monitoring platform designed for college campuses. It bridges the communication gap between students experiencing classroom/laboratory issues and campus administrative maintenance divisions.

### Core Capabilities:
- **Student Portal**:
  - Secure registration and role-isolated session management.
  - Interactive complaint submission with building/room location details, category selection, priority tagging, and optional photo attachment evidence.
  - Real-time status lifecycle tracking: `Submitted` &rarr; `Acknowledged` &rarr; `In Progress` &rarr; `Resolved` &rarr; `Closed` (or `Reopened`).
  - Transparent resolution timeline with administrative remarks.
  - In-app notification center for status milestones and notices.
  - 5-star resolution rating and qualitative feedback upon issue completion.
  - Personal profile view, account management, and secure password updating.
- **Administrator Portal**:
  - Centralized management dashboard with real-time database-derived analytics (total complaints, pending triage, in-progress work orders, high-priority unresolved alerts).
  - Multi-parameter filtering (full-text search across titles/references/students, category, status, priority, and date range).
  - Status transition workflow enforcing strict lifecycle rules.
  - Separation between student-visible public notices and internal administrative notes.
  - Staff assignment tracking and official resolution remarks.
  - Student feedback reviews.

---

## 2. Technology Stack & Constraints

- **Backend**: PHP 8.1 / 8.2 / 8.3 (Native procedural/modular with PDO prepared statements).
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB 10.4+.
- **Frontend**: Semantic HTML5, CSS3 Custom Properties (Design System), Vanilla JavaScript (ES6+).
- **Server Environment**: Apache 2.4+ (XAMPP / WampServer / LAMP stack).
- **Security**:
  - `password_hash()` (Bcrypt / Argon2id) & `password_verify()`.
  - Cryptographic session generation and `session_regenerate_id(true)`.
  - Double-submit CSRF token verification on all POST actions.
  - Sanitization, output escaping (`htmlspecialchars()`), and strict MIME-type file upload validation with `.htaccess` script execution prevention.
  - IP-based brute-force login rate limiting.

---

## 3. Complete Step-by-Step XAMPP Setup Guide

### Step 1: Prerequisites & Software Download
1. Download **XAMPP for Windows** (PHP 8.2.x or 8.1.x recommended) from [Apache Friends](https://www.apachefriends.org/).
2. Run the installer and ensure **Apache**, **MySQL**, and **phpMyAdmin** components are checked.
3. Complete the installation (default path: `C:\xampp`).

### Step 2: Project Deployment
1. Navigate to your XAMPP web root directory:
   ```
   C:\xampp\htdocs\
   ```
2. Copy or clone the `campusfix` folder into `htdocs`:
   ```
   C:\xampp\htdocs\campusfix
   ```
3. Verify that the file structure matches:
   ```
   C:\xampp\htdocs\campusfix\
     ├── actions/
     ├── admin/
     ├── assets/
     │   ├── css/style.css
     │   └── js/app.js
     ├── includes/
     │   ├── auth.php
     │   ├── config.php
     │   ├── csrf.php
     │   ├── db.php
     │   ├── functions.php
     │   ├── header.php
     │   ├── footer.php
     │   └── validation.php
     ├── student/
     ├── uploads/
     │   └── .htaccess
     ├── database.sql
     ├── index.php
     ├── login.php
     ├── logout.php
     ├── register.php
     ├── setup_admin.php
     └── README.md
   ```

### Step 3: Start Apache and MySQL
1. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Click **Start** next to **Apache**. Verify the port indicator shows `80, 443` (green status).
3. Click **Start** next to **MySQL**. Verify the port indicator shows `3306` (green status).

### Step 4: Create the Database & Import Schema
#### Method A: Using phpMyAdmin (Browser GUI - Recommended)
1. Open your browser and visit: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Click on **New** in the left sidebar navigation.
3. In the "Database name" input field, type:
   ```
   campusfix
   ```
4. Select `utf8mb4_unicode_ci` from the collation dropdown and click **Create**.
5. With `campusfix` selected, click on the **Import** tab in the top navigation bar.
6. Click **Choose File** and select:
   ```
   C:\xampp\htdocs\campusfix\database.sql
   ```
7. Click the **Import** button at the bottom of the page.
8. Confirm that all tables (`users`, `complaints`, `complaint_status_history`, `feedback`, `notifications`, `login_attempts`) were created successfully.

#### Method B: Using MySQL Command Line
1. Open the Windows Terminal or Command Prompt and run:
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root -p
   ```
   *(Press Enter if no password is set).*
2. Execute the following SQL statements:
   ```sql
   CREATE DATABASE campusfix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE campusfix;
   SOURCE C:/xampp/htdocs/campusfix/database.sql;
   EXIT;
   ```

### Step 5: Database Connection Configuration
Open `includes/config.php` in any text editor. The default settings match standard local XAMPP installations:
```php
define('DB_HOST', getenv('CF_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('CF_DB_NAME') ?: 'campusfix');
define('DB_USER', getenv('CF_DB_USER') ?: 'root');
define('DB_PASS', getenv('CF_DB_PASS') ?: ''); // Empty by default in XAMPP
```
*Note: If you have configured a custom MySQL root password in phpMyAdmin, enter it inside `DB_PASS`.*

---

## 4. Initial Administrator Account Provisioning

CampusFix incorporates a dedicated, secure setup utility (`setup_admin.php`):

1. Open your web browser and navigate to:
   ```
   http://localhost/campusfix/setup_admin.php
   ```
2. Fill in the administrator registration form:
   - **Full Name**: Campus Facilities Administrator
   - **Email**: `admin@campus.edu`
   - **Password**: Enter a secure password (minimum 8 characters)
   - **Confirm Password**: Re-enter the password
3. Click **Create Administrator Account**.
4. Upon successful creation, the system inserts the admin with role `admin` and unique enrollment identifier `ADMIN-XXXXXX`.
5. **Security Lockdown**:
   Once created, edit `includes/config.php` and disable the setup script:
   ```php
   define('ADMIN_SETUP_ENABLED', false);
   ```
   *Any future access attempts to `setup_admin.php` will be blocked automatically.*

---

## 5. End-to-End Application Testing & Demo Walkthrough

### 1. Public Landing Page
- Navigate to: [http://localhost/campusfix/](http://localhost/campusfix/)
- Test mobile responsive menu toggle.
- Verify smooth scrolling to `#features`, `#how-it-works`, and `#categories`.
- Inspect the interactive UI dashboard mockup preview.

### 2. Student Registration & Validation
- Click **Register** or go to `http://localhost/campusfix/register.php`.
- Test client-side and server-side validation:
  - Try submitting blank fields.
  - Try submitting passwords shorter than 8 characters or mismatched passwords.
  - Register a valid student:
    - **Name**: Priya Sharma
    - **Enrollment No**: CS-2024-042
    - **Email**: `priya.cs24@campus.edu`
    - **Department**: Computer Science and Engineering
    - **Year**: 2
    - **Password**: `Priya@2024`
- Confirm redirect to `login.php` with success banner.

### 3. Student Complaint Submission
- Log in with the registered student credentials.
- Notice the personalized greeting on the Student Dashboard.
- Click **Report Issue** (`student/create_complaint.php`).
- Complete the form:
  - **Title**: Lab 3 - Workstation #14 Monitor Flickering & No Network
  - **Category**: Laboratory and Computers
  - **Building**: Computer Science Block (Aryabhatta Hall)
  - **Room / Lab**: Hardware Lab 3, 2nd Floor
  - **Priority**: High
  - **Description**: The monitor for workstation 14 flickers continuously, and the RJ-45 LAN port is physically broken.
  - **Attachment**: Attach a sample JPG or PNG file (under 2 MB).
- Submit the complaint.
- Verify redirect to `student/complaint_details.php?id=1` with generated reference ID (e.g. `CF-20261024-A1B2C3`).
- Verify timeline shows the initial "Submitted" state.

### 4. Admin Management & Status Workflow
- Click **Log Out** and log in with the Administrator credentials (`admin@campus.edu`).
- On the Admin Dashboard (`admin/dashboard.php`), observe:
  - Stat cards updated from the database: Total (1), New Submissions (1), High Priority Open (1).
  - CSS Bar Chart showing category distribution.
- Click **Manage Complaints** (`admin/complaints.php`).
- Use the search bar to locate the complaint by reference code or keyword.
- Open the complaint details (`admin/complaint_details.php?id=1`).
- **Acknowledge Issue**:
  - Select new status: `Acknowledged`.
  - Enter comment: "Complaint acknowledged. Hardware maintenance staff scheduled for inspection."
  - Set Assigned Staff: `Mr. Suresh (Lab Tech)`.
  - Submit update.
- **Move to In Progress**:
  - Update status to `In Progress`.
  - Add remarks: "Technician on site replacing video cable and Ethernet adapter."
- **Mark Resolved**:
  - Update status to `Resolved`.
  - Enter official resolution details: "Replaced VGA cable and recrimped CAT-6 Ethernet jack. System fully operational."
  - Submit update.

### 5. In-App Notifications & Student Feedback
- Log back in as student `priya.cs24@campus.edu`.
- Observe the red unread notification pill on the navigation sidebar.
- Visit `student/notifications.php`.
- Notice the notifications generated for each status milestone (`Submitted`, `Acknowledged`, `In Progress`, `Resolved`).
- Open `student/complaint_details.php?id=1`.
- Because the issue is now `Resolved`, the **Resolution Feedback Form** is enabled.
- Select **5 Stars** and leave written feedback: "Fixed within 2 hours, excellent turnaround time!"
- Submit feedback.
- Confirm that duplicate feedback is rejected and feedback appears in `student/feedback.php`.

---

## 6. Troubleshooting Common XAMPP / PHP / Apache Errors

| Symptom / Error | Root Cause | Solution |
| :--- | :--- | :--- |
| **Apache won't start (Port 80/443 in use)** | IIS, Skype, or VMware Workstation occupies port 80/443. | In XAMPP Control Panel, click **Config** &rarr; `httpd.conf` and change `Listen 80` to `Listen 8080`. Then access via `http://localhost:8080/campusfix/`. |
| **MySQL shutdown unexpectedly (Port 3306)** | Another MySQL service (standalone MySQL) is running. | Open Windows Services (`services.msc`), stop `MySQL` or `MySQL80`, and restart MySQL in XAMPP. |
| **Database connection failed (500 Error)** | Wrong credentials or database not created. | Verify database `campusfix` exists in phpMyAdmin. Check `includes/config.php` for correct `DB_USER` (`root`) and `DB_PASS` (empty by default). |
| **Uploads failing / Permission Denied** | Missing write permissions on `uploads/` directory. | Ensure `uploads/` folder exists at `C:\xampp\htdocs\campusfix\uploads` and has write permissions. |
| **Blank White Screen (WSOD)** | PHP syntax error with `display_errors` disabled in `php.ini`. | Open `C:\xampp\php\php.ini`, set `display_errors = On`, and restart Apache. |

---

## 7. Security & Viva / Academic Architecture Notes

For your second-year mini project viva examination, be prepared to highlight the following architectural decisions:

1. **Defense in Depth**: We do not rely merely on client-side HTML `required` attributes or CSS button hiding. Every action is checked server-side for role, identity, CSRF token, and input integrity.
2. **Prepared Statements**: All SQL queries use PDO parameter binding (`?` placeholders), preventing SQL injection vulnerabilities.
3. **Session Hardening**: Sessions use `HttpOnly`, `SameSite=Lax`, strict lifetime timeouts, and ID regeneration upon privilege change (`session_regenerate_id(true)`).
4. **Finite State Machine**: Complaint transitions strictly follow a state chart defined in `includes/config.php` (`STATUS_TRANSITIONS`). Students cannot change statuses, and admins cannot skip to invalid states.
5. **Localhost vs. Production**: Localhost runs over HTTP without TLS. For real public internet deployment, enforce HTTPS, configure a Content Security Policy (CSP), disable directory indexes, and host uploaded media on a secured private object store.

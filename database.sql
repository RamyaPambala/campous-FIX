-- ============================================================
-- CampusFix — Smart Campus Facility Monitoring & Issue Resolution
-- Database Schema  ·  MySQL / MariaDB
-- ============================================================
-- Usage:
--   1. CREATE DATABASE campusfix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. USE campusfix;
--   3. SOURCE /path/to/database.sql;
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- USERS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `full_name`         VARCHAR(120)    NOT NULL,
    `enrollment_number` VARCHAR(30)     NOT NULL,
    `email`             VARCHAR(180)    NOT NULL,
    `department`        VARCHAR(80)     NOT NULL,
    `year_of_study`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `section`           VARCHAR(10)     DEFAULT NULL,
    `password_hash`     VARCHAR(255)    NOT NULL,
    `role`              ENUM('student','admin') NOT NULL DEFAULT 'student',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email`             (`email`),
    UNIQUE KEY `uq_enrollment_number` (`enrollment_number`),
    KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- COMPLAINTS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `reference_number`  VARCHAR(20)     NOT NULL,
    `user_id`           INT UNSIGNED    NOT NULL,
    `title`             VARCHAR(200)    NOT NULL,
    `description`       TEXT            NOT NULL,
    `category`          ENUM(
                            'Laboratory and Computers',
                            'Internet and Wi-Fi',
                            'Water Supply',
                            'Washroom Maintenance',
                            'Canteen Cleanliness',
                            'Electrical Issues',
                            'Classroom Facilities',
                            'Drinking Water',
                            'Other'
                        ) NOT NULL,
    `building`          VARCHAR(100)    NOT NULL,
    `location_details`  VARCHAR(200)    DEFAULT NULL,
    `priority`          ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    `status`            ENUM('Submitted','Acknowledged','In Progress','Resolved','Closed','Reopened')
                            NOT NULL DEFAULT 'Submitted',
    `attachment_path`   VARCHAR(300)    DEFAULT NULL,
    `resolution_comment` TEXT           DEFAULT NULL,
    `assigned_to`       VARCHAR(120)    DEFAULT NULL,
    `resolved_at`       DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reference` (`reference_number`),
    KEY `idx_user`     (`user_id`),
    KEY `idx_status`   (`status`),
    KEY `idx_category` (`category`),
    KEY `idx_priority` (`priority`),
    KEY `idx_created`  (`created_at`),
    CONSTRAINT `fk_complaint_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- COMPLAINT STATUS HISTORY
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaint_status_history` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `complaint_id`  INT UNSIGNED    NOT NULL,
    `changed_by`    INT UNSIGNED    NOT NULL,
    `old_status`    VARCHAR(30)     DEFAULT NULL,
    `new_status`    VARCHAR(30)     NOT NULL,
    `comment`       TEXT            DEFAULT NULL,
    `visibility`    ENUM('public','internal') NOT NULL DEFAULT 'public',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_history_complaint` (`complaint_id`),
    KEY `idx_history_created`   (`created_at`),
    CONSTRAINT `fk_history_complaint`
        FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_history_user`
        FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- FEEDBACK
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `complaint_id`  INT UNSIGNED    NOT NULL,
    `student_id`    INT UNSIGNED    NOT NULL,
    `rating`        TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment`       TEXT            DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_feedback_complaint` (`complaint_id`),
    KEY `idx_feedback_student` (`student_id`),
    CONSTRAINT `fk_feedback_complaint`
        FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_feedback_student`
        FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- NOTIFICATIONS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `complaint_id`  INT UNSIGNED    DEFAULT NULL,
    `title`         VARCHAR(200)    NOT NULL,
    `message`       TEXT            NOT NULL,
    `is_read`       TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user`    (`user_id`, `is_read`),
    KEY `idx_notif_created` (`created_at`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notif_complaint`
        FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- RATE LIMITING (login attempts)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ip_address`    VARCHAR(45)     NOT NULL,
    `email`         VARCHAR(180)    NOT NULL,
    `attempted_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_attempts` (`ip_address`, `email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- End of Schema
-- ============================================================

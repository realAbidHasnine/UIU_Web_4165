-- =====================================================================
-- SkillMatch — Phase 3 schema extension
-- Adds the milestone / escrow / dispute / review / approval domain that
-- the existing frontend pages already reference but had no tables for.
--
-- Existing tables are NOT dropped. Only additive ALTERs.
-- Engine: InnoDB, utf8mb4. Safe to re-run (IF NOT EXISTS guarded).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. project_milestones
--    Implied by: work-approval.html (Milestone Amount / Escrow Fee /
--    Total to Release), payment-released.html (Posted -> Submitted ->
--    Approved -> Paid), billing-payments.html (Milestone 1/2/3).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_milestones` (
  `id`              VARCHAR(50)  NOT NULL,
  `job_id`          VARCHAR(50)  NOT NULL,
  `client_id`       VARCHAR(50)  NOT NULL,
  `freelancer_id`   VARCHAR(50)  DEFAULT NULL,
  `label`           VARCHAR(100) NOT NULL DEFAULT 'Milestone 1',
  `description`     TEXT         DEFAULT NULL,
  `amount`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `escrow_fee`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`          ENUM('Posted','Submitted','Approved','Paid','Disputed') NOT NULL DEFAULT 'Posted',
  `due_date`        DATE         DEFAULT NULL,
  `submitted_at`    DATETIME     DEFAULT NULL,
  `approved_at`     DATETIME     DEFAULT NULL,
  `paid_at`         DATETIME     DEFAULT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ms_job` (`job_id`),
  KEY `idx_ms_client` (`client_id`),
  KEY `idx_ms_freelancer` (`freelancer_id`),
  KEY `idx_ms_status` (`status`),
  CONSTRAINT `fk_ms_job`       FOREIGN KEY (`job_id`)        REFERENCES `jobs`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_ms_client`    FOREIGN KEY (`client_id`)     REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ms_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. payments  (the billing ledger)
--    Implied by: billing-payments.html — receipt SM-YYYY-NNNN,
--    "Released" vs "Held in escrow", 3% escrow fee per milestone.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`            VARCHAR(50)   NOT NULL,
  `milestone_id`  VARCHAR(50)   NOT NULL,
  `job_id`        VARCHAR(50)   NOT NULL,
  `client_id`     VARCHAR(50)   NOT NULL,
  `freelancer_id` VARCHAR(50)   DEFAULT NULL,
  `amount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `escrow_fee`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`        ENUM('Held in escrow','Released','Refunded') NOT NULL DEFAULT 'Held in escrow',
  `receipt_id`    VARCHAR(30)   DEFAULT NULL,
  `released_at`   DATETIME      DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pay_receipt` (`receipt_id`),
  KEY `idx_pay_milestone` (`milestone_id`),
  KEY `idx_pay_client` (`client_id`),
  KEY `idx_pay_status` (`status`),
  KEY `idx_pay_created` (`created_at`),
  CONSTRAINT `fk_pay_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_job`       FOREIGN KEY (`job_id`)       REFERENCES `jobs`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_client`    FOREIGN KEY (`client_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. reviews
--    Implied by: rate-freelancer.html — Communication / Quality of Work
--    / Timeliness (1-5) + free-text feedback; payment-released.html
--    "Overall Satisfaction" comment.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id`            VARCHAR(50) NOT NULL,
  `job_id`        VARCHAR(50) DEFAULT NULL,
  `milestone_id`  VARCHAR(50) DEFAULT NULL,
  `client_id`     VARCHAR(50) NOT NULL,
  `freelancer_id` VARCHAR(50) NOT NULL,
  `communication` TINYINT NOT NULL DEFAULT 5,
  `quality`       TINYINT NOT NULL DEFAULT 5,
  `timeliness`    TINYINT NOT NULL DEFAULT 5,
  `overall`       DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  `comment`       TEXT        DEFAULT NULL,
  `satisfaction_comment` TEXT DEFAULT NULL,
  `created_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_milestone` (`milestone_id`),
  KEY `idx_rev_freelancer` (`freelancer_id`),
  KEY `idx_rev_client` (`client_id`),
  CONSTRAINT `fk_rev_job`        FOREIGN KEY (`job_id`)        REFERENCES `jobs`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rev_milestone`  FOREIGN KEY (`milestone_id`)  REFERENCES `project_milestones`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_client`     FOREIGN KEY (`client_id`)     REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. disputes
--    Implied by: raise-dispute.html (reason / outcome radios, partial
--    refund amount) + dispute-status.html (DIS-YYYY-NNNN, "Under review",
--    3-day freelancer response window, case trace).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disputes` (
  `id`                    VARCHAR(30) NOT NULL,
  `milestone_id`          VARCHAR(50) NOT NULL,
  `job_id`                VARCHAR(50) NOT NULL,
  `client_id`             VARCHAR(50) NOT NULL,
  `freelancer_id`         VARCHAR(50) DEFAULT NULL,
  `reason`                ENUM('Work quality','Missed deadline','Communication breakdown','Other') NOT NULL,
  `description`           TEXT NOT NULL,
  `desired_outcome`       ENUM('Request a revision','Partial refund','Full refund') NOT NULL,
  `partial_refund_amount` DECIMAL(10,2) DEFAULT NULL,
  `status`                ENUM('Under review','Resolved','Withdrawn','Escalated') NOT NULL DEFAULT 'Under review',
  `freelancer_response`   TEXT DEFAULT NULL,
  `responded_at`          DATETIME DEFAULT NULL,
  `resolution`            TEXT DEFAULT NULL,
  `response_due_at`       DATETIME DEFAULT NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`           DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dispute_milestone` (`milestone_id`),
  KEY `idx_dis_client` (`client_id`),
  KEY `idx_dis_freelancer` (`freelancer_id`),
  KEY `idx_dis_status` (`status`),
  CONSTRAINT `fk_dis_milestone`  FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dis_job`        FOREIGN KEY (`job_id`)       REFERENCES `jobs`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dis_client`     FOREIGN KEY (`client_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dis_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. approvals
--    Implied by: Admin/html/freelancer-approvals.html and
--    client-approvals.html — "Pending Review", portfolio/GitHub links,
--    submitted date, Approve / Reject.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `approvals` (
  `id`            VARCHAR(50) NOT NULL,
  `user_id`       VARCHAR(50) NOT NULL,
  `type`          ENUM('FREELANCER','CLIENT') NOT NULL,
  `status`        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `portfolio_url` VARCHAR(255) DEFAULT NULL,
  `github_url`    VARCHAR(255) DEFAULT NULL,
  `skills`        TEXT DEFAULT NULL,
  `reject_reason` VARCHAR(255) DEFAULT NULL,
  `reviewed_by`   VARCHAR(50) DEFAULT NULL,
  `reviewed_at`   DATETIME DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_approval_user` (`user_id`),
  KEY `idx_appr_type_status` (`type`,`status`),
  CONSTRAINT `fk_appr_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appr_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. reports
--    Implied by: Admin/html/reports-generator.html — report_type
--    financial|user-growth|fraud-log, date_from/date_to, generated
--    filename history.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id`           VARCHAR(50) NOT NULL,
  `report_type`  ENUM('financial','user-growth','fraud-log') NOT NULL,
  `date_from`    DATE DEFAULT NULL,
  `date_to`      DATE DEFAULT NULL,
  `filename`     VARCHAR(255) NOT NULL,
  `generated_by` VARCHAR(50) DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rep_type` (`report_type`),
  KEY `idx_rep_created` (`created_at`),
  CONSTRAINT `fk_rep_user` FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. notifications
--    Implied by: notification-dropdown-panel.html — unread rows,
--    "Mark all as read", "View all activity".
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT AUTO_INCREMENT NOT NULL,
  `user_id`    VARCHAR(50) NOT NULL,
  `type`       VARCHAR(40) NOT NULL DEFAULT 'info',
  `title`      VARCHAR(150) NOT NULL,
  `body`       VARCHAR(255) DEFAULT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Additive ALTERs to existing tables
--    (MariaDB has no "ADD COLUMN IF NOT EXISTS" before 10.0? — it does
--     from 10.0.2, so guarded statements are safe on 10.4.)
-- ---------------------------------------------------------------------

-- skill-category-manager.html: slug + subcategories + is_active toggle
ALTER TABLE `skill_categories` ADD COLUMN IF NOT EXISTS `slug`        VARCHAR(80)  DEFAULT NULL;
ALTER TABLE `skill_categories` ADD COLUMN IF NOT EXISTS `subcategories` TEXT        DEFAULT NULL;
ALTER TABLE `skill_categories` ADD COLUMN IF NOT EXISTS `is_active`   TINYINT(1)   NOT NULL DEFAULT 1;

-- question-bank-manager.html: Type + Difficulty pills
ALTER TABLE `skill_questions` ADD COLUMN IF NOT EXISTS `qtype`      ENUM('Multiple Choice','Coding') NOT NULL DEFAULT 'Multiple Choice';
ALTER TABLE `skill_questions` ADD COLUMN IF NOT EXISTS `difficulty` ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium';

-- deliverables: tie an upload to a milestone, allow real stored files
ALTER TABLE `deliverables` ADD COLUMN IF NOT EXISTS `milestone_id` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `deliverables` ADD COLUMN IF NOT EXISTS `file_name`    VARCHAR(255) DEFAULT NULL;
ALTER TABLE `deliverables` ADD COLUMN IF NOT EXISTS `repo_url`     VARCHAR(255) DEFAULT NULL;
ALTER TABLE `deliverables` ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME DEFAULT NULL;

-- jobs: a job needs an assigned freelancer for milestone flows
ALTER TABLE `jobs` ADD COLUMN IF NOT EXISTS `hired_freelancer_id` VARCHAR(50) DEFAULT NULL;

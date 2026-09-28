-- =============================================================================
-- SkillMatch Platform Database Schema & Seed Data
-- Database: skillmatch_db
-- Target: MySQL 5.7+ / MariaDB 10.4+ (XAMPP phpMyAdmin)
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- All passwords are secure bcrypt hashes (password123 / admin123). No plaintext.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `skillmatch_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `skillmatch_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Table: users
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('FREELANCER','CLIENT','ADMIN') NOT NULL DEFAULT 'FREELANCER',
  `name` varchar(100) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `bio` text DEFAULT NULL,
  `status` enum('Active','Suspended','Flagged') NOT NULL DEFAULT 'Active',
  `score` int(11) DEFAULT 90,
  `rating` decimal(3,2) DEFAULT 5.00,
  `earnings` decimal(12,2) DEFAULT 0.00,
  `completed_jobs` int(11) DEFAULT 0,
  `location` varchar(100) DEFAULT 'Remote',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: sessions
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `token` varchar(64) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: user_skills
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: skill_categories
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `skill_categories` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(20) NOT NULL,
  `question_count` int(11) NOT NULL DEFAULT 5,
  `difficulty` varchar(100) NOT NULL DEFAULT 'Intermediate',
  `slug` varchar(80) DEFAULT NULL,
  `subcategories` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: skill_questions
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `skill_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `question` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_index` int(11) NOT NULL,
  `qtype` enum('Multiple Choice','Coding') NOT NULL DEFAULT 'Multiple Choice',
  `difficulty` enum('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium',
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: test_results
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `test_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `freelancer_id` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `score` int(11) NOT NULL,
  `passed` tinyint(1) NOT NULL,
  `correct_count` int(11) NOT NULL,
  `total_count` int(11) NOT NULL,
  `verified_badge` varchar(50) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `freelancer_id` (`freelancer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: jobs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` varchar(50) NOT NULL,
  `client_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'web',
  `budget_type` enum('fixed','hourly') NOT NULL DEFAULT 'fixed',
  `budget` varchar(50) NOT NULL DEFAULT '3000',
  `budget_display` varchar(100) NOT NULL DEFAULT '$3,000 Fixed',
  `duration` varchar(50) NOT NULL DEFAULT '1-3-months',
  `duration_display` varchar(100) NOT NULL DEFAULT '1-3 Months',
  `level` varchar(50) NOT NULL DEFAULT 'Intermediate',
  `description` text NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `location` varchar(100) DEFAULT 'Remote',
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `responsibilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responsibilities`)),
  `status` enum('Open','In Progress','Completed','Awaiting Approval') NOT NULL DEFAULT 'Open',
  `posted_time` varchar(100) DEFAULT 'Just now',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hired_freelancer_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: proposals
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proposals` (
  `id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `proposed_rate` decimal(10,2) NOT NULL,
  `estimated_days` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('Submitted','Active','Accepted','Archived') NOT NULL DEFAULT 'Submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `freelancer_id` (`freelancer_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: deliverables
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `deliverables` (
  `id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `file_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`file_paths`)),
  `status` varchar(50) DEFAULT 'Submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `milestone_id` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `repo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: portfolio_items
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portfolio_items` (
  `id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT '#',
  `image` varchar(255) DEFAULT '../assets/images/portfolio-1.png',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `freelancer_id` (`freelancer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: chat_threads
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_threads` (
  `id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `last_message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'online',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: chat_messages
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` varchar(50) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `sender_role` varchar(50) NOT NULL,
  `text` text NOT NULL,
  `is_me` tinyint(1) DEFAULT 0,
  `sent_time` varchar(50) DEFAULT 'Just now',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: project_milestones
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_milestones` (
  `id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) DEFAULT NULL,
  `label` varchar(100) NOT NULL DEFAULT 'Milestone 1',
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `escrow_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Posted','Submitted','Approved','Paid','Disputed') NOT NULL DEFAULT 'Posted',
  `due_date` date DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ms_job` (`job_id`),
  KEY `idx_ms_client` (`client_id`),
  KEY `idx_ms_freelancer` (`freelancer_id`),
  KEY `idx_ms_status` (`status`),
  CONSTRAINT `fk_ms_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ms_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ms_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: payments
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` varchar(50) NOT NULL,
  `milestone_id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `escrow_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Held in escrow','Released','Refunded') NOT NULL DEFAULT 'Held in escrow',
  `receipt_id` varchar(30) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pay_receipt` (`receipt_id`),
  KEY `idx_pay_milestone` (`milestone_id`),
  KEY `idx_pay_client` (`client_id`),
  KEY `idx_pay_status` (`status`),
  KEY `idx_pay_created` (`created_at`),
  KEY `fk_pay_job` (`job_id`),
  CONSTRAINT `fk_pay_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: reviews
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` varchar(50) NOT NULL,
  `job_id` varchar(50) DEFAULT NULL,
  `milestone_id` varchar(50) DEFAULT NULL,
  `client_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `communication` tinyint(4) NOT NULL DEFAULT 5,
  `quality` tinyint(4) NOT NULL DEFAULT 5,
  `timeliness` tinyint(4) NOT NULL DEFAULT 5,
  `overall` decimal(3,2) NOT NULL DEFAULT 5.00,
  `comment` text DEFAULT NULL,
  `satisfaction_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_milestone` (`milestone_id`),
  KEY `idx_rev_freelancer` (`freelancer_id`),
  KEY `idx_rev_client` (`client_id`),
  KEY `fk_rev_job` (`job_id`),
  CONSTRAINT `fk_rev_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rev_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: disputes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disputes` (
  `id` varchar(30) NOT NULL,
  `milestone_id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) DEFAULT NULL,
  `reason` enum('Work quality','Missed deadline','Communication breakdown','Other') NOT NULL,
  `description` text NOT NULL,
  `desired_outcome` enum('Request a revision','Partial refund','Full refund') NOT NULL,
  `partial_refund_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Under review','Resolved','Withdrawn','Escalated') NOT NULL DEFAULT 'Under review',
  `freelancer_response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `response_due_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dispute_milestone` (`milestone_id`),
  KEY `idx_dis_client` (`client_id`),
  KEY `idx_dis_freelancer` (`freelancer_id`),
  KEY `idx_dis_status` (`status`),
  KEY `fk_dis_job` (`job_id`),
  CONSTRAINT `fk_dis_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dis_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dis_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dis_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: notifications
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'info',
  `title` varchar(150) NOT NULL,
  `body` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: approvals
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `approvals` (
  `id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `type` enum('FREELANCER','CLIENT') NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `portfolio_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `reviewed_by` varchar(50) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_approval_user` (`user_id`),
  KEY `idx_appr_type_status` (`type`,`status`),
  KEY `fk_appr_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_appr_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_appr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: reports
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id` varchar(50) NOT NULL,
  `report_type` enum('financial','user-growth','fraud-log') NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `generated_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rep_type` (`report_type`),
  KEY `idx_rep_created` (`created_at`),
  KEY `fk_rep_user` (`generated_by`),
  CONSTRAINT `fk_rep_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Seed users (26 rows)
INSERT INTO `users` (`id`, `email`, `password`, `role`, `name`, `title`, `company`, `hourly_rate`, `bio`, `status`, `score`, `rating`, `earnings`, `completed_jobs`, `location`, `created_at`) VALUES
  ('c-201', 'abid.hasina@flow.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'CLIENT', 'Abida Hasan', 'Design Director', 'Apex Capital Partners', '0.00', 'Managing product engineering pipelines and hiring top-tier technical contractors.', 'Active', '90', '4.90', '0.00', '8', 'London, UK', '2026-09-26 20:42:00'),
  ('c-202', 'david.miller@nexacorp.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'CLIENT', 'David Miller', 'CTO', 'NexaCorp Technologies', '0.00', 'Building next-gen enterprise SaaS solutions and looking for top-tier freelance engineering talent to accelerate our roadmap.', 'Active', '90', '4.90', '0.00', '12', 'New York, USA', '2026-09-26 21:13:40'),
  ('c-203', 'elena.rostova@novapay.io', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'CLIENT', 'Elena Rostova', 'Head of Product', 'NovaPay Global', '0.00', 'Fintech product leader looking for UI/UX and mobile specialists to build our next-gen payment experience.', 'Active', '88', '4.80', '0.00', '7', 'London, UK', '2026-09-26 21:13:40'),
  ('c-204', 'marcus.vance@stridehealth.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'CLIENT', 'Marcus Vance', 'CEO', 'Stride HealthTech', '0.00', 'Building digital health products that improve patient outcomes. Hiring engineers and designers passionate about healthcare tech.', 'Active', '85', '4.70', '0.00', '5', 'San Francisco, USA', '2026-09-26 21:13:40'),
  ('c-205', 'sophia.lee@luxeretail.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'CLIENT', 'Sophia Lee', 'VP Engineering', 'Luxe Retail Group', '0.00', 'Scaling our headless e-commerce infrastructure. Need backend, frontend, and DevOps specialists with high-traffic platform experience.', 'Active', '92', '4.95', '0.00', '15', 'Singapore', '2026-09-26 21:13:40'),
  ('c-210', 'ayesha.siddiqua@example.com', '$2y$10$kzXL9Z2xNxEhNBG.IOfVo.Xc4f.tNBixFjTHx0oq0i.RIvKNNhH0K', 'CLIENT', 'Ayesha Siddiqua', 'Head of Digital', 'Northwind Retail', '0.00', 'Leading digital products for a 40-store retail group.', 'Suspended', '72', '0.00', '0.00', '0', 'Dubai, UAE', '2026-09-26 22:18:24'),
  ('f-101', 'sarah.jenkins@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Sarah Jenkins', 'Senior Frontend & React Specialist', NULL, '65.00', 'Over 6 years of experience building modern, responsive, and performance-critical web applications with React, TypeScript, and modern CSS.', 'Active', '94', '4.95', '28450.00', '34', 'Remote (US/EU)', '2026-09-26 20:42:00'),
  ('fl-1', 'abid.hasnine@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Abid Hasnine', 'Full Stack Engineer & Cloud Architect', NULL, '75.00', 'Full stack web specialist specializing in high-performance cloud architectures, REST APIs, and microservices.', 'Active', '96', '5.00', '42000.00', '48', 'Remote', '2026-09-26 20:42:00'),
  ('fl-10', 'priya.nair@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Priya Nair', 'React Native Developer', NULL, '65.00', 'Cross-platform mobile developer with 4 years of React Native experience. Built apps for logistics, fintech, and healthcare sectors.', 'Active', '88', '4.78', '14500.00', '21', 'Remote', '2026-09-26 21:13:40'),
  ('fl-11', 'lukas.bergmann@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Lukas Bergmann', 'Blockchain & Smart Contract Developer', NULL, '90.00', 'Solidity and Web3.js expert. Built DeFi protocols, NFT minting platforms, and token vesting contracts on Ethereum and Polygon.', 'Active', '92', '4.90', '31000.00', '19', 'Remote', '2026-09-26 21:13:40'),
  ('fl-12', 'fatima.al-rashid@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Fatima Al-Rashid', 'Cybersecurity Analyst', NULL, '85.00', 'Penetration tester and security consultant. OSCP certified. Conducts web app security audits, red team exercises, and compliance reviews.', 'Active', '96', '4.98', '44000.00', '35', 'Remote', '2026-09-26 21:13:40'),
  ('fl-13', 'seun.adeleke@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Seun Adeleke', 'Technical Writer & Developer Advocate', NULL, '50.00', 'Writes API documentation, SDK guides, and developer tutorials for SaaS companies. Expert in OpenAPI, Swagger, and Postman.', 'Active', '87', '4.75', '9800.00', '33', 'Remote', '2026-09-26 21:13:40'),
  ('fl-14', 'maya.johnson@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Maya Johnson', 'Senior QA Automation Engineer', NULL, '62.00', 'Test automation architect using Selenium, Playwright, and Cypress. Builds robust CI-integrated test suites for agile teams.', 'Active', '91', '4.87', '17200.00', '29', 'Remote', '2026-09-26 21:13:40'),
  ('fl-2', 'sabbir.hossain@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Sabbir Hossain', 'Senior Mobile & UI/UX Designer', NULL, '60.00', 'Crafting human-centered UI/UX systems and high-converting interfaces across web and mobile platforms.', 'Active', '94', '4.90', '18500.00', '36', 'Remote', '2026-09-26 20:42:00'),
  ('fl-20', 'nina.okafor@example.com', '$2y$10$kzXL9Z2xNxEhNBG.IOfVo.Xc4f.tNBixFjTHx0oq0i.RIvKNNhH0K', 'FREELANCER', 'Nina Okafor', 'Product Designer', 'Independent', '48.00', 'Product designer with 6 years across fintech and health products.', 'Suspended', '88', '4.70', '0.00', '0', 'Lagos, Nigeria', '2026-09-26 22:18:24'),
  ('fl-21', 'rahim.chowdhury@example.com', '$2y$10$kzXL9Z2xNxEhNBG.IOfVo.Xc4f.tNBixFjTHx0oq0i.RIvKNNhH0K', 'FREELANCER', 'Rahim Chowdhury', 'Backend Engineer', 'Independent', '62.00', 'Node and Laravel specialist; previously led a payments platform rebuild.', 'Suspended', '91', '4.85', '0.00', '0', 'Dhaka, Bangladesh', '2026-09-26 22:18:24'),
  ('fl-22', 'marcus.bell@example.com', '$2y$10$kzXL9Z2xNxEhNBG.IOfVo.Xc4f.tNBixFjTHx0oq0i.RIvKNNhH0K', 'FREELANCER', 'Marcus Bell', 'React Engineer', 'Pixelcraft Studio', '74.00', 'React and TypeScript specialist, previously at a fintech scale-up.', 'Active', '97', '4.95', '0.00', '0', 'Austin, USA', '2026-09-26 22:18:24'),
  ('fl-23', 'lena.fischer@example.com', '$2y$10$kzXL9Z2xNxEhNBG.IOfVo.Xc4f.tNBixFjTHx0oq0i.RIvKNNhH0K', 'FREELANCER', 'Lena Fischer', 'Data Analyst', 'Independent', '39.00', 'Analyst focused on marketing attribution.', 'Suspended', '54', '3.20', '0.00', '0', 'Berlin, Germany', '2026-09-26 22:18:24'),
  ('fl-3', 'sadman.sakib@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Sadman Sakib', 'Data Scientist & ML Engineer', NULL, '85.00', 'Predictive analytics, natural language processing, and scalable data pipeline engineering.', 'Active', '99', '5.00', '52000.00', '210', 'Remote', '2026-09-26 20:42:00'),
  ('fl-4', 'nabila.islam@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Nabila Islam', 'Cloud & DevOps Engineer', NULL, '80.00', 'AWS-certified cloud infrastructure specialist with expertise in Kubernetes orchestration, CI/CD pipelines, and Infrastructure as Code with Terraform.', 'Active', '97', '4.95', '38000.00', '42', 'Remote', '2026-09-26 21:13:40')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

INSERT INTO `users` (`id`, `email`, `password`, `role`, `name`, `title`, `company`, `hourly_rate`, `bio`, `status`, `score`, `rating`, `earnings`, `completed_jobs`, `location`, `created_at`) VALUES
  ('fl-5', 'tanvir.ahmed@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Tanvir Ahmed', 'Mobile App Developer (Android/iOS)', NULL, '70.00', 'Native Android/iOS developer with 5+ years building finance and healthcare applications. Expert in Kotlin, Swift, and Flutter.', 'Active', '93', '4.85', '22000.00', '31', 'Remote', '2026-09-26 21:13:40'),
  ('fl-6', 'riya.sharma@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Riya Sharma', 'Backend API Architect', NULL, '72.00', 'Builds high-throughput REST and GraphQL APIs using Node.js, Express, and PostgreSQL. Specializes in event-driven microservices.', 'Active', '91', '4.88', '19500.00', '28', 'Remote', '2026-09-26 21:13:40'),
  ('fl-7', 'james.okonkwo@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'James Okonkwo', 'Full Stack Developer', NULL, '68.00', '7 years building end-to-end web applications with Vue.js, Django, and PostgreSQL for SaaS and e-commerce clients.', 'Active', '89', '4.80', '16800.00', '24', 'Remote', '2026-09-26 21:13:40'),
  ('fl-8', 'amara.diallo@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Amara Diallo', 'UI/UX Product Designer', NULL, '58.00', 'Human-centered designer specializing in B2B SaaS dashboard design, design system architecture, and usability research.', 'Active', '95', '4.92', '26000.00', '38', 'Remote', '2026-09-26 21:13:40'),
  ('fl-9', 'carlos.mendez@example.com', '$2y$10$cJ8/3bJpGESOzZ2/P4a6ZOSDHFMgJSm1XpTufF/KTIpd6oJLAY28q', 'FREELANCER', 'Carlos Mendez', 'Data Engineer & Python Specialist', NULL, '76.00', 'Designs and maintains production data pipelines using Apache Spark, Airflow, and dbt. Experience with BigQuery and Redshift.', 'Active', '90', '4.82', '21000.00', '27', 'Remote', '2026-09-26 21:13:40'),
  ('u-admin', 'admin@skillmatch.com', '$2y$10$xL8c2JR5MbWEQ.CxRZlmPOKmS9KuRDt7cAkBCL4AR4EINOt8H47wi', 'ADMIN', 'System Admin', 'Platform Operations Lead', 'SkillMatch Inc.', '0.00', 'Root administrator for platform monitoring, verification audit, and user accounts.', 'Active', '100', '5.00', '0.00', '0', 'Headquarters', '2026-09-26 20:42:00')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed user_skills (90 rows)
INSERT INTO `user_skills` (`id`, `user_id`, `skill_name`) VALUES
  ('7', 'fl-1', 'Spring Boot'),
  ('8', 'fl-1', 'React'),
  ('9', 'fl-1', 'Docker'),
  ('10', 'fl-1', 'PostgreSQL'),
  ('11', 'fl-1', 'AWS'),
  ('12', 'fl-1', 'Kubernetes'),
  ('13', 'fl-2', 'Figma'),
  ('14', 'fl-2', 'UI/UX Design'),
  ('15', 'fl-2', 'Mobile Design'),
  ('16', 'fl-2', 'Flutter'),
  ('17', 'fl-2', 'Prototyping'),
  ('18', 'fl-2', 'Design Systems'),
  ('19', 'fl-3', 'Python'),
  ('20', 'fl-3', 'Machine Learning'),
  ('21', 'fl-3', 'TensorFlow'),
  ('22', 'fl-3', 'Data Science'),
  ('23', 'fl-3', 'NLP'),
  ('24', 'fl-3', 'SQL'),
  ('25', 'fl-4', 'AWS'),
  ('26', 'fl-4', 'Kubernetes')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

INSERT INTO `user_skills` (`id`, `user_id`, `skill_name`) VALUES
  ('27', 'fl-4', 'Terraform'),
  ('28', 'fl-4', 'Docker'),
  ('29', 'fl-4', 'CI/CD'),
  ('30', 'fl-4', 'Linux'),
  ('31', 'fl-5', 'Kotlin'),
  ('32', 'fl-5', 'Swift'),
  ('33', 'fl-5', 'Flutter'),
  ('34', 'fl-5', 'Android'),
  ('35', 'fl-5', 'iOS'),
  ('36', 'fl-5', 'Firebase'),
  ('37', 'fl-6', 'Node.js'),
  ('38', 'fl-6', 'GraphQL'),
  ('39', 'fl-6', 'PostgreSQL'),
  ('40', 'fl-6', 'Redis'),
  ('41', 'fl-6', 'Express.js'),
  ('42', 'fl-7', 'Vue.js'),
  ('43', 'fl-7', 'Django'),
  ('44', 'fl-7', 'Python'),
  ('45', 'fl-7', 'PostgreSQL'),
  ('46', 'fl-7', 'REST API')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

INSERT INTO `user_skills` (`id`, `user_id`, `skill_name`) VALUES
  ('47', 'fl-8', 'Figma'),
  ('48', 'fl-8', 'UI/UX Design'),
  ('49', 'fl-8', 'Design Systems'),
  ('50', 'fl-8', 'User Research'),
  ('51', 'fl-8', 'Prototyping'),
  ('52', 'fl-9', 'Apache Spark'),
  ('53', 'fl-9', 'Airflow'),
  ('54', 'fl-9', 'Python'),
  ('55', 'fl-9', 'dbt'),
  ('56', 'fl-9', 'BigQuery'),
  ('57', 'fl-9', 'SQL'),
  ('58', 'fl-10', 'React Native'),
  ('59', 'fl-10', 'JavaScript'),
  ('60', 'fl-10', 'iOS'),
  ('61', 'fl-10', 'Android'),
  ('62', 'fl-10', 'Redux'),
  ('63', 'fl-11', 'Solidity'),
  ('64', 'fl-11', 'Web3.js'),
  ('65', 'fl-11', 'Ethereum'),
  ('66', 'fl-11', 'DeFi')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

INSERT INTO `user_skills` (`id`, `user_id`, `skill_name`) VALUES
  ('67', 'fl-11', 'Smart Contracts'),
  ('68', 'fl-12', 'Penetration Testing'),
  ('69', 'fl-12', 'OSCP'),
  ('70', 'fl-12', 'Cybersecurity'),
  ('71', 'fl-12', 'OWASP'),
  ('72', 'fl-12', 'Burp Suite'),
  ('73', 'fl-13', 'Technical Writing'),
  ('74', 'fl-13', 'OpenAPI'),
  ('75', 'fl-13', 'Postman'),
  ('76', 'fl-13', 'Markdown'),
  ('77', 'fl-13', 'REST APIs'),
  ('78', 'fl-14', 'Selenium'),
  ('79', 'fl-14', 'Playwright'),
  ('80', 'fl-14', 'Cypress'),
  ('81', 'fl-14', 'QA Automation'),
  ('82', 'fl-14', 'Pytest'),
  ('107', 'fl-20', 'Figma'),
  ('108', 'fl-20', 'Design Systems'),
  ('109', 'fl-20', 'Prototyping'),
  ('110', 'fl-21', 'Node.js')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

INSERT INTO `user_skills` (`id`, `user_id`, `skill_name`) VALUES
  ('111', 'fl-21', 'Laravel'),
  ('112', 'fl-21', 'PostgreSQL'),
  ('113', 'c-210', 'Project Management'),
  ('114', 'fl-22', 'React'),
  ('115', 'fl-22', 'TypeScript'),
  ('116', 'fl-22', 'Next.js'),
  ('117', 'fl-23', 'SQL'),
  ('118', 'fl-23', 'Tableau'),
  ('131', 'f-101', 'React'),
  ('132', 'f-101', 'TypeScript')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed skill_categories (4 rows)
INSERT INTO `skill_categories` (`id`, `name`, `icon`, `question_count`, `difficulty`, `slug`, `subcategories`, `is_active`) VALUES
  ('cloud', 'Cloud & DevOps', '☁️', '5', 'Advanced - Expert', 'cloud', NULL, '1'),
  ('mobile', 'Mobile App Development', '📱', '5', 'Intermediate - Advanced', 'mobile', NULL, '1'),
  ('uiux', 'UI / UX Design', '🎨', '5', 'All Levels', 'uiux', NULL, '1'),
  ('web', 'Web Development', '💻', '5', 'Intermediate - Expert', 'web', NULL, '1')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed skill_questions (20 rows)
INSERT INTO `skill_questions` (`id`, `category`, `question`, `options`, `correct_index`, `qtype`, `difficulty`) VALUES
  ('1', 'web', 'In modern JavaScript and asynchronous programming, what does Promise.all() do when one of the input promises rejects?', '[\"It waits for all other promises to resolve before returning an error\", \"It immediately rejects with the reason of the first promise that rejected\", \"It ignores the rejected promise and returns successful results\", \"It retries the rejected promise three times automatically\"]', '1', 'Multiple Choice', 'Medium'),
  ('2', 'web', 'Which HTTP method is idempotent and intended to completely replace an existing resource on a REST API?', '[\"POST\", \"PATCH\", \"PUT\", \"CONNECT\"]', '2', 'Multiple Choice', 'Medium'),
  ('3', 'web', 'What is the primary benefit of React Virtual DOM reconciliation (Diffing Algorithm)?', '[\"Bypassing CSS cascade calculations completely\", \"Minimizing costly native DOM layout recalculations and repaints\", \"Guaranteeing zero memory consumption during animation loops\", \"Converting JSX syntax directly into native machine assembly\"]', '1', 'Multiple Choice', 'Medium'),
  ('4', 'web', 'In database systems, what does the \"I\" in the ACID guarantee stand for?', '[\"Integrity\", \"Isolation\", \"Indexation\", \"Idempotence\"]', '1', 'Multiple Choice', 'Medium'),
  ('5', 'web', 'In web applications, what HTTP header protects against Cross-Site Scripting (XSS) by restricting where scripts can execute from?', '[\"Access-Control-Allow-Origin\", \"Content-Security-Policy\", \"X-Content-Type-Options\", \"Strict-Transport-Security\"]', '1', 'Multiple Choice', 'Medium'),
  ('6', 'uiux', 'In design systems, what is the purpose of a \"token\" (design token)?', '[\"A placeholder component used in wireframes\",\"A named variable that stores a design decision like color, spacing, or typography\",\"A type of user interaction event\",\"A password used for Figma API access\"]', '1', 'Multiple Choice', 'Medium'),
  ('7', 'uiux', 'Which UX research method is most appropriate for discovering users\' mental models and unexplored pain points?', '[\"A/B Testing\",\"Moderated usability testing\",\"Contextual inquiry / ethnographic observation\",\"Card sorting\"]', '2', 'Multiple Choice', 'Medium'),
  ('8', 'uiux', 'In Fitts\' Law applied to UI design, which factor most directly determines the time required to acquire a target?', '[\"The color contrast ratio of the button\",\"The distance to the target and its size\",\"The font weight of the button label\",\"The number of items in the navigation\"]', '1', 'Multiple Choice', 'Medium'),
  ('9', 'uiux', 'What does WCAG 2.1 Level AA require for the contrast ratio of normal text against its background?', '[\"At least 2.5:1\",\"At least 4.5:1\",\"At least 7:1\",\"At least 3:1\"]', '1', 'Multiple Choice', 'Medium'),
  ('10', 'uiux', 'In atomic design methodology, what is the correct hierarchy from smallest to largest?', '[\"Atoms → Molecules → Organisms → Templates → Pages\",\"Molecules → Atoms → Organisms → Pages → Templates\",\"Pages → Templates → Organisms → Molecules → Atoms\",\"Atoms → Organisms → Molecules → Templates → Pages\"]', '0', 'Multiple Choice', 'Medium'),
  ('11', 'mobile', 'In Flutter, what is the fundamental difference between a StatelessWidget and a StatefulWidget?', '[\"StatelessWidget supports animations; StatefulWidget does not\",\"StatefulWidget can rebuild itself when its internal state changes; StatelessWidget cannot\",\"StatelessWidget is faster but does not support layout\",\"StatefulWidget can only be used in the root widget tree\"]', '1', 'Multiple Choice', 'Medium'),
  ('12', 'mobile', 'In iOS development, what is the primary purpose of the App Delegate?', '[\"Rendering UI components on screen\",\"Managing the app lifecycle events and entry point\",\"Handling push notifications only\",\"Storing persistent user preferences\"]', '1', 'Multiple Choice', 'Medium'),
  ('13', 'mobile', 'Which architectural pattern is recommended by Google for Android development using Jetpack components?', '[\"MVC (Model-View-Controller)\",\"MVVM (Model-View-ViewModel)\",\"MVP (Model-View-Presenter)\",\"VIPER\"]', '1', 'Multiple Choice', 'Medium'),
  ('14', 'mobile', 'What does React Native\'s \"Bridge\" primarily do?', '[\"Converts JSX to native Swift code at build time\",\"Enables communication between JavaScript and native platform threads\",\"Compiles JavaScript into ARM machine code\",\"Manages routing between screens\"]', '1', 'Multiple Choice', 'Medium'),
  ('15', 'mobile', 'In Android, what is the correct way to preserve UI state across configuration changes (e.g., screen rotation)?', '[\"Store state in a static variable\",\"Use a ViewModel from the Android Architecture Components\",\"Write state to SharedPreferences on every change\",\"Override onSaveInstanceState() only\"]', '1', 'Multiple Choice', 'Medium'),
  ('16', 'cloud', 'In Kubernetes, what is the purpose of a \"Deployment\" object?', '[\"To expose a set of pods as a network service\",\"To declare the desired state for a replicated application and manage rolling updates\",\"To configure persistent storage volumes\",\"To define security policies for pods\"]', '1', 'Multiple Choice', 'Medium'),
  ('17', 'cloud', 'Which AWS service is a serverless compute platform that runs code in response to events without provisioning servers?', '[\"Amazon EC2\",\"AWS Fargate\",\"AWS Lambda\",\"Amazon ECS\"]', '2', 'Multiple Choice', 'Medium'),
  ('18', 'cloud', 'What does the CAP theorem state about distributed systems?', '[\"A system can achieve Consistency, Availability, and Partition tolerance simultaneously\",\"A distributed system can guarantee at most two of: Consistency, Availability, and Partition tolerance\",\"Consistency and Availability are mutually exclusive in all cloud systems\",\"Partition tolerance can always be traded for performance\"]', '1', 'Multiple Choice', 'Medium'),
  ('19', 'cloud', 'In Docker, what is the difference between a Docker image and a Docker container?', '[\"They are the same thing with different names\",\"An image is a read-only blueprint; a container is a running instance of an image\",\"A container is stored on disk; an image runs in memory\",\"An image requires a host OS; a container does not\"]', '1', 'Multiple Choice', 'Medium'),
  ('20', 'cloud', 'What is the primary purpose of a CI/CD pipeline?', '[\"To manually test code before deployment\",\"To automate the build, test, and deployment lifecycle so changes can be delivered reliably and frequently\",\"To monitor production application performance\",\"To manage infrastructure provisioning\"]', '1', 'Multiple Choice', 'Medium')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed jobs (20 rows)
INSERT INTO `jobs` (`id`, `client_id`, `title`, `category`, `budget_type`, `budget`, `budget_display`, `duration`, `duration_display`, `level`, `description`, `company`, `location`, `skills`, `responsibilities`, `status`, `posted_time`, `created_at`, `hired_freelancer_id`) VALUES
  ('job-1', 'c-201', 'Modern Fintech Dashboard in React & Spring Boot', 'web', 'fixed', '3000-5000', '$3,000 - $5,000', '1-3-months', '1 to 3 Months', 'Expert', 'We are redesigning our enterprise wealth management dashboard. We require an experienced frontend developer who can build pixel-perfect interactive widgets, realtime chart components, and integrate smoothly with microservices.', 'Apex Capital Partners', 'Remote (US/EU)', '[\"React\", \"TypeScript\", \"Spring Boot\", \"REST API\", \"Chart.js\"]', '[\"Develop reusable React components\", \"Integrate balance and yield REST endpoints\", \"Optimize rendering for tables with 10k rows\"]', 'Open', '2 hours ago', '2026-09-26 20:42:00', NULL),
  ('job-10', 'c-203', 'Mobile Banking App — Flutter', 'mobile', 'fixed', '3000-5000', '$4,000 - $5,500', '1-3-months', '1 to 3 Months', 'Senior', 'Build a cross-platform Flutter banking app with biometric auth, transaction history, P2P transfers, and push notifications.', 'NovaPay Global', 'Remote', '[\"Flutter\",\"Dart\",\"Firebase\",\"Biometrics\",\"Push Notifications\",\"REST APIs\"]', '[\"Implement biometric login (FaceID/TouchID)\",\"Build real-time transaction feed\",\"Add P2P transfer with QR code scan\",\"Integrate Firebase Cloud Messaging\"]', 'Open', '2 days ago', '2026-09-26 21:13:40', NULL),
  ('job-11', 'c-204', 'Machine Learning — Patient Risk Scoring Model', 'data', 'hourly', 'under-1000', '$75 - $100 / hr', '1-3-months', '1 to 3 Months', 'Expert', 'Develop an ML model to predict 30-day hospital readmission risk from patient EHR data. Include explainability layer for clinicians.', 'Stride HealthTech', 'Remote', '[\"Python\",\"scikit-learn\",\"XGBoost\",\"SHAP\",\"FastAPI\",\"PostgreSQL\"]', '[\"Explore and preprocess EHR dataset\",\"Train and evaluate classification models\",\"Implement SHAP explainability\",\"Wrap model in FastAPI endpoint\"]', 'Open', '3 days ago', '2026-09-26 21:13:40', NULL),
  ('job-12', 'c-205', 'AWS Lambda Serverless API Refactor', 'cloud', 'hourly', 'under-1000', '$60 - $80 / hr', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Refactor 12 existing REST API endpoints from a monolith to AWS Lambda + API Gateway with proper IAM roles and DynamoDB.', 'Luxe Retail Group', 'Remote', '[\"AWS Lambda\",\"API Gateway\",\"DynamoDB\",\"IAM\",\"Serverless Framework\",\"Node.js\"]', '[\"Map existing endpoints to Lambda functions\",\"Implement API Gateway with custom authorizers\",\"Migrate data layer to DynamoDB\",\"Write integration tests\"]', 'Open', '4 days ago', '2026-09-26 21:13:40', NULL),
  ('job-13', 'c-201', 'Admin Dashboard — React + Spring Boot', 'web', 'fixed', '1000-3000', '$2,500 - $3,500', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Build an internal admin panel to manage user accounts, view analytics, and export CSV reports. Must integrate with existing Spring Boot APIs.', 'Apex Capital Partners', 'Remote', '[\"React\",\"TypeScript\",\"Chart.js\",\"Spring Boot\",\"REST API\",\"CSV Export\"]', '[\"Build user management CRUD tables\",\"Implement date-range analytics charts\",\"Add CSV export functionality\",\"Write unit tests for API integration\"]', 'Open', '5 days ago', '2026-09-26 21:13:40', NULL),
  ('job-14', 'c-202', 'iOS App — AI-Powered Fitness Coach', 'mobile', 'fixed', '3000-5000', '$5,000 - $7,000', '1-3-months', '1 to 3 Months', 'Expert', 'Build a native iOS fitness coaching app with CoreML workout detection, Apple Watch sync, and GPT-powered personalized workout plans.', 'NexaCorp Technologies', 'Remote', '[\"Swift\",\"SwiftUI\",\"CoreML\",\"HealthKit\",\"WatchKit\",\"OpenAI API\"]', '[\"Implement CoreML workout detection model\",\"Build HealthKit and Apple Watch sync\",\"Integrate OpenAI API for personalized plans\",\"Design SwiftUI interface with animations\"]', 'Open', '6 days ago', '2026-09-26 21:13:40', NULL),
  ('job-15', 'c-203', 'UX Research & Accessibility Audit', 'uiux', 'hourly', 'under-1000', '$50 - $70 / hr', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Conduct a comprehensive UX research study and accessibility audit of our 3 main user flows, with actionable WCAG 2.1 AA recommendations.', 'NovaPay Global', 'Remote', '[\"UX Research\",\"WCAG 2.1\",\"Accessibility\",\"Figma\",\"Usability Testing\",\"ARIA\"]', '[\"Conduct 8 moderated usability sessions\",\"Run automated accessibility scans\",\"Annotate WCAG violations in Figma\",\"Deliver prioritized recommendation report\"]', 'In Progress', '1 week ago', '2026-09-26 21:13:40', 'fl-1'),
  ('job-16', 'c-204', 'DevOps — GitHub Actions CI/CD Pipeline', 'cloud', 'fixed', 'under-1000', '$800 - $1,200', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Set up GitHub Actions CI/CD pipeline with automated testing, Docker image builds, staging deployment, and Slack notifications.', 'Stride HealthTech', 'Remote', '[\"GitHub Actions\",\"Docker\",\"AWS ECR\",\"Kubernetes\",\"Slack API\",\"Jest\"]', '[\"Write multi-stage GitHub Actions workflow\",\"Configure Docker build and push to ECR\",\"Set up kubectl deployment to staging\",\"Add Slack notification on deploy status\"]', 'Open', '1 week ago', '2026-09-26 21:13:40', NULL),
  ('job-17', 'c-205', 'E-Commerce SEO & Core Web Vitals Optimization', 'web', 'fixed', 'under-1000', '$1,500 - $2,500', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Audit and optimize our Next.js storefront for Core Web Vitals scores (LCP < 2.5s, FID < 100ms, CLS < 0.1) and on-page SEO.', 'Luxe Retail Group', 'Remote', '[\"Next.js\",\"Core Web Vitals\",\"SEO\",\"Image Optimization\",\"Lighthouse\",\"Structured Data\"]', '[\"Run Lighthouse and CrUX analysis\",\"Optimize image loading with next/image\",\"Implement structured data schema\",\"Fix cumulative layout shift issues\"]', 'Open', '1 week ago', '2026-09-26 21:13:40', NULL),
  ('job-18', 'c-201', 'Real-Time Chat System — WebSockets + React', 'web', 'fixed', '1000-3000', '$2,000 - $3,000', '1-3-months', '1 to 3 Months', 'Intermediate', 'Build a real-time chat module using WebSockets (Socket.io) and React for our freelancer marketplace platform.', 'Apex Capital Partners', 'Remote', '[\"React\",\"Socket.io\",\"Node.js\",\"Redis\",\"PostgreSQL\",\"JWT\"]', '[\"Implement Socket.io server with Redis pub/sub\",\"Build React chat UI with message history\",\"Add typing indicators and read receipts\",\"Implement JWT auth for WebSocket connections\"]', 'In Progress', '2 weeks ago', '2026-09-26 21:13:40', 'f-101'),
  ('job-19', 'c-202', 'API Security Audit & Penetration Test', 'cloud', 'hourly', '1000-3000', '$85 - $110 / hr', 'less-1-month', 'Less than 1 Month', 'Expert', 'Conduct a comprehensive black-box and grey-box penetration test on our REST API infrastructure, with detailed report and remediation guidance.', 'NexaCorp Technologies', 'Remote', '[\"Penetration Testing\",\"OWASP\",\"Burp Suite\",\"OSCP\",\"REST APIs\",\"Security Report\"]', '[\"Enumerate and fingerprint all API endpoints\",\"Test for OWASP Top 10 vulnerabilities\",\"Exploit and document all findings\",\"Deliver prioritized remediation report\"]', 'Open', '2 weeks ago', '2026-09-26 21:13:40', NULL),
  ('job-2', 'c-201', 'Mobile Banking App UI/UX Redesign System', 'uiux', 'hourly', 'under-1000', '$45 - $65 / hr', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Seeking a talented product designer to audit and revitalize our mobile retail banking user flows. You will create modern, high-converting Figma component libraries and interactive prototypes.', 'NovaPay Global', 'Remote', '[\"Figma\", \"Mobile Design\", \"Design Systems\", \"Prototyping\", \"iOS / Android\"]', '[\"Conduct usability review of existing money-transfer screens\", \"Build atomic design tokens in Figma\", \"Deliver specifications for engineering\"]', 'Open', '5 hours ago', '2026-09-26 20:42:00', NULL),
  ('job-20', 'c-203', 'Branding & Design — Mobile App Launch Kit', 'uiux', 'fixed', '1000-3000', '$1,800 - $2,500', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Design complete brand identity and app store launch kit: logo, color system, typography, App Store/Play Store screenshots, and onboarding screens.', 'NovaPay Global', 'Remote', '[\"Figma\",\"Branding\",\"Logo Design\",\"App Store Design\",\"Typography\",\"Illustration\"]', '[\"Design logo and brand color system\",\"Create 5 App Store screenshot sets\",\"Design 4-screen onboarding flow\",\"Export assets for development handoff\"]', 'Open', '3 weeks ago', '2026-09-26 21:13:40', NULL),
  ('job-3', 'c-201', 'Full-Stack Cross-Platform Mobile Flutter App', 'mobile', 'fixed', '1000-3000', '$2,500 - $3,500', '1-3-months', '1 to 3 Months', 'Senior', 'Build an iOS and Android fitness tracking companion app that syncs wearable sensor readings in real-time to a secure cloud backend.', 'Stride HealthTech', 'Remote', '[\"Flutter\", \"Dart\", \"WebSockets\", \"HealthKit\"]', '[\"Implement Bluetooth LE background syncing\", \"Design offline-first SQLite cache\", \"Connect biometric login\"]', 'Open', '1 day ago', '2026-09-26 20:42:00', NULL),
  ('job-4', 'c-201', 'Enterprise Headless E-Commerce Platform', 'web', 'fixed', '5000-plus', '$6,000 - $9,000', 'more-3-months', '3+ Months', 'Expert', 'High-volume international e-commerce redesign with headless CMS, sub-second product catalogue search, and automated inventory sync.', 'Luxe Retail Group', 'Remote (Worldwide)', '[\"Next.js\", \"PostgreSQL\", \"Stripe\", \"Redis\"]', '[\"Architect Next.js store frontend\", \"Integrate Stripe Payment Intents\", \"Implement Redis cache\"]', 'Open', '2 days ago', '2026-09-26 20:42:00', NULL),
  ('job-5', 'c-202', 'Cloud Infrastructure Migration to AWS EKS', 'cloud', 'fixed', '5000-plus', '$7,500 - $12,000', 'more-3-months', '3+ Months', 'Expert', 'Migrate our legacy VM-based microservices to AWS EKS with zero-downtime. Includes Terraform IaC, Helm charts, and multi-region failover.', 'NexaCorp Technologies', 'Remote (Worldwide)', '[\"AWS\",\"Kubernetes\",\"Terraform\",\"Helm\",\"Docker\",\"CI/CD\"]', '[\"Architect multi-region EKS cluster\",\"Write Terraform modules for VPC and EKS\",\"Set up Helm chart releases for all services\",\"Implement blue/green deployment strategy\"]', 'Open', '3 hours ago', '2026-09-26 21:13:40', NULL),
  ('job-6', 'c-203', 'Figma Design System & Mobile Prototype', 'uiux', 'hourly', 'under-1000', '$55 - $75 / hr', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Build a comprehensive Figma component library and interactive prototype for our mobile payment app. Must include dark mode variants and accessibility annotations.', 'NovaPay Global', 'Remote', '[\"Figma\",\"Mobile Design\",\"Design Systems\",\"Prototyping\",\"Accessibility\"]', '[\"Audit existing screen inventory\",\"Build atomic component library\",\"Create interactive prototype for 12 core flows\",\"Document accessibility annotations\"]', 'Open', '1 hour ago', '2026-09-26 21:13:40', NULL),
  ('job-7', 'c-204', 'Healthcare Patient Portal — React + Node.js', 'web', 'fixed', '3000-5000', '$4,500 - $6,000', '1-3-months', '1 to 3 Months', 'Intermediate', 'Build a HIPAA-compliant patient portal with appointment scheduling, lab results display, and video consultation integration.', 'Stride HealthTech', 'Remote (US Only)', '[\"React\",\"Node.js\",\"PostgreSQL\",\"HIPAA\",\"WebRTC\",\"JWT\"]', '[\"Implement secure auth with MFA\",\"Build appointment booking calendar\",\"Integrate lab results API\",\"Add video consultation module\"]', 'Open', '6 hours ago', '2026-09-26 21:13:40', NULL),
  ('job-8', 'c-205', 'Next.js Headless E-Commerce Storefront', 'web', 'fixed', '5000-plus', '$8,000 - $12,000', 'more-3-months', '3+ Months', 'Expert', 'Architect and build a high-performance headless Next.js storefront for 500K+ SKU catalogue. Sub-second search, Stripe integration, and CDN-optimized images.', 'Luxe Retail Group', 'Remote', '[\"Next.js\",\"TypeScript\",\"Stripe\",\"Redis\",\"Algolia\",\"CDN\"]', '[\"Implement ISR for product pages\",\"Integrate Algolia search with instant results\",\"Build Stripe checkout with 3DS support\",\"Set up Redis cache for cart sessions\"]', 'Open', '12 hours ago', '2026-09-26 21:13:40', NULL),
  ('job-9', 'c-202', 'Data Pipeline & Analytics Dashboard', 'data', 'fixed', '1000-3000', '$2,000 - $3,500', '1-3-months', '1 to 3 Months', 'Intermediate', 'Build an automated ETL pipeline from multiple CRM sources into a central data warehouse, with a React analytics dashboard.', 'NexaCorp Technologies', 'Remote', '[\"Python\",\"Apache Airflow\",\"dbt\",\"BigQuery\",\"React\",\"Chart.js\"]', '[\"Design star schema in BigQuery\",\"Build Airflow DAGs for nightly ETL\",\"Create dbt transformation models\",\"Build React dashboard with drill-down charts\"]', 'Open', '1 day ago', '2026-09-26 21:13:40', NULL)
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed proposals (15 rows)
INSERT INTO `proposals` (`id`, `job_id`, `freelancer_id`, `proposed_rate`, `estimated_days`, `cover_letter`, `status`, `submitted_at`) VALUES
  ('prop-1', 'job-1', 'f-101', '3500.00', '14', 'I have extensive experience building scalable financial dashboards with React and Chart.js. I have architected 6 enterprise portals with strict type safety.', 'Active', '2026-09-26 20:42:00'),
  ('prop-10', 'job-11', 'fl-3', '8500.00', '42', 'I have published research on clinical risk prediction models. I will use XGBoost with SHAP explainability and wrap it in a FastAPI endpoint for your clinical team.', 'Active', '2026-09-26 21:13:40'),
  ('prop-11', 'job-12', 'fl-4', '7200.00', '20', 'I have refactored 3 monolith APIs to AWS Lambda + API Gateway with DynamoDB. I follow least-privilege IAM principles and write comprehensive integration tests.', 'Submitted', '2026-09-26 21:13:40'),
  ('prop-12', 'job-14', 'fl-5', '6200.00', '50', 'Native iOS developer here. I have built CoreML-powered workout classification for a fitness startup and integrated Apple Watch sync using HealthKit.', 'Submitted', '2026-09-26 21:13:40'),
  ('prop-13', 'job-19', 'fl-12', '9500.00', '14', 'OSCP-certified penetration tester. I have conducted over 20 professional API security assessments. I deliver CVE-level findings with complete PoC code and remediation steps.', 'Active', '2026-09-26 21:13:40'),
  ('prop-15', 'job-15', 'fl-1', '550.00', '14', 'I run heuristic reviews and axe-core audits as part of every handover, with a written remediation backlog.', 'Accepted', '2026-08-25 13:05:00'),
  ('prop-18', 'job-18', 'f-101', '2400.00', '21', 'I have built realtime messaging twice before and would use Socket.IO with a Redis pub/sub layer so it scales past a single node.', 'Accepted', '2026-08-20 09:30:00'),
  ('prop-2', 'job-2', 'f-101', '55.00', '7', 'I can audit your current mobile screens and deliver a clean, componentized design system ready for Flutter.', 'Submitted', '2026-09-26 20:42:00'),
  ('prop-3', 'job-4', 'f-101', '7500.00', '30', 'Over 5 years of experience building high-traffic headless e-commerce storefronts with Next.js and secure payments.', 'Active', '2026-09-26 20:42:00'),
  ('prop-4', 'job-5', 'fl-4', '9500.00', '45', 'I am an AWS-certified DevOps engineer with 4 years of Kubernetes production experience. I have migrated 3 enterprise applications to EKS with zero downtime using blue/green deployments.', 'Submitted', '2026-09-26 21:13:40'),
  ('prop-5', 'job-6', 'fl-8', '65.00', '18', 'My design system work for fintech clients has been adopted by 4 engineering teams. I deliver WCAG AA-compliant component libraries with precise developer handoff documentation.', 'Active', '2026-09-26 21:13:40'),
  ('prop-6', 'job-7', 'fl-1', '5500.00', '35', 'I specialize in healthcare SaaS platforms and have built two HIPAA-compliant portals using React and Node.js with JWT auth and audit logging.', 'Submitted', '2026-09-26 21:13:40'),
  ('prop-7', 'job-8', 'fl-1', '10500.00', '75', 'I have architected two headless Next.js storefronts for high-SKU e-commerce. My implementation achieved 98 Lighthouse performance scores.', 'Submitted', '2026-09-26 21:13:40'),
  ('prop-8', 'job-9', 'fl-3', '3000.00', '30', 'My data engineering background includes building Airflow + dbt pipelines processing 50M+ events daily. I can have your BigQuery data model production-ready in 4 weeks.', 'Active', '2026-09-26 21:13:40'),
  ('prop-9', 'job-10', 'fl-5', '4800.00', '40', 'I built 2 banking apps in Flutter for European fintech clients. Both passed PCI DSS compliance reviews. I can integrate biometric auth and P2P transfers within 6 weeks.', 'Submitted', '2026-09-26 21:13:40')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed portfolio_items (9 rows)
INSERT INTO `portfolio_items` (`id`, `freelancer_id`, `title`, `category`, `url`, `image`, `description`, `created_at`) VALUES
  ('port-1', 'f-101', 'Fintech Real-Time Trading Terminal', 'Web Application', 'https://github.com/example/trading-ui', '../assets/images/portfolio-1.png', 'High-frequency charting dashboard with WebSockets and canvas rendering.', '2026-09-26 20:42:00'),
  ('port-2', 'f-101', 'Telehealth Clinical Workspace', 'Healthcare Portal', 'https://github.com/example/telehealth', '../assets/images/portfolio-1.png', 'HIPAA-compliant appointment manager, medical records visualizer, and video consultation.', '2026-09-26 20:42:00'),
  ('port-3', 'f-101', 'Global Logistics Freight Tracker', 'Enterprise SaaS', 'https://github.com/example/freight', '../assets/images/portfolio-1.png', 'Supply-chain map visualization with route optimization calculations.', '2026-09-26 20:42:00'),
  ('port-4', 'fl-1', 'Enterprise Cloud Migration for FinServ Client', 'Cloud Architecture', 'https://github.com/example/cloud-migration', '../assets/images/portfolio-1.png', 'Zero-downtime migration of 18-service monolith to AWS EKS using Terraform and ArgoCD GitOps.', '2026-09-26 21:13:40'),
  ('port-5', 'fl-2', 'NovaPay Mobile Banking Redesign', 'UI/UX Design', 'https://figma.com/example/novapay', '../assets/images/portfolio-1.png', 'Complete redesign of mobile banking flows with 40% improvement in task completion rate.', '2026-09-26 21:13:40'),
  ('port-6', 'fl-3', 'Patient Readmission ML Pipeline', 'Machine Learning', 'https://github.com/example/readmission-model', '../assets/images/portfolio-1.png', 'XGBoost model achieving AUC 0.89 for 30-day hospital readmission prediction.', '2026-09-26 21:13:40'),
  ('port-7', 'fl-4', 'Multi-Region EKS Deployment', 'DevOps / Cloud', 'https://github.com/example/eks-infra', '../assets/images/portfolio-1.png', 'Production-grade Terraform + Helm IaC for multi-region Kubernetes cluster.', '2026-09-26 21:13:40'),
  ('port-8', 'fl-8', 'B2B SaaS Design System', 'UI/UX Design', 'https://figma.com/example/design-system', '../assets/images/portfolio-1.png', 'Atomic design system with 200+ components, covering light, dark, and high-contrast modes.', '2026-09-26 21:13:40'),
  ('port-9', 'fl-12', 'REST API Security Assessment', 'Cybersecurity', 'https://github.com/example/pentest-report', '../assets/images/portfolio-1.png', 'Documented 14 OWASP Top 10 findings with PoC code and step-by-step remediation guidance.', '2026-09-26 21:13:40')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed chat_threads (6 rows)
INSERT INTO `chat_threads` (`id`, `client_id`, `freelancer_id`, `client_name`, `last_message`, `status`, `updated_at`) VALUES
  ('thread-1', 'c-201', 'f-101', 'David Miller', 'Hi Sarah, we reviewed your proposal for the Fintech Dashboard and were impressed with your portfolio!', 'online', '2026-09-26 23:07:50'),
  ('thread-2', 'c-201', 'f-101', 'Elena Rostova', '', 'offline', '2026-09-26 22:42:02'),
  ('thread-3', 'c-201', 'f-101', 'Marcus Vance', '', 'offline', '2026-09-26 22:42:02'),
  ('thread-4', 'c-202', 'fl-4', 'David Miller', 'Hi Nabila, we reviewed your EKS migration proposal and it looks very solid.', 'online', '2026-09-26 22:42:02'),
  ('thread-5', 'c-203', 'fl-8', 'Elena Rostova', 'The component library is exactly what we needed. Very clean and well-organized.', 'offline', '2026-09-26 22:42:02'),
  ('thread-6', 'c-204', 'fl-3', 'Marcus Vance', 'The risk scoring model is impressive. AUC of 0.87 is above our target.', 'online', '2026-09-26 22:42:02')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed chat_messages (13 rows)
INSERT INTO `chat_messages` (`id`, `thread_id`, `sender_name`, `sender_role`, `text`, `is_me`, `sent_time`, `created_at`) VALUES
  ('1', 'thread-1', 'David Miller', 'Client', 'Hi Sarah, we reviewed your proposal for the Fintech Dashboard and were impressed with your portfolio!', '0', '10:30 AM', '2026-09-26 20:42:00'),
  ('2', 'thread-1', 'Sarah Jenkins', 'Freelancer', 'Thank you David! I would love to discuss your architecture and API specifications.', '1', '10:32 AM', '2026-09-26 20:42:00'),
  ('3', 'thread-1', 'David Miller', 'Client', 'Our backend is built with standard REST endpoints. Can you handle the interactive Chart components?', '0', '10:35 AM', '2026-09-26 20:42:00'),
  ('4', 'thread-1', 'Sarah Jenkins', 'Freelancer', 'Yes, absolutely. I regularly work with Chart.js and can easily consume your endpoints.', '1', '10:38 AM', '2026-09-26 20:42:00'),
  ('5', 'thread-4', 'David Miller', 'Client', 'Hi Nabila, we reviewed your EKS migration proposal and it looks very solid.', '0', '09:15 AM', '2026-09-26 21:13:40'),
  ('6', 'thread-4', 'Nabila Islam', 'Freelancer', 'Thank you David! I have done 3 similar migrations. I will share a detailed plan document today.', '1', '09:22 AM', '2026-09-26 21:13:40'),
  ('7', 'thread-4', 'David Miller', 'Client', 'Can you share your EKS migration plan document?', '0', '09:45 AM', '2026-09-26 21:13:40'),
  ('8', 'thread-5', 'Elena Rostova', 'Client', 'The component library is exactly what we needed. Very clean and well-organized.', '0', '02:10 PM', '2026-09-26 21:13:40'),
  ('9', 'thread-5', 'Amara Diallo', 'Freelancer', 'Glad you like it! I have added dark mode variants for all 48 components as well.', '1', '02:18 PM', '2026-09-26 21:13:40'),
  ('10', 'thread-5', 'Elena Rostova', 'Client', 'The Figma prototype looks excellent. When can we schedule a handoff call?', '0', '02:30 PM', '2026-09-26 21:13:40'),
  ('11', 'thread-6', 'Marcus Vance', 'Client', 'The risk scoring model is impressive. AUC of 0.87 is above our target.', '0', '11:00 AM', '2026-09-26 21:13:40'),
  ('12', 'thread-6', 'Sadman Sakib', 'Freelancer', 'Great news! I used class-weighted XGBoost to handle the imbalanced dataset. SHAP analysis is next.', '1', '11:15 AM', '2026-09-26 21:13:40'),
  ('13', 'thread-6', 'Marcus Vance', 'Client', 'The risk scoring model accuracy looks great. Can you add the SHAP waterfall charts?', '0', '11:30 AM', '2026-09-26 21:13:40')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed project_milestones (5 rows)
INSERT INTO `project_milestones` (`id`, `job_id`, `client_id`, `freelancer_id`, `label`, `description`, `amount`, `escrow_fee`, `status`, `due_date`, `submitted_at`, `approved_at`, `paid_at`, `created_at`) VALUES
  ('ms-15-1', 'job-15', 'c-203', 'fl-1', 'Heuristic evaluation of the five key flows', 'Evaluate signup, checkout, search, support and settings against Nielsen heuristics, with severity-rated findings.', '300.00', '9.00', 'Disputed', '2026-09-12', '2026-09-10 10:20:00', NULL, NULL, '2026-09-26 22:18:24'),
  ('ms-15-2', 'job-15', 'c-203', 'fl-1', 'Accessibility remediation plan and retest', 'Prioritised fix list with effort estimates, then a retest to confirm each finding is resolved.', '250.00', '7.50', 'Posted', '2026-09-30', NULL, NULL, NULL, '2026-09-26 22:18:24'),
  ('ms-18-1', 'job-18', 'c-201', 'f-101', 'Design the chat protocol and data model', 'Message envelope schema, presence handling and the delivery/ack contract, agreed in writing before any UI work starts.', '400.00', '12.00', 'Paid', '2026-08-28', '2026-08-27 15:10:00', '2026-08-28 11:02:00', '2026-08-28 11:02:00', '2026-09-26 22:18:24'),
  ('ms-18-2', 'job-18', 'c-201', 'f-101', 'Realtime message delivery over WebSockets', 'Socket.IO gateway with Redis pub/sub, typing indicators, read receipts and automatic reconnection.', '900.00', '27.00', 'Submitted', '2026-09-20', '2026-09-18 17:45:00', NULL, NULL, '2026-09-26 22:18:24'),
  ('ms-18-3', 'job-18', 'c-201', 'f-101', 'Client integration, tests and handover', 'Wire the existing React app to the gateway, add integration tests and document the deployment steps.', '700.00', '21.00', 'Posted', '2026-10-05', NULL, NULL, NULL, '2026-09-26 22:18:24')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed payments (5 rows)
INSERT INTO `payments` (`id`, `milestone_id`, `job_id`, `client_id`, `freelancer_id`, `amount`, `escrow_fee`, `total`, `status`, `receipt_id`, `released_at`, `created_at`) VALUES
  ('pay-15-1', 'ms-15-1', 'job-15', 'c-203', 'fl-1', '300.00', '9.00', '309.00', 'Held in escrow', NULL, NULL, '2026-09-26 22:18:24'),
  ('pay-15-2', 'ms-15-2', 'job-15', 'c-203', 'fl-1', '250.00', '7.50', '257.50', 'Held in escrow', NULL, NULL, '2026-09-26 22:18:24'),
  ('pay-18-1', 'ms-18-1', 'job-18', 'c-201', 'f-101', '400.00', '12.00', '412.00', 'Released', 'RCP-7A41C9', '2026-08-28 11:02:00', '2026-09-26 22:18:24'),
  ('pay-18-2', 'ms-18-2', 'job-18', 'c-201', 'f-101', '900.00', '27.00', '927.00', 'Held in escrow', NULL, NULL, '2026-09-26 22:18:24'),
  ('pay-18-3', 'ms-18-3', 'job-18', 'c-201', 'f-101', '700.00', '21.00', '721.00', 'Held in escrow', NULL, NULL, '2026-09-26 22:18:24')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed reviews (1 rows)
INSERT INTO `reviews` (`id`, `job_id`, `milestone_id`, `client_id`, `freelancer_id`, `communication`, `quality`, `timeliness`, `overall`, `comment`, `satisfaction_comment`, `created_at`) VALUES
  ('rev-1', 'job-18', 'ms-18-1', 'c-201', 'f-101', '5', '5', '5', '5.00', 'Delivered ahead of the milestone date and the protocol document answered every question we raised in the review call.', 'Exactly the level of detail we needed before committing to the build phase.', '2026-09-26 22:18:24')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed disputes (1 rows)
INSERT INTO `disputes` (`id`, `milestone_id`, `job_id`, `client_id`, `freelancer_id`, `reason`, `description`, `desired_outcome`, `partial_refund_amount`, `status`, `freelancer_response`, `responded_at`, `resolution`, `response_due_at`, `created_at`, `resolved_at`) VALUES
  ('DIS-2026-0001', 'ms-15-1', 'job-15', 'c-203', 'fl-1', 'Work quality', 'The report lists the checkout flow as low risk, but two of the five findings are screen-reader blockers that our audit flagged last quarter. We need this redone before we can share it with the board.', 'Request a revision', NULL, 'Under review', NULL, NULL, NULL, '2026-09-29 10:20:00', '2026-09-26 22:18:24', NULL)
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed notifications (8 rows)
INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `link`, `is_read`, `created_at`) VALUES
  ('1', 'c-201', 'milestone', 'Milestone ready for review', 'f-101 submitted \"Realtime message delivery over WebSockets\" for job-18. Approve it or request changes.', '../client/work-approval.html', '1', '2026-09-26 22:18:24'),
  ('2', 'c-201', 'payment', 'Payment released', 'You released $400.00 to Sarah Jenkins for \"Design the chat protocol and data model\".', '../client/billing-payments.html', '1', '2026-09-26 22:18:24'),
  ('3', 'c-201', 'milestone', 'Escrow funded', 'You funded $721.00 for \"Client integration, tests and handover\". It stays in escrow until you approve the work.', '../client/milestones.html', '1', '2026-09-26 22:18:24'),
  ('4', 'f-101', 'payment', 'You were paid $400.00', 'Abid Hasnine approved \"Design the chat protocol and data model\" on Real-Time Chat System.', '../freelancer/freelancer_projects.html', '1', '2026-09-26 22:18:24'),
  ('5', 'f-101', 'milestone', 'Changes requested', 'c-203 asked for a redo on the heuristic evaluation: two findings were under-rated. Respond within 3 days.', '../freelancer/freelancer_projects.html', '0', '2026-09-26 22:18:24'),
  ('6', 'f-101', 'review', 'You received a 5.00 review', 'Abid Hasnine left 5 stars for \"Design the chat protocol and data model\".', '../freelancer/freelancer_projects.html', '1', '2026-09-26 22:18:24'),
  ('7', 'fl-1', 'dispute', 'Dispute filed against your milestone', 'c-203 opened dispute DIS-2026-0001 (Work quality). The escrow of $300.00 is frozen until an administrator reviews it.', '../freelancer/freelancer_projects.html', '0', '2026-09-26 22:18:24'),
  ('8', 'u-admin', 'dispute', 'New dispute awaiting review', 'DIS-2026-0001 was filed on job-15 by c-203 against fl-1.', '../Admin/html/disputes.html', '0', '2026-09-26 22:18:24')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Seed approvals (5 rows)
INSERT INTO `approvals` (`id`, `user_id`, `type`, `status`, `portfolio_url`, `github_url`, `skills`, `reject_reason`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
  ('apr-1', 'fl-20', 'FREELANCER', 'Pending', 'https://nina.design', 'https://github.com/ninaokafor', 'Figma, Design Systems, Prototyping', NULL, NULL, NULL, '2026-09-26 22:18:24'),
  ('apr-2', 'fl-21', 'FREELANCER', 'Pending', 'https://rahim.dev', 'https://github.com/rahimc', 'Node.js, Laravel, PostgreSQL', NULL, NULL, NULL, '2026-09-26 22:18:24'),
  ('apr-3', 'c-210', 'CLIENT', 'Pending', NULL, NULL, 'Project Management', NULL, NULL, NULL, '2026-09-26 22:18:24'),
  ('apr-4', 'fl-22', 'FREELANCER', 'Approved', 'https://marcusbell.dev', 'https://github.com/marcusbell', 'React, TypeScript, Next.js', NULL, 'u-admin', '2026-08-14 10:12:00', '2026-09-26 22:18:24'),
  ('apr-5', 'fl-23', 'FREELANCER', 'Rejected', 'https://lena.data', NULL, 'SQL, Tableau', 'Portfolio did not show relevant analytics work', 'u-admin', '2026-08-02 16:40:00', '2026-09-26 22:18:24')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);


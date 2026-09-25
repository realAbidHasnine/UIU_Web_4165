-- =============================================================================
-- SkillMatch Database Schema & Seed Data (MySQL / MariaDB for XAMPP phpMyAdmin)
-- Database: skillmatch_db
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `skillmatch_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `skillmatch_db`;

-- -----------------------------------------------------------------------------
-- 1. Table: users
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('FREELANCER', 'CLIENT', 'ADMIN') NOT NULL DEFAULT 'FREELANCER',
  `name` VARCHAR(100) NOT NULL,
  `title` VARCHAR(150) DEFAULT NULL,
  `company` VARCHAR(150) DEFAULT NULL,
  `hourly_rate` DECIMAL(10,2) DEFAULT 0.00,
  `bio` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Suspended', 'Flagged') NOT NULL DEFAULT 'Active',
  `score` INT DEFAULT 90,
  `rating` DECIMAL(3,2) DEFAULT 5.00,
  `earnings` DECIMAL(12,2) DEFAULT 0.00,
  `completed_jobs` INT DEFAULT 0,
  `location` VARCHAR(100) DEFAULT 'Remote',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: jobs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `client_id` VARCHAR(50) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'web',
  `budget_type` ENUM('fixed', 'hourly') NOT NULL DEFAULT 'fixed',
  `budget` VARCHAR(50) NOT NULL DEFAULT '3000',
  `budget_display` VARCHAR(100) NOT NULL DEFAULT '$3,000 Fixed',
  `duration` VARCHAR(50) NOT NULL DEFAULT '1-3-months',
  `duration_display` VARCHAR(100) NOT NULL DEFAULT '1-3 Months',
  `level` VARCHAR(50) NOT NULL DEFAULT 'Intermediate',
  `description` TEXT NOT NULL,
  `company` VARCHAR(150) DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT 'Remote',
  `skills` JSON DEFAULT NULL,
  `responsibilities` JSON DEFAULT NULL,
  `status` ENUM('Open', 'In Progress', 'Completed', 'Awaiting Approval') NOT NULL DEFAULT 'Open',
  `posted_time` VARCHAR(100) DEFAULT 'Just now',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`category`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: proposals
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proposals` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `job_id` VARCHAR(50) NOT NULL,
  `freelancer_id` VARCHAR(50) NOT NULL,
  `proposed_rate` DECIMAL(10,2) NOT NULL,
  `estimated_days` INT NOT NULL,
  `cover_letter` TEXT DEFAULT NULL,
  `status` ENUM('Submitted', 'Active', 'Accepted', 'Archived') NOT NULL DEFAULT 'Submitted',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`job_id`),
  INDEX (`freelancer_id`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: chat_threads
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_threads` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `client_id` VARCHAR(50) NOT NULL,
  `freelancer_id` VARCHAR(50) NOT NULL,
  `client_name` VARCHAR(100) NOT NULL,
  `last_message` TEXT DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'online',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table: chat_messages
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `thread_id` VARCHAR(50) NOT NULL,
  `sender_name` VARCHAR(100) NOT NULL,
  `sender_role` VARCHAR(50) NOT NULL,
  `text` TEXT NOT NULL,
  `is_me` TINYINT(1) DEFAULT 0,
  `sent_time` VARCHAR(50) DEFAULT 'Just now',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Table: portfolio_items
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portfolio_items` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `freelancer_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) DEFAULT '#',
  `image` VARCHAR(255) DEFAULT '../assets/images/portfolio-1.png',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`freelancer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Table: skill_categories
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `skill_categories` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(20) NOT NULL,
  `question_count` INT NOT NULL DEFAULT 5,
  `difficulty` VARCHAR(100) NOT NULL DEFAULT 'Intermediate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Table: skill_questions
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `skill_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(50) NOT NULL,
  `question` TEXT NOT NULL,
  `options` JSON NOT NULL,
  `correct_index` INT NOT NULL,
  INDEX (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Table: test_results
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `test_results` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `freelancer_id` VARCHAR(50) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `score` INT NOT NULL,
  `passed` TINYINT(1) NOT NULL,
  `correct_count` INT NOT NULL,
  `total_count` INT NOT NULL,
  `verified_badge` VARCHAR(50) DEFAULT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`freelancer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Table: deliverables
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `deliverables` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `job_id` VARCHAR(50) NOT NULL,
  `freelancer_id` VARCHAR(50) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `file_paths` JSON DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'Submitted',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- SEED DATA (Pre-populates database to match frontend views immediately)
-- =============================================================================

-- Seed Users
INSERT INTO `users` (`id`, `email`, `password`, `role`, `name`, `title`, `company`, `hourly_rate`, `bio`, `status`, `score`, `rating`, `earnings`, `completed_jobs`, `location`)
VALUES
('f-101', 'sarah.jenkins@example.com', 'password123', 'FREELANCER', 'Sarah Jenkins', 'Senior Frontend & React Specialist', NULL, 65.00, 'Over 6 years of experience building modern, responsive, and performance-critical web applications with React, TypeScript, and modern CSS.', 'Active', 94, 4.95, 28450.00, 34, 'Remote (US/EU)'),
('fl-1', 'abid.hasnine@example.com', 'password123', 'FREELANCER', 'Abid Hasnine', 'Full Stack Engineer & Cloud Architect', NULL, 75.00, 'Full stack web specialist specializing in high-performance cloud architectures, REST APIs, and microservices.', 'Active', 96, 5.00, 42000.00, 48, 'Remote'),
('fl-2', 'sabbir.hossain@example.com', 'password123', 'FREELANCER', 'Sabbir Hossain', 'Senior Mobile & UI/UX Designer', NULL, 60.00, 'Crafting human-centered UI/UX systems and high-converting interfaces across web and mobile platforms.', 'Active', 94, 4.90, 18500.00, 36, 'Remote'),
('fl-3', 'sadman.sakib@example.com', 'password123', 'FREELANCER', 'Sadman Sakib', 'Data Scientist & ML Engineer', NULL, 85.00, 'Predictive analytics, natural language processing, and scalable data pipeline engineering.', 'Active', 99, 5.00, 52000.00, 210, 'Remote'),
('c-201', 'abid.hasina@flow.com', 'password123', 'CLIENT', 'Abida Hasan', 'Design Director', 'Apex Capital Partners', 0.00, 'Managing product engineering pipelines and hiring top-tier technical contractors.', 'Active', 90, 4.90, 0.00, 8, 'London, UK'),
('u-admin', 'admin@skillmatch.com', 'admin123', 'ADMIN', 'System Admin', 'Platform Operations Lead', 'SkillMatch Inc.', 0.00, 'Root administrator for platform monitoring, verification audit, and user accounts.', 'Active', 100, 5.00, 0.00, 0, 'Headquarters')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Jobs
INSERT INTO `jobs` (`id`, `client_id`, `title`, `category`, `budget_type`, `budget`, `budget_display`, `duration`, `duration_display`, `level`, `description`, `company`, `location`, `skills`, `responsibilities`, `status`, `posted_time`)
VALUES
('job-1', 'c-201', 'Modern Fintech Dashboard in React & Spring Boot', 'web', 'fixed', '3000-5000', '$3,000 - $5,000', '1-3-months', '1 to 3 Months', 'Expert', 'We are redesigning our enterprise wealth management dashboard. We require an experienced frontend developer who can build pixel-perfect interactive widgets, realtime chart components, and integrate smoothly with microservices.', 'Apex Capital Partners', 'Remote (US/EU)', '["React", "TypeScript", "Spring Boot", "REST API", "Chart.js"]', '["Develop reusable React components", "Integrate balance and yield REST endpoints", "Optimize rendering for tables with 10k rows"]', 'Open', '2 hours ago'),
('job-2', 'c-201', 'Mobile Banking App UI/UX Redesign System', 'uiux', 'hourly', 'under-1000', '$45 - $65 / hr', 'less-1-month', 'Less than 1 Month', 'Intermediate', 'Seeking a talented product designer to audit and revitalize our mobile retail banking user flows. You will create modern, high-converting Figma component libraries and interactive prototypes.', 'NovaPay Global', 'Remote', '["Figma", "Mobile Design", "Design Systems", "Prototyping", "iOS / Android"]', '["Conduct usability review of existing money-transfer screens", "Build atomic design tokens in Figma", "Deliver specifications for engineering"]', 'Open', '5 hours ago'),
('job-3', 'c-201', 'Full-Stack Cross-Platform Mobile Flutter App', 'mobile', 'fixed', '1000-3000', '$2,500 - $3,500', '1-3-months', '1 to 3 Months', 'Senior', 'Build an iOS and Android fitness tracking companion app that syncs wearable sensor readings in real-time to a secure cloud backend.', 'Stride HealthTech', 'Remote', '["Flutter", "Dart", "WebSockets", "HealthKit"]', '["Implement Bluetooth LE background syncing", "Design offline-first SQLite cache", "Connect biometric login"]', 'Open', '1 day ago'),
('job-4', 'c-201', 'Enterprise Headless E-Commerce Platform', 'web', 'fixed', '5000-plus', '$6,000 - $9,000', 'more-3-months', '3+ Months', 'Expert', 'High-volume international e-commerce redesign with headless CMS, sub-second product catalogue search, and automated inventory sync.', 'Luxe Retail Group', 'Remote (Worldwide)', '["Next.js", "PostgreSQL", "Stripe", "Redis"]', '["Architect Next.js store frontend", "Integrate Stripe Payment Intents", "Implement Redis cache"]', 'Open', '2 days ago')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Seed Proposals
INSERT INTO `proposals` (`id`, `job_id`, `freelancer_id`, `proposed_rate`, `estimated_days`, `cover_letter`, `status`)
VALUES
('prop-1', 'job-1', 'f-101', 3500.00, 14, 'I have extensive experience building scalable financial dashboards with React and Chart.js. I have architected 6 enterprise portals with strict type safety.', 'Active'),
('prop-2', 'job-2', 'f-101', 55.00, 7, 'I can audit your current mobile screens and deliver a clean, componentized design system ready for Flutter.', 'Submitted'),
('prop-3', 'job-4', 'f-101', 7500.00, 30, 'Over 5 years of experience building high-traffic headless e-commerce storefronts with Next.js and secure payments.', 'Active')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);

-- Seed Chat Threads
INSERT INTO `chat_threads` (`id`, `client_id`, `freelancer_id`, `client_name`, `last_message`, `status`)
VALUES
('thread-1', 'c-201', 'f-101', 'David Miller', 'I regularly work with Chart.js and can easily consume your Spring Boot endpoints.', 'online'),
('thread-2', 'c-201', 'f-101', 'Elena Rostova', 'Could you provide sample Figma links for your latest design system work?', 'offline'),
('thread-3', 'c-201', 'f-101', 'Marcus Vance', 'Got it Marcus! I will upload the build through the work portal today.', 'offline')
ON DUPLICATE KEY UPDATE `client_name`=VALUES(`client_name`);

-- Seed Chat Messages
INSERT INTO `chat_messages` (`thread_id`, `sender_name`, `sender_role`, `text`, `is_me`, `sent_time`)
VALUES
('thread-1', 'David Miller', 'Client', 'Hi Sarah, we reviewed your proposal for the Fintech Dashboard and were impressed with your portfolio!', 0, '10:30 AM'),
('thread-1', 'Sarah Jenkins', 'Freelancer', 'Thank you David! I would love to discuss your architecture and API specifications.', 1, '10:32 AM'),
('thread-1', 'David Miller', 'Client', 'Our backend is built with standard REST endpoints. Can you handle the interactive Chart components?', 0, '10:35 AM'),
('thread-1', 'Sarah Jenkins', 'Freelancer', 'Yes, absolutely. I regularly work with Chart.js and can easily consume your endpoints.', 1, '10:38 AM');

-- Seed Portfolio Items
INSERT INTO `portfolio_items` (`id`, `freelancer_id`, `title`, `category`, `url`, `description`)
VALUES
('port-1', 'f-101', 'Fintech Real-Time Trading Terminal', 'Web Application', 'https://github.com/example/trading-ui', 'High-frequency charting dashboard with WebSockets and canvas rendering.'),
('port-2', 'f-101', 'Telehealth Clinical Workspace', 'Healthcare Portal', 'https://github.com/example/telehealth', 'HIPAA-compliant appointment manager, medical records visualizer, and video consultation.'),
('port-3', 'f-101', 'Global Logistics Freight Tracker', 'Enterprise SaaS', 'https://github.com/example/freight', 'Supply-chain map visualization with route optimization calculations.')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Seed Skill Categories
INSERT INTO `skill_categories` (`id`, `name`, `icon`, `question_count`, `difficulty`)
VALUES
('web', 'Web Development', '💻', 5, 'Intermediate - Expert'),
('uiux', 'UI / UX Design', '🎨', 5, 'All Levels'),
('mobile', 'Mobile App Development', '📱', 5, 'Intermediate - Advanced'),
('cloud', 'Cloud & DevOps', '☁️', 5, 'Advanced - Expert')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Skill Questions
INSERT INTO `skill_questions` (`category`, `question`, `options`, `correct_index`)
VALUES
('web', 'In modern JavaScript and asynchronous programming, what does Promise.all() do when one of the input promises rejects?', '["It waits for all other promises to resolve before returning an error", "It immediately rejects with the reason of the first promise that rejected", "It ignores the rejected promise and returns successful results", "It retries the rejected promise three times automatically"]', 1),
('web', 'Which HTTP method is idempotent and intended to completely replace an existing resource on a REST API?', '["POST", "PATCH", "PUT", "CONNECT"]', 2),
('web', 'What is the primary benefit of React Virtual DOM reconciliation (Diffing Algorithm)?', '["Bypassing CSS cascade calculations completely", "Minimizing costly native DOM layout recalculations and repaints", "Guaranteeing zero memory consumption during animation loops", "Converting JSX syntax directly into native machine assembly"]', 1),
('web', 'In database systems, what does the "I" in the ACID guarantee stand for?', '["Integrity", "Isolation", "Indexation", "Idempotence"]', 1),
('web', 'In web applications, what HTTP header protects against Cross-Site Scripting (XSS) by restricting where scripts can execute from?', '["Access-Control-Allow-Origin", "Content-Security-Policy", "X-Content-Type-Options", "Strict-Transport-Security"]', 1);

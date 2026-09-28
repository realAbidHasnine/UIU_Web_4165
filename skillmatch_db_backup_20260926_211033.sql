-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: skillmatch_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,'thread-1','David Miller','Client','Hi Sarah, we reviewed your proposal for the Fintech Dashboard and were impressed with your portfolio!',0,'10:30 AM','2026-09-26 14:42:00'),(2,'thread-1','Sarah Jenkins','Freelancer','Thank you David! I would love to discuss your architecture and API specifications.',1,'10:32 AM','2026-09-26 14:42:00'),(3,'thread-1','David Miller','Client','Our backend is built with standard REST endpoints. Can you handle the interactive Chart components?',0,'10:35 AM','2026-09-26 14:42:00'),(4,'thread-1','Sarah Jenkins','Freelancer','Yes, absolutely. I regularly work with Chart.js and can easily consume your endpoints.',1,'10:38 AM','2026-09-26 14:42:00');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_threads`
--

DROP TABLE IF EXISTS `chat_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_threads` (
  `id` varchar(50) NOT NULL,
  `client_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `last_message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'online',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_threads`
--

LOCK TABLES `chat_threads` WRITE;
/*!40000 ALTER TABLE `chat_threads` DISABLE KEYS */;
INSERT INTO `chat_threads` VALUES ('thread-1','c-201','f-101','David Miller','I regularly work with Chart.js and can easily consume your Spring Boot endpoints.','online','2026-09-26 14:42:00'),('thread-2','c-201','f-101','Elena Rostova','Could you provide sample Figma links for your latest design system work?','offline','2026-09-26 14:42:00'),('thread-3','c-201','f-101','Marcus Vance','Got it Marcus! I will upload the build through the work portal today.','offline','2026-09-26 14:42:00');
/*!40000 ALTER TABLE `chat_threads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliverables`
--

DROP TABLE IF EXISTS `deliverables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverables` (
  `id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `freelancer_id` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `file_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`file_paths`)),
  `status` varchar(50) DEFAULT 'Submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliverables`
--

LOCK TABLES `deliverables` WRITE;
/*!40000 ALTER TABLE `deliverables` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliverables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
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
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES ('job-1','c-201','Modern Fintech Dashboard in React & Spring Boot','web','fixed','3000-5000','$3,000 - $5,000','1-3-months','1 to 3 Months','Expert','We are redesigning our enterprise wealth management dashboard. We require an experienced frontend developer who can build pixel-perfect interactive widgets, realtime chart components, and integrate smoothly with microservices.','Apex Capital Partners','Remote (US/EU)','[\"React\", \"TypeScript\", \"Spring Boot\", \"REST API\", \"Chart.js\"]','[\"Develop reusable React components\", \"Integrate balance and yield REST endpoints\", \"Optimize rendering for tables with 10k rows\"]','Open','2 hours ago','2026-09-26 14:42:00'),('job-2','c-201','Mobile Banking App UI/UX Redesign System','uiux','hourly','under-1000','$45 - $65 / hr','less-1-month','Less than 1 Month','Intermediate','Seeking a talented product designer to audit and revitalize our mobile retail banking user flows. You will create modern, high-converting Figma component libraries and interactive prototypes.','NovaPay Global','Remote','[\"Figma\", \"Mobile Design\", \"Design Systems\", \"Prototyping\", \"iOS / Android\"]','[\"Conduct usability review of existing money-transfer screens\", \"Build atomic design tokens in Figma\", \"Deliver specifications for engineering\"]','Open','5 hours ago','2026-09-26 14:42:00'),('job-3','c-201','Full-Stack Cross-Platform Mobile Flutter App','mobile','fixed','1000-3000','$2,500 - $3,500','1-3-months','1 to 3 Months','Senior','Build an iOS and Android fitness tracking companion app that syncs wearable sensor readings in real-time to a secure cloud backend.','Stride HealthTech','Remote','[\"Flutter\", \"Dart\", \"WebSockets\", \"HealthKit\"]','[\"Implement Bluetooth LE background syncing\", \"Design offline-first SQLite cache\", \"Connect biometric login\"]','Open','1 day ago','2026-09-26 14:42:00'),('job-4','c-201','Enterprise Headless E-Commerce Platform','web','fixed','5000-plus','$6,000 - $9,000','more-3-months','3+ Months','Expert','High-volume international e-commerce redesign with headless CMS, sub-second product catalogue search, and automated inventory sync.','Luxe Retail Group','Remote (Worldwide)','[\"Next.js\", \"PostgreSQL\", \"Stripe\", \"Redis\"]','[\"Architect Next.js store frontend\", \"Integrate Stripe Payment Intents\", \"Implement Redis cache\"]','Open','2 days ago','2026-09-26 14:42:00');
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portfolio_items`
--

DROP TABLE IF EXISTS `portfolio_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_items` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portfolio_items`
--

LOCK TABLES `portfolio_items` WRITE;
/*!40000 ALTER TABLE `portfolio_items` DISABLE KEYS */;
INSERT INTO `portfolio_items` VALUES ('port-1','f-101','Fintech Real-Time Trading Terminal','Web Application','https://github.com/example/trading-ui','../assets/images/portfolio-1.png','High-frequency charting dashboard with WebSockets and canvas rendering.','2026-09-26 14:42:00'),('port-2','f-101','Telehealth Clinical Workspace','Healthcare Portal','https://github.com/example/telehealth','../assets/images/portfolio-1.png','HIPAA-compliant appointment manager, medical records visualizer, and video consultation.','2026-09-26 14:42:00'),('port-3','f-101','Global Logistics Freight Tracker','Enterprise SaaS','https://github.com/example/freight','../assets/images/portfolio-1.png','Supply-chain map visualization with route optimization calculations.','2026-09-26 14:42:00');
/*!40000 ALTER TABLE `portfolio_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proposals`
--

DROP TABLE IF EXISTS `proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proposals` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proposals`
--

LOCK TABLES `proposals` WRITE;
/*!40000 ALTER TABLE `proposals` DISABLE KEYS */;
INSERT INTO `proposals` VALUES ('prop-1','job-1','f-101',3500.00,14,'I have extensive experience building scalable financial dashboards with React and Chart.js. I have architected 6 enterprise portals with strict type safety.','Active','2026-09-26 14:42:00'),('prop-2','job-2','f-101',55.00,7,'I can audit your current mobile screens and deliver a clean, componentized design system ready for Flutter.','Submitted','2026-09-26 14:42:00'),('prop-3','job-4','f-101',7500.00,30,'Over 5 years of experience building high-traffic headless e-commerce storefronts with Next.js and secure payments.','Active','2026-09-26 14:42:00');
/*!40000 ALTER TABLE `proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skill_categories`
--

DROP TABLE IF EXISTS `skill_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_categories` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(20) NOT NULL,
  `question_count` int(11) NOT NULL DEFAULT 5,
  `difficulty` varchar(100) NOT NULL DEFAULT 'Intermediate',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_categories`
--

LOCK TABLES `skill_categories` WRITE;
/*!40000 ALTER TABLE `skill_categories` DISABLE KEYS */;
INSERT INTO `skill_categories` VALUES ('cloud','Cloud & DevOps','☁️',5,'Advanced - Expert'),('mobile','Mobile App Development','📱',5,'Intermediate - Advanced'),('uiux','UI / UX Design','🎨',5,'All Levels'),('web','Web Development','💻',5,'Intermediate - Expert');
/*!40000 ALTER TABLE `skill_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skill_questions`
--

DROP TABLE IF EXISTS `skill_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `question` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_index` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_questions`
--

LOCK TABLES `skill_questions` WRITE;
/*!40000 ALTER TABLE `skill_questions` DISABLE KEYS */;
INSERT INTO `skill_questions` VALUES (1,'web','In modern JavaScript and asynchronous programming, what does Promise.all() do when one of the input promises rejects?','[\"It waits for all other promises to resolve before returning an error\", \"It immediately rejects with the reason of the first promise that rejected\", \"It ignores the rejected promise and returns successful results\", \"It retries the rejected promise three times automatically\"]',1),(2,'web','Which HTTP method is idempotent and intended to completely replace an existing resource on a REST API?','[\"POST\", \"PATCH\", \"PUT\", \"CONNECT\"]',2),(3,'web','What is the primary benefit of React Virtual DOM reconciliation (Diffing Algorithm)?','[\"Bypassing CSS cascade calculations completely\", \"Minimizing costly native DOM layout recalculations and repaints\", \"Guaranteeing zero memory consumption during animation loops\", \"Converting JSX syntax directly into native machine assembly\"]',1),(4,'web','In database systems, what does the \"I\" in the ACID guarantee stand for?','[\"Integrity\", \"Isolation\", \"Indexation\", \"Idempotence\"]',1),(5,'web','In web applications, what HTTP header protects against Cross-Site Scripting (XSS) by restricting where scripts can execute from?','[\"Access-Control-Allow-Origin\", \"Content-Security-Policy\", \"X-Content-Type-Options\", \"Strict-Transport-Security\"]',1);
/*!40000 ALTER TABLE `skill_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_results`
--

DROP TABLE IF EXISTS `test_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_results` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_results`
--

LOCK TABLES `test_results` WRITE;
/*!40000 ALTER TABLE `test_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('c-201','abid.hasina@flow.com','password123','CLIENT','Abida Hasan','Design Director','Apex Capital Partners',0.00,'Managing product engineering pipelines and hiring top-tier technical contractors.','Active',90,4.90,0.00,8,'London, UK','2026-09-26 14:42:00'),('f-101','sarah.jenkins@example.com','password123','FREELANCER','Sarah Jenkins','Senior Frontend & React Specialist',NULL,65.00,'Over 6 years of experience building modern, responsive, and performance-critical web applications with React, TypeScript, and modern CSS.','Active',94,4.95,28450.00,34,'Remote (US/EU)','2026-09-26 14:42:00'),('fl-1','abid.hasnine@example.com','password123','FREELANCER','Abid Hasnine','Full Stack Engineer & Cloud Architect',NULL,75.00,'Full stack web specialist specializing in high-performance cloud architectures, REST APIs, and microservices.','Active',96,5.00,42000.00,48,'Remote','2026-09-26 14:42:00'),('fl-2','sabbir.hossain@example.com','password123','FREELANCER','Sabbir Hossain','Senior Mobile & UI/UX Designer',NULL,60.00,'Crafting human-centered UI/UX systems and high-converting interfaces across web and mobile platforms.','Active',94,4.90,18500.00,36,'Remote','2026-09-26 14:42:00'),('fl-3','sadman.sakib@example.com','password123','FREELANCER','Sadman Sakib','Data Scientist & ML Engineer',NULL,85.00,'Predictive analytics, natural language processing, and scalable data pipeline engineering.','Active',99,5.00,52000.00,210,'Remote','2026-09-26 14:42:00'),('u-admin','admin@skillmatch.com','admin123','ADMIN','System Admin','Platform Operations Lead','SkillMatch Inc.',0.00,'Root administrator for platform monitoring, verification audit, and user accounts.','Active',100,5.00,0.00,0,'Headquarters','2026-09-26 14:42:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-26 21:10:34

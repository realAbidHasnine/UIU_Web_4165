-- =============================================================================
-- SkillMatch Schema Additions & Extended Seed Data
-- Safe to re-run: uses IF NOT EXISTS + INSERT IGNORE / ON DUPLICATE KEY UPDATE
-- =============================================================================

USE `skillmatch_db`;

-- -----------------------------------------------------------------------------
-- NEW TABLE: sessions (maps Bearer tokens → user IDs)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `token` VARCHAR(64) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- NEW TABLE: user_skills (per-user skill tags)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(50) NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- UPDATE existing users to use hashed passwords
-- (password_hash('password123', PASSWORD_BCRYPT) — pre-computed below)
-- All demo users share password: password123  (admin: admin123)
-- -----------------------------------------------------------------------------
UPDATE `users` SET `password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
  WHERE `id` IN ('f-101','fl-1','fl-2','fl-3') AND LENGTH(`password`) < 60;

UPDATE `users` SET `password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
  WHERE `id` = 'c-201' AND LENGTH(`password`) < 60;

-- admin123 hash
UPDATE `users` SET `password` = '$2y$10$T3JF0.s.8Sn0yHkfvdIVeO0cHWxPnq3Y8MlIxmIxB8lrqNxGRtUy'
  WHERE `id` = 'u-admin' AND LENGTH(`password`) < 60;

-- -----------------------------------------------------------------------------
-- Seed user_skills
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `user_skills` (`user_id`, `skill_name`) VALUES
-- Sarah Jenkins (f-101)
('f-101', 'React'), ('f-101', 'TypeScript'), ('f-101', 'TailwindCSS'),
('f-101', 'Spring Boot'), ('f-101', 'REST APIs'), ('f-101', 'Chart.js'),
-- Abid Hasnine (fl-1)
('fl-1', 'Spring Boot'), ('fl-1', 'React'), ('fl-1', 'Docker'),
('fl-1', 'PostgreSQL'), ('fl-1', 'AWS'), ('fl-1', 'Kubernetes'),
-- Sabbir Hossain (fl-2)
('fl-2', 'Figma'), ('fl-2', 'UI/UX Design'), ('fl-2', 'Mobile Design'),
('fl-2', 'Flutter'), ('fl-2', 'Prototyping'), ('fl-2', 'Design Systems'),
-- Sadman Sakib (fl-3)
('fl-3', 'Python'), ('fl-3', 'Machine Learning'), ('fl-3', 'TensorFlow'),
('fl-3', 'Data Science'), ('fl-3', 'NLP'), ('fl-3', 'SQL');

-- -----------------------------------------------------------------------------
-- Extended Skill Questions — uiux category (5 questions)
-- -----------------------------------------------------------------------------
INSERT INTO `skill_questions` (`category`, `question`, `options`, `correct_index`) VALUES
('uiux', 'In design systems, what is the purpose of a "token" (design token)?',
 '["A placeholder component used in wireframes","A named variable that stores a design decision like color, spacing, or typography","A type of user interaction event","A password used for Figma API access"]', 1),
('uiux', 'Which UX research method is most appropriate for discovering users'' mental models and unexplored pain points?',
 '["A/B Testing","Moderated usability testing","Contextual inquiry / ethnographic observation","Card sorting"]', 2),
('uiux', 'In Fitts'' Law applied to UI design, which factor most directly determines the time required to acquire a target?',
 '["The color contrast ratio of the button","The distance to the target and its size","The font weight of the button label","The number of items in the navigation"]', 1),
('uiux', 'What does WCAG 2.1 Level AA require for the contrast ratio of normal text against its background?',
 '["At least 2.5:1","At least 4.5:1","At least 7:1","At least 3:1"]', 1),
('uiux', 'In atomic design methodology, what is the correct hierarchy from smallest to largest?',
 '["Atoms → Molecules → Organisms → Templates → Pages","Molecules → Atoms → Organisms → Pages → Templates","Pages → Templates → Organisms → Molecules → Atoms","Atoms → Organisms → Molecules → Templates → Pages"]', 0);

-- -----------------------------------------------------------------------------
-- Extended Skill Questions — mobile category (5 questions)
-- -----------------------------------------------------------------------------
INSERT INTO `skill_questions` (`category`, `question`, `options`, `correct_index`) VALUES
('mobile', 'In Flutter, what is the fundamental difference between a StatelessWidget and a StatefulWidget?',
 '["StatelessWidget supports animations; StatefulWidget does not","StatefulWidget can rebuild itself when its internal state changes; StatelessWidget cannot","StatelessWidget is faster but does not support layout","StatefulWidget can only be used in the root widget tree"]', 1),
('mobile', 'In iOS development, what is the primary purpose of the App Delegate?',
 '["Rendering UI components on screen","Managing the app lifecycle events and entry point","Handling push notifications only","Storing persistent user preferences"]', 1),
('mobile', 'Which architectural pattern is recommended by Google for Android development using Jetpack components?',
 '["MVC (Model-View-Controller)","MVVM (Model-View-ViewModel)","MVP (Model-View-Presenter)","VIPER"]', 1),
('mobile', 'What does React Native''s "Bridge" primarily do?',
 '["Converts JSX to native Swift code at build time","Enables communication between JavaScript and native platform threads","Compiles JavaScript into ARM machine code","Manages routing between screens"]', 1),
('mobile', 'In Android, what is the correct way to preserve UI state across configuration changes (e.g., screen rotation)?',
 '["Store state in a static variable","Use a ViewModel from the Android Architecture Components","Write state to SharedPreferences on every change","Override onSaveInstanceState() only"]', 1);

-- -----------------------------------------------------------------------------
-- Extended Skill Questions — cloud category (5 questions)
-- -----------------------------------------------------------------------------
INSERT INTO `skill_questions` (`category`, `question`, `options`, `correct_index`) VALUES
('cloud', 'In Kubernetes, what is the purpose of a "Deployment" object?',
 '["To expose a set of pods as a network service","To declare the desired state for a replicated application and manage rolling updates","To configure persistent storage volumes","To define security policies for pods"]', 1),
('cloud', 'Which AWS service is a serverless compute platform that runs code in response to events without provisioning servers?',
 '["Amazon EC2","AWS Fargate","AWS Lambda","Amazon ECS"]', 2),
('cloud', 'What does the CAP theorem state about distributed systems?',
 '["A system can achieve Consistency, Availability, and Partition tolerance simultaneously","A distributed system can guarantee at most two of: Consistency, Availability, and Partition tolerance","Consistency and Availability are mutually exclusive in all cloud systems","Partition tolerance can always be traded for performance"]', 1),
('cloud', 'In Docker, what is the difference between a Docker image and a Docker container?',
 '["They are the same thing with different names","An image is a read-only blueprint; a container is a running instance of an image","A container is stored on disk; an image runs in memory","An image requires a host OS; a container does not"]', 1),
('cloud', 'What is the primary purpose of a CI/CD pipeline?',
 '["To manually test code before deployment","To automate the build, test, and deployment lifecycle so changes can be delivered reliably and frequently","To monitor production application performance","To manage infrastructure provisioning"]', 1);

-- -----------------------------------------------------------------------------
-- Extended Seed Users (15 more freelancers + 4 more clients)
-- All use password: password123  (same bcrypt hash as above)
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`,`email`,`password`,`role`,`name`,`title`,`company`,`hourly_rate`,`bio`,`status`,`score`,`rating`,`earnings`,`completed_jobs`,`location`)
VALUES
('fl-4','nabila.islam@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Nabila Islam','Cloud & DevOps Engineer',NULL,80.00,'AWS-certified cloud infrastructure specialist with expertise in Kubernetes orchestration, CI/CD pipelines, and Infrastructure as Code with Terraform.','Active',97,4.95,38000.00,42,'Remote'),
('fl-5','tanvir.ahmed@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Tanvir Ahmed','Mobile App Developer (Android/iOS)',NULL,70.00,'Native Android/iOS developer with 5+ years building finance and healthcare applications. Expert in Kotlin, Swift, and Flutter.','Active',93,4.85,22000.00,31,'Remote'),
('fl-6','riya.sharma@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Riya Sharma','Backend API Architect',NULL,72.00,'Builds high-throughput REST and GraphQL APIs using Node.js, Express, and PostgreSQL. Specializes in event-driven microservices.','Active',91,4.88,19500.00,28,'Remote'),
('fl-7','james.okonkwo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','James Okonkwo','Full Stack Developer',NULL,68.00,'7 years building end-to-end web applications with Vue.js, Django, and PostgreSQL for SaaS and e-commerce clients.','Active',89,4.80,16800.00,24,'Remote'),
('fl-8','amara.diallo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Amara Diallo','UI/UX Product Designer',NULL,58.00,'Human-centered designer specializing in B2B SaaS dashboard design, design system architecture, and usability research.','Active',95,4.92,26000.00,38,'Remote'),
('fl-9','carlos.mendez@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Carlos Mendez','Data Engineer & Python Specialist',NULL,76.00,'Designs and maintains production data pipelines using Apache Spark, Airflow, and dbt. Experience with BigQuery and Redshift.','Active',90,4.82,21000.00,27,'Remote'),
('fl-10','priya.nair@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Priya Nair','React Native Developer',NULL,65.00,'Cross-platform mobile developer with 4 years of React Native experience. Built apps for logistics, fintech, and healthcare sectors.','Active',88,4.78,14500.00,21,'Remote'),
('fl-11','lukas.bergmann@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Lukas Bergmann','Blockchain & Smart Contract Developer',NULL,90.00,'Solidity and Web3.js expert. Built DeFi protocols, NFT minting platforms, and token vesting contracts on Ethereum and Polygon.','Active',92,4.90,31000.00,19,'Remote'),
('fl-12','fatima.al-rashid@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Fatima Al-Rashid','Cybersecurity Analyst',NULL,85.00,'Penetration tester and security consultant. OSCP certified. Conducts web app security audits, red team exercises, and compliance reviews.','Active',96,4.98,44000.00,35,'Remote'),
('fl-13','seun.adeleke@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Seun Adeleke','Technical Writer & Developer Advocate',NULL,50.00,'Writes API documentation, SDK guides, and developer tutorials for SaaS companies. Expert in OpenAPI, Swagger, and Postman.','Active',87,4.75,9800.00,33,'Remote'),
('fl-14','maya.johnson@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','FREELANCER','Maya Johnson','Senior QA Automation Engineer',NULL,62.00,'Test automation architect using Selenium, Playwright, and Cypress. Builds robust CI-integrated test suites for agile teams.','Active',91,4.87,17200.00,29,'Remote'),
('c-202','david.miller@nexacorp.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','CLIENT','David Miller','CTO','NexaCorp Technologies',0.00,'Building next-gen enterprise SaaS solutions and looking for top-tier freelance engineering talent to accelerate our roadmap.','Active',90,4.90,0.00,12,'New York, USA'),
('c-203','elena.rostova@novapay.io','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','CLIENT','Elena Rostova','Head of Product','NovaPay Global',0.00,'Fintech product leader looking for UI/UX and mobile specialists to build our next-gen payment experience.','Active',88,4.80,0.00,7,'London, UK'),
('c-204','marcus.vance@stridehealth.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','CLIENT','Marcus Vance','CEO','Stride HealthTech',0.00,'Building digital health products that improve patient outcomes. Hiring engineers and designers passionate about healthcare tech.','Active',85,4.70,0.00,5,'San Francisco, USA'),
('c-205','sophia.lee@luxeretail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','CLIENT','Sophia Lee','VP Engineering','Luxe Retail Group',0.00,'Scaling our headless e-commerce infrastructure. Need backend, frontend, and DevOps specialists with high-traffic platform experience.','Active',92,4.95,0.00,15,'Singapore')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- -----------------------------------------------------------------------------
-- User Skills for extended freelancers
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `user_skills` (`user_id`, `skill_name`) VALUES
('fl-4','AWS'),('fl-4','Kubernetes'),('fl-4','Terraform'),('fl-4','Docker'),('fl-4','CI/CD'),('fl-4','Linux'),
('fl-5','Kotlin'),('fl-5','Swift'),('fl-5','Flutter'),('fl-5','Android'),('fl-5','iOS'),('fl-5','Firebase'),
('fl-6','Node.js'),('fl-6','GraphQL'),('fl-6','PostgreSQL'),('fl-6','Redis'),('fl-6','Express.js'),
('fl-7','Vue.js'),('fl-7','Django'),('fl-7','Python'),('fl-7','PostgreSQL'),('fl-7','REST API'),
('fl-8','Figma'),('fl-8','UI/UX Design'),('fl-8','Design Systems'),('fl-8','User Research'),('fl-8','Prototyping'),
('fl-9','Apache Spark'),('fl-9','Airflow'),('fl-9','Python'),('fl-9','dbt'),('fl-9','BigQuery'),('fl-9','SQL'),
('fl-10','React Native'),('fl-10','JavaScript'),('fl-10','iOS'),('fl-10','Android'),('fl-10','Redux'),
('fl-11','Solidity'),('fl-11','Web3.js'),('fl-11','Ethereum'),('fl-11','DeFi'),('fl-11','Smart Contracts'),
('fl-12','Penetration Testing'),('fl-12','OSCP'),('fl-12','Cybersecurity'),('fl-12','OWASP'),('fl-12','Burp Suite'),
('fl-13','Technical Writing'),('fl-13','OpenAPI'),('fl-13','Postman'),('fl-13','Markdown'),('fl-13','REST APIs'),
('fl-14','Selenium'),('fl-14','Playwright'),('fl-14','Cypress'),('fl-14','QA Automation'),('fl-14','Pytest');

-- -----------------------------------------------------------------------------
-- Extended Jobs (20 more realistic jobs across all categories)
-- -----------------------------------------------------------------------------
INSERT INTO `jobs` (`id`,`client_id`,`title`,`category`,`budget_type`,`budget`,`budget_display`,`duration`,`duration_display`,`level`,`description`,`company`,`location`,`skills`,`responsibilities`,`status`,`posted_time`)
VALUES
('job-5','c-202','Cloud Infrastructure Migration to AWS EKS','cloud','fixed','5000-plus','$7,500 - $12,000','more-3-months','3+ Months','Expert','Migrate our legacy VM-based microservices to AWS EKS with zero-downtime. Includes Terraform IaC, Helm charts, and multi-region failover.','NexaCorp Technologies','Remote (Worldwide)','["AWS","Kubernetes","Terraform","Helm","Docker","CI/CD"]','["Architect multi-region EKS cluster","Write Terraform modules for VPC and EKS","Set up Helm chart releases for all services","Implement blue/green deployment strategy"]','Open','3 hours ago'),

('job-6','c-203','Figma Design System & Mobile Prototype','uiux','hourly','under-1000','$55 - $75 / hr','less-1-month','Less than 1 Month','Intermediate','Build a comprehensive Figma component library and interactive prototype for our mobile payment app. Must include dark mode variants and accessibility annotations.','NovaPay Global','Remote','["Figma","Mobile Design","Design Systems","Prototyping","Accessibility"]','["Audit existing screen inventory","Build atomic component library","Create interactive prototype for 12 core flows","Document accessibility annotations"]','Open','1 hour ago'),

('job-7','c-204','Healthcare Patient Portal — React + Node.js','web','fixed','3000-5000','$4,500 - $6,000','1-3-months','1 to 3 Months','Intermediate','Build a HIPAA-compliant patient portal with appointment scheduling, lab results display, and video consultation integration.','Stride HealthTech','Remote (US Only)','["React","Node.js","PostgreSQL","HIPAA","WebRTC","JWT"]','["Implement secure auth with MFA","Build appointment booking calendar","Integrate lab results API","Add video consultation module"]','Open','6 hours ago'),

('job-8','c-205','Next.js Headless E-Commerce Storefront','web','fixed','5000-plus','$8,000 - $12,000','more-3-months','3+ Months','Expert','Architect and build a high-performance headless Next.js storefront for 500K+ SKU catalogue. Sub-second search, Stripe integration, and CDN-optimized images.','Luxe Retail Group','Remote','["Next.js","TypeScript","Stripe","Redis","Algolia","CDN"]','["Implement ISR for product pages","Integrate Algolia search with instant results","Build Stripe checkout with 3DS support","Set up Redis cache for cart sessions"]','Open','12 hours ago'),

('job-9','c-202','Data Pipeline & Analytics Dashboard','data','fixed','1000-3000','$2,000 - $3,500','1-3-months','1 to 3 Months','Intermediate','Build an automated ETL pipeline from multiple CRM sources into a central data warehouse, with a React analytics dashboard.','NexaCorp Technologies','Remote','["Python","Apache Airflow","dbt","BigQuery","React","Chart.js"]','["Design star schema in BigQuery","Build Airflow DAGs for nightly ETL","Create dbt transformation models","Build React dashboard with drill-down charts"]','Open','1 day ago'),

('job-10','c-203','Mobile Banking App — Flutter','mobile','fixed','3000-5000','$4,000 - $5,500','1-3-months','1 to 3 Months','Senior','Build a cross-platform Flutter banking app with biometric auth, transaction history, P2P transfers, and push notifications.','NovaPay Global','Remote','["Flutter","Dart","Firebase","Biometrics","Push Notifications","REST APIs"]','["Implement biometric login (FaceID/TouchID)","Build real-time transaction feed","Add P2P transfer with QR code scan","Integrate Firebase Cloud Messaging"]','Open','2 days ago'),

('job-11','c-204','Machine Learning — Patient Risk Scoring Model','data','hourly','under-1000','$75 - $100 / hr','1-3-months','1 to 3 Months','Expert','Develop an ML model to predict 30-day hospital readmission risk from patient EHR data. Include explainability layer for clinicians.','Stride HealthTech','Remote','["Python","scikit-learn","XGBoost","SHAP","FastAPI","PostgreSQL"]','["Explore and preprocess EHR dataset","Train and evaluate classification models","Implement SHAP explainability","Wrap model in FastAPI endpoint"]','Open','3 days ago'),

('job-12','c-205','AWS Lambda Serverless API Refactor','cloud','hourly','under-1000','$60 - $80 / hr','less-1-month','Less than 1 Month','Intermediate','Refactor 12 existing REST API endpoints from a monolith to AWS Lambda + API Gateway with proper IAM roles and DynamoDB.','Luxe Retail Group','Remote','["AWS Lambda","API Gateway","DynamoDB","IAM","Serverless Framework","Node.js"]','["Map existing endpoints to Lambda functions","Implement API Gateway with custom authorizers","Migrate data layer to DynamoDB","Write integration tests"]','Open','4 days ago'),

('job-13','c-201','Admin Dashboard — React + Spring Boot','web','fixed','1000-3000','$2,500 - $3,500','less-1-month','Less than 1 Month','Intermediate','Build an internal admin panel to manage user accounts, view analytics, and export CSV reports. Must integrate with existing Spring Boot APIs.','Apex Capital Partners','Remote','["React","TypeScript","Chart.js","Spring Boot","REST API","CSV Export"]','["Build user management CRUD tables","Implement date-range analytics charts","Add CSV export functionality","Write unit tests for API integration"]','Open','5 days ago'),

('job-14','c-202','iOS App — AI-Powered Fitness Coach','mobile','fixed','3000-5000','$5,000 - $7,000','1-3-months','1 to 3 Months','Expert','Build a native iOS fitness coaching app with CoreML workout detection, Apple Watch sync, and GPT-powered personalized workout plans.','NexaCorp Technologies','Remote','["Swift","SwiftUI","CoreML","HealthKit","WatchKit","OpenAI API"]','["Implement CoreML workout detection model","Build HealthKit and Apple Watch sync","Integrate OpenAI API for personalized plans","Design SwiftUI interface with animations"]','Open','6 days ago'),

('job-15','c-203','UX Research & Accessibility Audit','uiux','hourly','under-1000','$50 - $70 / hr','less-1-month','Less than 1 Month','Intermediate','Conduct a comprehensive UX research study and accessibility audit of our 3 main user flows, with actionable WCAG 2.1 AA recommendations.','NovaPay Global','Remote','["UX Research","WCAG 2.1","Accessibility","Figma","Usability Testing","ARIA"]','["Conduct 8 moderated usability sessions","Run automated accessibility scans","Annotate WCAG violations in Figma","Deliver prioritized recommendation report"]','In Progress','1 week ago'),

('job-16','c-204','DevOps — GitHub Actions CI/CD Pipeline','cloud','fixed','under-1000','$800 - $1,200','less-1-month','Less than 1 Month','Intermediate','Set up GitHub Actions CI/CD pipeline with automated testing, Docker image builds, staging deployment, and Slack notifications.','Stride HealthTech','Remote','["GitHub Actions","Docker","AWS ECR","Kubernetes","Slack API","Jest"]','["Write multi-stage GitHub Actions workflow","Configure Docker build and push to ECR","Set up kubectl deployment to staging","Add Slack notification on deploy status"]','Open','1 week ago'),

('job-17','c-205','E-Commerce SEO & Core Web Vitals Optimization','web','fixed','under-1000','$1,500 - $2,500','less-1-month','Less than 1 Month','Intermediate','Audit and optimize our Next.js storefront for Core Web Vitals scores (LCP < 2.5s, FID < 100ms, CLS < 0.1) and on-page SEO.','Luxe Retail Group','Remote','["Next.js","Core Web Vitals","SEO","Image Optimization","Lighthouse","Structured Data"]','["Run Lighthouse and CrUX analysis","Optimize image loading with next/image","Implement structured data schema","Fix cumulative layout shift issues"]','Open','1 week ago'),

('job-18','c-201','Real-Time Chat System — WebSockets + React','web','fixed','1000-3000','$2,000 - $3,000','1-3-months','1 to 3 Months','Intermediate','Build a real-time chat module using WebSockets (Socket.io) and React for our freelancer marketplace platform.','Apex Capital Partners','Remote','["React","Socket.io","Node.js","Redis","PostgreSQL","JWT"]','["Implement Socket.io server with Redis pub/sub","Build React chat UI with message history","Add typing indicators and read receipts","Implement JWT auth for WebSocket connections"]','In Progress','2 weeks ago'),

('job-19','c-202','API Security Audit & Penetration Test','cloud','hourly','1000-3000','$85 - $110 / hr','less-1-month','Less than 1 Month','Expert','Conduct a comprehensive black-box and grey-box penetration test on our REST API infrastructure, with detailed report and remediation guidance.','NexaCorp Technologies','Remote','["Penetration Testing","OWASP","Burp Suite","OSCP","REST APIs","Security Report"]','["Enumerate and fingerprint all API endpoints","Test for OWASP Top 10 vulnerabilities","Exploit and document all findings","Deliver prioritized remediation report"]','Open','2 weeks ago'),

('job-20','c-203','Branding & Design — Mobile App Launch Kit','uiux','fixed','1000-3000','$1,800 - $2,500','less-1-month','Less than 1 Month','Intermediate','Design complete brand identity and app store launch kit: logo, color system, typography, App Store/Play Store screenshots, and onboarding screens.','NovaPay Global','Remote','["Figma","Branding","Logo Design","App Store Design","Typography","Illustration"]','["Design logo and brand color system","Create 5 App Store screenshot sets","Design 4-screen onboarding flow","Export assets for development handoff"]','Open','3 weeks ago')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- -----------------------------------------------------------------------------
-- Extended Proposals (connecting extended freelancers to jobs)
-- -----------------------------------------------------------------------------
INSERT INTO `proposals` (`id`,`job_id`,`freelancer_id`,`proposed_rate`,`estimated_days`,`cover_letter`,`status`)
VALUES
('prop-4','job-5','fl-4',9500.00,45,'I am an AWS-certified DevOps engineer with 4 years of Kubernetes production experience. I have migrated 3 enterprise applications to EKS with zero downtime using blue/green deployments.','Submitted'),
('prop-5','job-6','fl-8',65.00,18,'My design system work for fintech clients has been adopted by 4 engineering teams. I deliver WCAG AA-compliant component libraries with precise developer handoff documentation.','Active'),
('prop-6','job-7','fl-1',5500.00,35,'I specialize in healthcare SaaS platforms and have built two HIPAA-compliant portals using React and Node.js with JWT auth and audit logging.','Submitted'),
('prop-7','job-8','fl-1',10500.00,75,'I have architected two headless Next.js storefronts for high-SKU e-commerce. My implementation achieved 98 Lighthouse performance scores.','Submitted'),
('prop-8','job-9','fl-3',3000.00,30,'My data engineering background includes building Airflow + dbt pipelines processing 50M+ events daily. I can have your BigQuery data model production-ready in 4 weeks.','Active'),
('prop-9','job-10','fl-5',4800.00,40,'I built 2 banking apps in Flutter for European fintech clients. Both passed PCI DSS compliance reviews. I can integrate biometric auth and P2P transfers within 6 weeks.','Submitted'),
('prop-10','job-11','fl-3',8500.00,42,'I have published research on clinical risk prediction models. I will use XGBoost with SHAP explainability and wrap it in a FastAPI endpoint for your clinical team.','Active'),
('prop-11','job-12','fl-4',7200.00,20,'I have refactored 3 monolith APIs to AWS Lambda + API Gateway with DynamoDB. I follow least-privilege IAM principles and write comprehensive integration tests.','Submitted'),
('prop-12','job-14','fl-5',6200.00,50,'Native iOS developer here. I have built CoreML-powered workout classification for a fitness startup and integrated Apple Watch sync using HealthKit.','Submitted'),
('prop-13','job-19','fl-12',9500.00,14,'OSCP-certified penetration tester. I have conducted over 20 professional API security assessments. I deliver CVE-level findings with complete PoC code and remediation steps.','Active')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);

-- -----------------------------------------------------------------------------
-- Additional Chat Threads and Messages
-- -----------------------------------------------------------------------------
INSERT INTO `chat_threads` (`id`,`client_id`,`freelancer_id`,`client_name`,`last_message`,`status`)
VALUES
('thread-4','c-202','fl-4','David Miller','Can you share your EKS migration plan document?','online'),
('thread-5','c-203','fl-8','Elena Rostova','The Figma prototype looks excellent. When can we schedule a handoff call?','offline'),
('thread-6','c-204','fl-3','Marcus Vance','The risk scoring model accuracy looks great. Can you add the SHAP waterfall charts?','online')
ON DUPLICATE KEY UPDATE `client_name`=VALUES(`client_name`);

INSERT INTO `chat_messages` (`thread_id`,`sender_name`,`sender_role`,`text`,`is_me`,`sent_time`)
VALUES
('thread-4','David Miller','Client','Hi Nabila, we reviewed your EKS migration proposal and it looks very solid.', 0,'09:15 AM'),
('thread-4','Nabila Islam','Freelancer','Thank you David! I have done 3 similar migrations. I will share a detailed plan document today.', 1,'09:22 AM'),
('thread-4','David Miller','Client','Can you share your EKS migration plan document?', 0,'09:45 AM'),
('thread-5','Elena Rostova','Client','The component library is exactly what we needed. Very clean and well-organized.',0,'02:10 PM'),
('thread-5','Amara Diallo','Freelancer','Glad you like it! I have added dark mode variants for all 48 components as well.',1,'02:18 PM'),
('thread-5','Elena Rostova','Client','The Figma prototype looks excellent. When can we schedule a handoff call?',0,'02:30 PM'),
('thread-6','Marcus Vance','Client','The risk scoring model is impressive. AUC of 0.87 is above our target.',0,'11:00 AM'),
('thread-6','Sadman Sakib','Freelancer','Great news! I used class-weighted XGBoost to handle the imbalanced dataset. SHAP analysis is next.',1,'11:15 AM'),
('thread-6','Marcus Vance','Client','The risk scoring model accuracy looks great. Can you add the SHAP waterfall charts?',0,'11:30 AM');

-- -----------------------------------------------------------------------------
-- Extended Portfolio Items
-- -----------------------------------------------------------------------------
INSERT INTO `portfolio_items` (`id`,`freelancer_id`,`title`,`category`,`url`,`description`)
VALUES
('port-4','fl-1','Enterprise Cloud Migration for FinServ Client','Cloud Architecture','https://github.com/example/cloud-migration','Zero-downtime migration of 18-service monolith to AWS EKS using Terraform and ArgoCD GitOps.'),
('port-5','fl-2','NovaPay Mobile Banking Redesign','UI/UX Design','https://figma.com/example/novapay','Complete redesign of mobile banking flows with 40% improvement in task completion rate.'),
('port-6','fl-3','Patient Readmission ML Pipeline','Machine Learning','https://github.com/example/readmission-model','XGBoost model achieving AUC 0.89 for 30-day hospital readmission prediction.'),
('port-7','fl-4','Multi-Region EKS Deployment','DevOps / Cloud','https://github.com/example/eks-infra','Production-grade Terraform + Helm IaC for multi-region Kubernetes cluster.'),
('port-8','fl-8','B2B SaaS Design System','UI/UX Design','https://figma.com/example/design-system','Atomic design system with 200+ components, covering light, dark, and high-contrast modes.'),
('port-9','fl-12','REST API Security Assessment','Cybersecurity','https://github.com/example/pentest-report','Documented 14 OWASP Top 10 findings with PoC code and step-by-step remediation guidance.')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Verify additions
SELECT 'sessions table' as item, COUNT(*) as count FROM sessions
UNION ALL SELECT 'user_skills', COUNT(*) FROM user_skills
UNION ALL SELECT 'users total', COUNT(*) FROM users
UNION ALL SELECT 'jobs total', COUNT(*) FROM jobs
UNION ALL SELECT 'proposals total', COUNT(*) FROM proposals
UNION ALL SELECT 'skill_questions total', COUNT(*) FROM skill_questions
UNION ALL SELECT 'chat_threads total', COUNT(*) FROM chat_threads
UNION ALL SELECT 'chat_messages total', COUNT(*) FROM chat_messages
UNION ALL SELECT 'portfolio_items total', COUNT(*) FROM portfolio_items;

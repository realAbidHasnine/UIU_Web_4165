-- Sample data for the client portal. Run after schema.sql.

USE skillmatch;

INSERT INTO platform_settings (id, escrow_fee_rate, dispute_response_days) VALUES
  (1, 0.0300, 3);

INSERT INTO skill_categories (id, slug, name, subcategories, is_active) VALUES
  (1, 'web-development',   'Web Development',   'Frontend, Backend, Fullstack', 1),
  (2, 'ui-ux-design',      'UI/UX Design',      'Web, Mobile, Research',        1),
  (3, 'mobile-app-dev',    'Mobile App Dev',    'iOS, Android, React Native',   1),
  (4, 'data-science',      'Data Science',      'Machine Learning, Analytics',  1),
  (5, 'content-writing',   'Content Writing',   'Copywriting, Technical Docs',  1),
  (6, 'digital-marketing', 'Digital Marketing', 'SEO, Growth, Performance',     1);

-- Password hashes are placeholders. Before a real login, swap them for
-- password_hash('your-password', PASSWORD_DEFAULT).

INSERT INTO users
  (id, email, password_hash, role, status, avatar_url, email_verified_at)
VALUES
  (1, 'abid.hasina@flow.com', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'client', 'active', 'https://i.pravatar.cc/64?img=47', '2026-01-04 10:00:00'),

  (2, 'sabbir.hossain@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/112?img=12', '2026-02-11 09:15:00'),

  (3, 'ahsan@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=11', '2026-02-19 14:20:00'),

  (4, 'sadia.islam@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=44', '2026-03-02 11:05:00'),

  (5, 'esa.haque@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=13', '2026-03-08 16:40:00'),

  (6, 'moinul.islam@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=15', '2026-03-21 13:10:00'),

  (7, 'sadia.rahman@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/160?img=32', '2026-01-27 08:30:00'),

  (8, 'jomshed.das@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=52', '2026-04-01 10:00:00'),

  (9, 'moriam.sami@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=45', '2026-04-03 10:00:00'),

  (10, 'aniket.khan@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=68', '2026-04-05 10:00:00'),

  (11, 'robert.jenkins@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=59', '2026-04-07 10:00:00'),

  (12, 'alif.khan@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/96?img=68', '2026-01-15 10:00:00'),

  (13, 'nadia.islam@acmecorp.com', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'client', 'active', NULL, '2026-02-20 10:00:00'),

  (14, 'sabrina@startupx.co', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'client', 'active', NULL, '2026-03-15 10:00:00'),

  (15, 'abdullah@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=14', '2026-05-02 10:00:00'),

  (16, 'sumaiya.jahan@skillmatch.io', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'freelancer', 'active', 'https://i.pravatar.cc/64?img=26', '2026-06-11 10:00:00'),

  (17, 'admin@skillmatch.com', '$2y$10$PLACEHOLDER0000000000000000000000000000000000000000000',
   'admin', 'active', NULL, NULL);

INSERT INTO client_profiles
  (id, user_id, first_name, last_name, company_name, location, bio, hiring_needs, setup_complete)
VALUES
  (1, 1, 'Abida', 'Hasan', 'Flow', 'Dhaka, Bangladesh',
   'Independent client hiring pre-vetted, skill-tested freelancers. I focus on clear requirements, transparent milestones, and fair collaboration to ship high-quality products.',
   'Web development and digital services. Mostly React front-end work with headless commerce backends.',
   1),

  (2, 13, 'Nadia', 'Islam', 'Acme Corp', 'Dhaka, Bangladesh', NULL, NULL, 1),
  (3, 14, 'Sabrina', NULL, 'StartupX', 'Dhaka, Bangladesh', NULL, NULL, 1);

INSERT INTO freelancers
  (id, user_id, headline, location, github_url, experience_level,
   hourly_rate, overall_rating, completed_jobs_count,
   is_available_now, headline_score_pct, is_verified)
VALUES
  (1, 2, 'Full-Stack Web & Mobile Engineer', 'Dhaka, Bangladesh',
   'https://github.com/SabbirHossain', 'expert', 85.00, 4.90, 96, 1, 96, 1),

  (2, 3, 'Senior Full Stack Developer', 'Dhaka, Bangladesh',
   'https://github.com/AhsanDev', 'expert', 75.00, 4.70, 58, 0, 94, 1),

  (3, 4, 'Senior Frontend Engineer', 'Dhaka, Bangladesh',
   'https://github.com/SadiaIslam', 'expert', 55.00, 4.70, 41, 1, 92, 1),

  (4, 5, 'Full-Stack Developer', 'Dhaka, Bangladesh',
   'https://github.com/EsaHaque', 'intermediate', 40.00, 5.00, 63, 1, 88, 1),

  (5, 6, 'Python & Scraping Specialist', 'Dhaka, Bangladesh',
   'https://github.com/MoinulIslam', 'intermediate', 60.00, 4.60, 27, 0, 91, 1),

  (6, 7, 'Senior Full Stack Engineer', 'Dhaka, Bangladesh',
   'https://github.com/SadiaRahman', 'expert', 85.00, 5.00, 124, 1, 98, 1),

  (7, 8, 'Senior React Developer', 'Dhaka, Bangladesh',
   'https://github.com/JomshedDas', 'expert', 65.00, 4.90, 120, 1, 98, 1),

  (8, 9, 'UX/UI Product Designer', 'Dhaka, Bangladesh',
   'https://github.com/MoriamSami', 'intermediate', 85.00, 5.00, 84, 1, 95, 1),

  (9, 10, 'Full Stack Engineer', 'Dhaka, Bangladesh',
   'https://github.com/AniketKhan', 'expert', 75.00, 4.80, 210, 1, 92, 1),

  (10, 11, 'Technical Writer', 'Dhaka, Bangladesh',
   'https://github.com/RobertJenkins', 'entry', 45.00, 4.70, 56, 1, 88, 1),

  (11, 12, 'Senior Frontend Engineer & React Architect', 'Dhaka, Bangladesh',
   'https://github.com/AlifKhan', 'expert', 85.00, 5.00, 124, 1, 99, 1),

  (12, 15, 'Frontend Developer', 'Dhaka, Bangladesh',
   'https://github.com/AbdullahH', 'intermediate', 50.00, 4.80, 33, 0, 90, 1),

  (13, 16, 'UI Designer', 'Dhaka, Bangladesh',
   'https://github.com/SumaiyaJahan', 'intermediate', 60.00, 4.90, 45, 0, 93, 1);

INSERT INTO freelancer_skill_scores
  (freelancer_id, skill_name, score_pct, proficiency, certificate_no, last_retested_at)
VALUES
  (6, 'React',          98, 'advanced',   'SM-CERT-0412', '2026-03-14'),
  (6, 'Node.js',        95, 'advanced',   'SM-CERT-0413', '2026-03-14'),
  (6, 'TypeScript',     92, 'advanced',   'SM-CERT-0414', '2026-03-14'),
  (6, 'PostgreSQL',     88, 'proficient', 'SM-CERT-0415', '2026-03-14'),
  (11, 'React & Next.js', 98, 'advanced',   'SM-CERT-0417', '2026-03-14'),
  (11, 'Design Systems',  99, 'advanced',   'SM-CERT-0418', '2026-03-14'),
  (11, 'TypeScript',      94, 'advanced',   'SM-CERT-0419', '2026-03-14'),
  (11, 'GraphQL',         91, 'proficient', 'SM-CERT-0420', '2026-03-14'),
  (1, 'React',          96, 'advanced',   'SM-CERT-0388', '2026-02-20'),
  (1, 'React Native',   90, 'advanced',   'SM-CERT-0389', '2026-02-20'),
  (1, 'Node.js',        88, 'proficient', 'SM-CERT-0390', '2026-02-20'),
  (7, 'React',          98, 'advanced',   'SM-CERT-0401', '2026-03-02'),
  (7, 'TypeScript',     96, 'advanced',   'SM-CERT-0402', '2026-03-02'),
  (7, 'Node.js',        94, 'advanced',   'SM-CERT-0403', '2026-03-02'),
  (8, 'Figma',          98, 'advanced',   'SM-CERT-0404', '2026-03-05'),
  (8, 'Prototyping',    95, 'advanced',   'SM-CERT-0405', '2026-03-05'),
  (8, 'User Research',  82, 'proficient', 'SM-CERT-0406', '2026-03-05');

INSERT INTO freelancer_portfolio_items
  (freelancer_id, title, description, repo_url, technologies, position)
VALUES
  (6, 'E-Commerce Analytics Dashboard',
   'A high-throughput real-time analytics platform visualizing sales data across global regions.',
   'https://github.com/SadiaRahman', 'React,Node.js,PostgreSQL', 1),

  (6, 'Fintech API Gateway',
   'Microservices architecture handling secure transaction routing and rate limiting.',
   'https://github.com/SadiaRahman', 'Node.js,PostgreSQL', 2),

  (6, 'SaaS User Provisioning Tool',
   'Automated workflow tool for enterprise client onboarding and IAM management.',
   'https://github.com/SadiaRahman', 'React,TypeScript', 3),

  (11, 'Design System Rollout',
   'Token-driven component library adopted across four product surfaces.',
   'https://github.com/AlifKhan', 'React,TypeScript,Tailwind', 1);

INSERT INTO projects
  (id, client_id, category_id, title, description, budget_type, budget_min, budget_max,
   estimated_duration, status, cover_letter_required, portfolio_links_required,
   verified_skill_test_required, published_at, hired_at, completed_at)
VALUES
  (1, 1, 1, 'E-commerce Redesign & Migration',
   'Seeking a senior React developer to migrate our existing monolithic e-commerce platform to a modern headless architecture using Next.js and Shopify Storefront API. The project involves full frontend redesign based on provided Figma files, integrating payment gateways, and ensuring robust SEO and performance optimization.',
   'fixed', 4500.00, 4500.00, '3-6-months', 'in_progress', 1, 1, 0,
   '2026-07-18 09:00:00', '2026-08-01 11:30:00', NULL),

  (2, 1, 3, 'React Native Mobile App Development',
   'Cross-platform mobile app for our booking flow, built with React Native and a shared API layer.',
   'hourly', 45.00, 65.00, '1-3-months', 'open', 1, 1, 1,
   '2026-09-07 10:00:00', NULL, NULL),

  (3, 1, 2, 'Brand Identity & Logo Design',
   'Full brand refresh: logo suite, colour system, typography scale and a usage guide.',
   'fixed', 1200.00, 1200.00, 'less-than-1-month', 'completed', 1, 1, 0,
   '2023-09-12 10:00:00', '2023-09-20 12:00:00', '2023-10-12 15:00:00'),

  (4, 1, 4, 'Python Data Scraping Script',
   'A production-grade scraper for a public dataset, with retry handling and typed output.',
   'fixed', 450.00, 450.00, 'less-than-1-month', 'disputed', 1, 1, 0,
   '2026-08-22 09:00:00', '2026-08-29 10:15:00', NULL),

  (5, 1, 1, 'React Developer for SaaS',
   'Long-term React work maintaining our SaaS dashboard and design system.',
   'hourly', 55.00, 55.00, '3-6-months', 'in_progress', 0, 1, 1,
   '2026-08-25 09:00:00', '2026-09-01 09:00:00', NULL),

  (6, 1, 2, 'Logo Refresh',
   'Simplified logo refresh for the new brand direction.',
   'fixed', 500.00, 500.00, 'less-than-1-month', 'completed', 0, 1, 0,
   '2026-08-01 09:00:00', '2026-08-05 09:00:00', '2026-08-14 14:00:00'),

  (7, 1, 1, 'API Integration',
   'Integrate our billing provider and webhook reconciliation into the existing service.',
   'fixed', 1200.00, 1200.00, 'less-than-1-month', 'awaiting_approval', 1, 0, 1,
   '2026-10-01 08:00:00', NULL, NULL);

INSERT INTO project_skills (project_id, skill_name, position) VALUES
  (1, 'React', 1), (1, 'TypeScript', 2), (1, 'UI Design', 3),
  (2, 'React Native', 1), (2, 'TypeScript', 2), (2, 'Node.js', 3),
  (3, 'Illustrator', 1), (3, 'Branding', 2),
  (4, 'Python', 1), (4, 'Pandas', 2),
  (5, 'React', 1), (5, 'TypeScript', 2),
  (6, 'Branding', 1),
  (7, 'API Integration', 1), (7, 'Node.js', 2);

INSERT INTO screening_questions (project_id, position, question_text) VALUES
  (1, 1, 'Describe a similar project you shipped in the last year.'),
  (1, 2, 'What would you deliver in the first two weeks?'),
  (2, 1, 'Have you shipped a React Native app to both stores?'),
  (3, 1, 'Walk us through a brand system you have documented.'),
  (4, 1, 'How do you handle rate limits and retries in a long-running scrape?'),
  (5, 1, 'Describe your experience maintaining a production design system.'),
  (6, 1, 'What made the previous logo difficult to use in small sizes?'),
  (7, 1, 'Have you integrated a billing provider with webhook reconciliation before?');

INSERT INTO project_attachments
  (project_id, uploaded_by, filename, file_size_bytes, mime_type, storage_path, position)
VALUES
  (1, 1, 'migration-brief.pdf', 1843200, 'application/pdf',
   'uploads/project-1/migration-brief.pdf', 1),
  (1, 1, 'design-tokens.fig', 9437184, 'application/octet-stream',
   'uploads/project-1/design-tokens.fig', 2),
  (4, 1, 'target-sources.csv', 24576, 'text/csv',
   'uploads/project-4/target-sources.csv', 1);

INSERT INTO milestones
  (id, project_id, position, title, amount, status,
   due_at, submitted_at, approved_at, paid_at)
VALUES
  (1, 1, 1, 'Design mockups approved', 1500.00, 'paid',
   '2026-08-14', '2026-08-12 16:00:00', '2026-08-15 10:00:00', '2026-08-15 10:05:00'),
  (2, 1, 2, 'Frontend build complete', 1200.00, 'paid',
   '2026-08-27', '2026-08-26 15:30:00', '2026-08-28 09:00:00', '2026-08-28 09:10:00'),
  (3, 1, 3, 'Data migration complete', 800.00, 'submitted',
   '2026-09-04', '2026-09-05 11:20:00', NULL, NULL),
  (4, 1, 4, 'Launch and handover', 1000.00, 'posted',
   '2026-09-30', NULL, NULL, NULL),
  (5, 3, 1, 'Brand guidelines delivered', 1200.00, 'paid',
   '2023-10-11', '2023-10-10 12:00:00', '2023-10-11 09:00:00', '2023-10-11 09:10:00'),
  (6, 4, 1, 'Scraper script and sample data', 450.00, 'submitted',
   '2026-09-08', '2026-09-09 10:40:00', NULL, NULL),
  (7, 6, 1, 'Final delivery', 500.00, 'paid',
   '2026-08-13', '2026-08-12 18:00:00', '2026-08-14 10:00:00', '2026-08-14 10:05:00');

INSERT INTO proposals
  (id, project_id, freelancer_id, rate_amount, rate_unit, cover_letter,
   match_score_pct, status, submitted_at, decided_at)
VALUES
  (1, 1, 3, 45.00, 'hour',
   'I have extensive experience with e-commerce migrations and React. I have successfully transitioned 3 similar platforms to headless architectures using...',
   96, 'accepted', '2026-07-22 09:12:00', '2026-08-01 11:30:00'),

  (2, 1, 4, 55.00, 'hour',
   'Senior Frontend Engineer specializing in Next.js and Shopify integrations. I can handle the entire migration process smoothly.',
   92, 'viewed', '2026-07-23 14:05:00', NULL),

  (3, 1, 5, 40.00, 'hour',
   'Hi! I am a full-stack developer with a strong focus on React and modern web architectures.',
   88, 'viewed', '2026-07-25 08:40:00', NULL),

  (4, 4, 6, 60.00, 'hour',
   'I build scrapers daily and have shipped three production pipelines with retry and backoff.',
   91, 'accepted', '2026-08-25 10:00:00', '2026-08-29 10:15:00'),

  (5, 2, 3, 60.00, 'hour',
   'Two React Native apps shipped to both stores last year.',
   90, 'pending', '2026-09-08 09:00:00', NULL),

  (6, 2, 5, 55.00, 'hour',
   'Happy to take this on. Available from this week.',
   85, 'pending', '2026-09-09 12:30:00', NULL),

  (7, 3, 7, 1200.00, 'project',
   'Brand systems are my core work. Here is a comparable engagement.',
   97, 'accepted', '2023-09-14 10:00:00', '2023-09-20 12:00:00'),

  (8, 6, 7, 500.00, 'project',
   'Simplified mark delivered in three variants with a usage sheet.',
   98, 'accepted', '2026-08-02 10:00:00', '2026-08-05 09:00:00'),

  (9, 5, 4, 55.00, 'hour',
   'I maintain a design system and a dashboard of similar scale.',
   92, 'accepted', '2026-08-26 10:00:00', '2026-09-01 09:00:00'),

  (10, 7, 1, 1200.00, 'project',
   'Billing provider integrations are routine for me, including reconciliation.',
   94, 'pending', '2026-10-01 09:00:00', NULL);

INSERT INTO proposal_screening_answers (proposal_id, screening_question_id, answer_text) VALUES
  (1, 1, 'I led the headless migration of a 40k-SKU storefront from a Magento monolith in 14 weeks.'),
  (1, 2, 'Discovery and a clickable shell of the new storefront, plus the API contract.'),
  (4, 5, 'Exponential backoff with a circuit breaker, plus idempotent checkpoints so a restart resumes.'),
  (7, 3, 'Documented a 60-page system with token tiers and enforced it in code review.'),
  (8, 6, 'It collapsed below 24px and the fine detail muddied at favicon sizes.');

INSERT INTO milestone_submissions
  (milestone_id, submitted_by, filename, file_size_bytes, mime_type, storage_path, notes, submitted_at)
VALUES
  (6, 6, 'final_script.py', 24576, 'text/x-python', 'uploads/milestone-6/final_script.py',
   'Includes exponential backoff and idempotent checkpoints. Run with --config config.yaml.',
   '2026-09-09 10:38:00'),

  (6, 6, 'data_sample.csv', 1258291, 'text/csv', 'uploads/milestone-6/data_sample.csv',
   '200 rows sampled from the full output.', '2026-09-09 10:39:00'),

  (3, 3, 'migration_report.md', 8421, 'text/markdown', 'uploads/milestone-3/migration_report.md',
   'Catalogue diff and redirect map.', '2026-09-05 11:18:00');

INSERT INTO milestone_feedback (milestone_id, submission_id, author_id, body, created_at) VALUES
  (6, 1, 1, 'Great job on the script, but could you clarify how to run it against a paginated source.', '2026-09-10 09:30:00'),
  (3, 3, 1, 'Catalogue diff looks right. Please add the redirect map to the handover docs.', '2026-10-01 08:45:00');

-- escrow_fee and total_charged are worked out by a trigger, not typed here.

INSERT INTO escrow_payments
  (milestone_id, project_id, freelancer_id, receipt_no, amount, status, held_at, released_at)
VALUES
  (1, 1, 3, 'SM-2026-0752', 1500.00, 'released', '2026-08-01 11:30:00', '2026-08-15 10:05:00'),
  (2, 1, 3, 'SM-2026-0791', 1200.00, 'released', '2026-08-01 11:30:00', '2026-08-28 09:10:00'),
  (3, 1, 3, 'SM-2026-0802', 800.00,  'held',     '2026-08-01 11:30:00', NULL),
  (6, 4, 6, 'SM-2026-0847', 450.00,  'held',     '2026-08-29 10:15:00', NULL),
  (7, 6, 7, 'SM-2026-0718', 500.00,  'released', '2026-08-05 09:00:00', '2026-08-14 14:05:00'),
  (5, 3, 7, 'SM-2023-0311', 1200.00, 'released', '2023-09-20 12:00:00', '2023-10-11 09:10:00');

INSERT INTO disputes
  (dispute_no, project_id, milestone_id, raised_by, reason, description,
   desired_outcome, escrow_locked_amount, status, filed_at)
VALUES
  ('DIS-2026-0117', 4, 6, 1, 'work_quality',
   'The scraper runs but returns incomplete rows on the paginated sources and there is no error handling when a page shape changes.',
   'revision', 450.00, 'under_review', '2026-09-09 14:20:00');

INSERT INTO dispute_events (dispute_id, event_type, actor_user_id, note, created_at) VALUES
  (1, 'filed',        1, 'You requested a revision of Milestone 1.',       '2026-09-09 14:20:00'),
  (1, 'under_review', 1, 'Moinul Islam has 3 days to respond with a fix.', '2026-09-09 14:20:01');

INSERT INTO reviews
  (reviewer_id, freelancer_id, project_id, milestone_id, reviewer_title, reviewer_company,
   overall_rating, communication_rating, quality_of_work_rating, timeliness_rating, body, created_at)
VALUES
  (1, 6, 4, 6, NULL, NULL,
   4, 5, 3, 4,
   'Great effort on the script, but it needs real error handling before I can run it against production sources.',
   '2026-09-10 10:00:00'),

  (13, 7, NULL, NULL, 'Tech Lead', 'Acme Corp',
   5, 5, 5, 5,
   'Sadia delivered the backend refactor two weeks ahead of schedule. Her code quality is exceptional and she communicated clearly throughout the entire process.',
   '2026-05-02 11:00:00'),

  (14, 7, NULL, NULL, 'Founder', 'StartupX',
   5, 5, 5, 5,
   'Incredibly talented full-stack engineer. She translated our vague requirements into a robust, scalable product.',
   '2026-06-18 15:30:00');

INSERT INTO conversations
  (id, project_id, client_id, freelancer_id, subject, last_message_at, last_message_preview)
VALUES
  (1, 1, 1, 2,  'Dashboard redesign',              NOW(),                 'Mainly regarding the timeline charts...'),
  (2, 1, 1, 15, 'E-commerce Redesign & Migration', '2026-10-01 09:00:00', 'Thanks for the feedback. I have updated the...'),
  (3, 6, 1, 16, 'Logo Refresh',                   '2026-09-24 16:20:00', 'Project completed and final files attached.');

INSERT INTO messages (conversation_id, sender_id, body, sent_at, read_at) VALUES
  (1, 2, 'Hi there! I have reviewed the brief for the dashboard redesign. I have a few quick questions about the data visualization requirements.',
   DATE_ADD(CURDATE(), INTERVAL 10 HOUR) + INTERVAL 30 MINUTE, NULL),

  (1, 1, 'Great, thanks for taking a look. What specific aspects of the data visualization need clarification?',
   DATE_ADD(CURDATE(), INTERVAL 10 HOUR) + INTERVAL 35 MINUTE,
   DATE_ADD(CURDATE(), INTERVAL 10 HOUR) + INTERVAL 36 MINUTE),

  (1, 2, 'Mainly regarding the timeline charts. Do you prefer a unified view or separate widgets for each metric? I will send over the updated wireframes showing both options shortly.',
   DATE_ADD(CURDATE(), INTERVAL 10 HOUR) + INTERVAL 42 MINUTE, NULL),

  (2, 1, 'The redirect map looks right. One thing to change before we close this out.',
   '2026-10-01 08:50:00', '2026-10-01 09:02:00'),

  (2, 15, 'Thanks for the feedback. I have updated the catalogue importer and added the redirect map you asked for.',
   '2026-10-01 09:00:00', NULL),

  (3, 16, 'Project completed and final files attached. Let me know if you need the source exports.',
   '2026-09-24 16:20:00', '2026-09-24 16:40:00');

INSERT INTO notifications
  (user_id, type, title, body, related_type, related_id, is_read, read_at, created_at)
VALUES
  (1, 'proposal_accepted', 'Proposal Accepted',
   'You accepted Ahsan''s proposal for ''E-commerce Redesign & Migration''.',
   'project', 1, 0, NULL, DATE_SUB(NOW(), INTERVAL 2 MINUTE)),

  (1, 'new_message', 'New Message',
   'Sabbir Hossain sent you a message',
   'message', 1, 1, DATE_SUB(NOW(), INTERVAL 55 MINUTE), DATE_SUB(NOW(), INTERVAL 1 HOUR)),

  (1, 'email_verification', 'Action Required',
   'Please verify your email address to continue',
   NULL, NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),

  (1, 'proposal_received', 'New Proposal',
   'Sadia Islam submitted a proposal for ''E-commerce Redesign & Migration''.',
   'proposal', 2, 1, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR)),

  (1, 'milestone_submitted', 'Milestone Submitted',
   'Moinul Islam submitted Milestone 1 for ''Python Data Scraping Script''. Review the work.',
   'milestone', 6, 0, NULL, DATE_SUB(NOW(), INTERVAL 7 HOUR)),

  (1, 'dispute_filed', 'Dispute Filed',
   'DIS-2026-0117 is under review. The $450.00 stays locked in escrow.',
   'dispute', 1, 0, NULL, DATE_SUB(NOW(), INTERVAL 8 HOUR));
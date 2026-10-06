DROP DATABASE IF EXISTS skillmatch;
CREATE DATABASE skillmatch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skillmatch;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client','freelancer','admin') NOT NULL,
  status ENUM('active','flagged','suspended') NOT NULL DEFAULT 'active',
  avatar_url VARCHAR(500) NULL,
  email_verified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE client_profiles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NULL,
  company_name VARCHAR(160) NULL,
  location VARCHAR(160) NULL,
  bio TEXT NULL,
  hiring_needs TEXT NULL,
  setup_complete TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_client_profiles_user (user_id),
  CONSTRAINT fk_cp_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE skill_categories (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  subcategories VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sc_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE platform_settings (
  id TINYINT UNSIGNED NOT NULL,
  escrow_fee_rate DECIMAL(5,4) NOT NULL DEFAULT 0.0300,
  dispute_response_days TINYINT UNSIGNED NOT NULL DEFAULT 3,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE freelancers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  headline VARCHAR(200) NULL,
  location VARCHAR(160) NULL,
  github_url VARCHAR(255) NULL,
  experience_level ENUM('entry','intermediate','expert') NULL,
  hourly_rate DECIMAL(10,2) NULL,
  overall_rating DECIMAL(3,2) NULL,
  completed_jobs_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_available_now TINYINT(1) NOT NULL DEFAULT 0,
  headline_score_pct TINYINT UNSIGNED NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_f_user (user_id),
  KEY idx_f_browse (is_available_now, headline_score_pct),
  CONSTRAINT fk_f_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE freelancer_skill_scores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  freelancer_id BIGINT UNSIGNED NOT NULL,
  skill_name VARCHAR(120) NOT NULL,
  score_pct TINYINT UNSIGNED NOT NULL,
  proficiency ENUM('proficient','advanced') NULL,
  certificate_no VARCHAR(20) NULL,
  last_retested_at DATE NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fss (freelancer_id, skill_name),
  KEY idx_fss_skill (skill_name, score_pct),
  CONSTRAINT fk_fss_f FOREIGN KEY (freelancer_id) REFERENCES freelancers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE freelancer_portfolio_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  freelancer_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  repo_url VARCHAR(500) NULL,
  technologies VARCHAR(500) NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pi_f (freelancer_id, position),
  CONSTRAINT fk_pi_f FOREIGN KEY (freelancer_id) REFERENCES freelancers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  category_id SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  budget_type ENUM('fixed','hourly') NOT NULL DEFAULT 'fixed',
  budget_min DECIMAL(12,2) NULL,
  budget_max DECIMAL(12,2) NULL,
  estimated_duration ENUM('less-than-1-month','1-3-months','3-6-months') NULL,
  status ENUM('draft','open','in_progress','awaiting_approval','completed','disputed','cancelled') NOT NULL DEFAULT 'draft',
  cover_letter_required TINYINT(1) NOT NULL DEFAULT 0,
  portfolio_links_required TINYINT(1) NOT NULL DEFAULT 0,
  verified_skill_test_required TINYINT(1) NOT NULL DEFAULT 0,
  attachment_limit TINYINT UNSIGNED NOT NULL DEFAULT 5,
  wizard_step TINYINT UNSIGNED NULL,
  published_at DATETIME NULL,
  hired_at DATETIME NULL,
  completed_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_p_client (client_id, status),
  KEY idx_p_cat (category_id, status),
  KEY idx_p_budget (budget_min, budget_max),
  CONSTRAINT ck_p_wizard CHECK (wizard_step IS NULL OR wizard_step BETWEEN 1 AND 4),
  CONSTRAINT fk_p_client FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_p_cat FOREIGN KEY (category_id) REFERENCES skill_categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE project_skills (
  project_id BIGINT UNSIGNED NOT NULL,
  skill_name VARCHAR(120) NOT NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (project_id, skill_name),
  KEY idx_ps_name (skill_name),
  CONSTRAINT fk_ps_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE screening_questions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  question_text VARCHAR(500) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sq (project_id, position),
  CONSTRAINT fk_sq_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_attachments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL,
  mime_type VARCHAR(120) NULL,
  storage_path VARCHAR(500) NOT NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pa_p (project_id, position),
  CONSTRAINT fk_pa_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_u FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE milestones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('posted','submitted','revision_requested','approved','paid','disputed') NOT NULL DEFAULT 'posted',
  due_at DATE NULL,
  submitted_at DATETIME NULL,
  approved_at DATETIME NULL,
  paid_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_m (project_id, position),
  KEY idx_m_status (status),
  CONSTRAINT fk_m_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE milestone_submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  milestone_id BIGINT UNSIGNED NOT NULL,
  submitted_by BIGINT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL,
  mime_type VARCHAR(120) NULL,
  storage_path VARCHAR(500) NOT NULL,
  notes TEXT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ms_m (milestone_id),
  CONSTRAINT fk_ms_m FOREIGN KEY (milestone_id) REFERENCES milestones (id) ON DELETE CASCADE,
  CONSTRAINT fk_ms_u FOREIGN KEY (submitted_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE milestone_feedback (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  milestone_id BIGINT UNSIGNED NOT NULL,
  submission_id BIGINT UNSIGNED NULL,
  author_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mf_m (milestone_id),
  CONSTRAINT fk_mf_m FOREIGN KEY (milestone_id) REFERENCES milestones (id) ON DELETE CASCADE,
  CONSTRAINT fk_mf_s FOREIGN KEY (submission_id) REFERENCES milestone_submissions (id) ON DELETE CASCADE,
  CONSTRAINT fk_mf_a FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE proposals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  freelancer_id BIGINT UNSIGNED NOT NULL,
  rate_amount DECIMAL(10,2) NOT NULL,
  rate_unit ENUM('hour','project') NOT NULL DEFAULT 'hour',
  cover_letter TEXT NULL,
  match_score_pct TINYINT UNSIGNED NULL,
  status ENUM('pending','viewed','accepted','declined','withdrawn') NOT NULL DEFAULT 'pending',
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prop (project_id, freelancer_id),
  KEY idx_prop_status (project_id, status),
  KEY idx_prop_f (freelancer_id, status),
  CONSTRAINT fk_prop_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_prop_f FOREIGN KEY (freelancer_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE proposal_screening_answers (
  proposal_id BIGINT UNSIGNED NOT NULL,
  screening_question_id BIGINT UNSIGNED NOT NULL,
  answer_text TEXT NOT NULL,
  PRIMARY KEY (proposal_id, screening_question_id),
  CONSTRAINT fk_psa_p FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE,
  CONSTRAINT fk_psa_q FOREIGN KEY (screening_question_id) REFERENCES screening_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE escrow_payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  milestone_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  freelancer_id BIGINT UNSIGNED NOT NULL,
  receipt_no VARCHAR(20) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  escrow_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_charged DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('held','frozen','released','partially_refunded','refunded') NOT NULL DEFAULT 'held',
  held_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  released_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ep_receipt (receipt_no),
  UNIQUE KEY uq_ep_m (milestone_id),
  KEY idx_ep_ledger (project_id, status, held_at),
  KEY idx_ep_month (status, released_at),
  CONSTRAINT fk_ep_m FOREIGN KEY (milestone_id) REFERENCES milestones (id) ON DELETE CASCADE,
  CONSTRAINT fk_ep_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_ep_f FOREIGN KEY (freelancer_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT ck_ep_released CHECK (status <> 'released' OR released_at IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE disputes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dispute_no VARCHAR(20) NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  milestone_id BIGINT UNSIGNED NOT NULL,
  raised_by BIGINT UNSIGNED NOT NULL,
  reason ENUM('work_quality','missed_deadline','communication_breakdown','other') NOT NULL,
  description TEXT NOT NULL,
  desired_outcome ENUM('revision','partial_refund','full_refund') NOT NULL,
  partial_refund_amount DECIMAL(12,2) NULL,
  escrow_locked_amount DECIMAL(12,2) NOT NULL,
  status ENUM('filed','under_review','resolved','escalated') NOT NULL DEFAULT 'filed',
  filed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  response_due_at DATETIME NULL,
  resolved_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_d_no (dispute_no),
  KEY idx_d_proj (project_id, status),
  CONSTRAINT fk_d_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_d_m FOREIGN KEY (milestone_id) REFERENCES milestones (id) ON DELETE CASCADE,
  CONSTRAINT fk_d_u FOREIGN KEY (raised_by) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT ck_d_partial CHECK (desired_outcome <> 'partial_refund' OR partial_refund_amount IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE dispute_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dispute_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('filed','under_review','freelancer_response','revision_submitted','partial_refund_agreed','resolved','escalated') NOT NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_de_d (dispute_id, created_at),
  CONSTRAINT fk_de_d FOREIGN KEY (dispute_id) REFERENCES disputes (id) ON DELETE CASCADE,
  CONSTRAINT fk_de_a FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  freelancer_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NULL,
  milestone_id BIGINT UNSIGNED NULL,
  reviewer_title VARCHAR(120) NULL,
  reviewer_company VARCHAR(160) NULL,
  overall_rating TINYINT UNSIGNED NOT NULL,
  communication_rating TINYINT UNSIGNED NULL,
  quality_of_work_rating TINYINT UNSIGNED NULL,
  timeliness_rating TINYINT UNSIGNED NULL,
  body TEXT NULL,
  verified_engagement TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rev_m (milestone_id),
  KEY idx_rev_f (freelancer_id, created_at),
  CONSTRAINT fk_rev_r FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_f FOREIGN KEY (freelancer_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT fk_rev_m FOREIGN KEY (milestone_id) REFERENCES milestones (id) ON DELETE SET NULL,
  CONSTRAINT ck_rev_rating CHECK (overall_rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE conversations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  freelancer_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(200) NOT NULL,
  last_message_at DATETIME NULL,
  last_message_preview VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_conv (project_id, client_id, freelancer_id),
  KEY idx_conv_recent (client_id, last_message_at),
  CONSTRAINT fk_conv_p FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT fk_conv_c FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_f FOREIGN KEY (freelancer_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_msg (conversation_id, sent_at),
  KEY idx_msg_unread (conversation_id, read_at),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_s FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type ENUM('proposal_received','proposal_accepted','new_message','action_required','email_verification','new_job_match','milestone_submitted','milestone_paid','dispute_filed','dispute_updated','review_received','project_published') NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(500) NULL,
  related_type VARCHAR(40) NULL,
  related_id BIGINT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif (user_id, is_read, created_at),
  CONSTRAINT fk_notif_u FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT ck_notif_read CHECK (is_read = 1 OR read_at IS NULL)
) ENGINE=InnoDB;

DELIMITER $$

CREATE TRIGGER trg_escrow_before_insert
BEFORE INSERT ON escrow_payments
FOR EACH ROW
BEGIN
  DECLARE v_rate DECIMAL(5,4);
  SELECT escrow_fee_rate INTO v_rate FROM platform_settings WHERE id = 1;
  SET NEW.escrow_fee = ROUND(NEW.amount * v_rate, 2);
  SET NEW.total_charged = NEW.amount + NEW.escrow_fee;
END$$

CREATE TRIGGER trg_dispute_before_insert
BEFORE INSERT ON disputes
FOR EACH ROW
BEGIN
  DECLARE v_days TINYINT UNSIGNED;
  SELECT dispute_response_days INTO v_days FROM platform_settings WHERE id = 1;
  SET NEW.response_due_at = DATE_ADD(NEW.filed_at, INTERVAL v_days DAY);
END$$

CREATE TRIGGER trg_dispute_after_insert
AFTER INSERT ON disputes
FOR EACH ROW
BEGIN
  UPDATE milestones SET status = 'disputed'
   WHERE id = NEW.milestone_id AND status IN ('posted','submitted','revision_requested','approved');
  UPDATE escrow_payments SET status = 'frozen'
   WHERE milestone_id = NEW.milestone_id AND status = 'held';
END$$

DELIMITER ;
-- =====================================================================
-- Virtual Career Simulation System — Database Schema
-- Students explore careers via interactive simulations, take career
-- quizzes, complete an interest assessment (RIASEC-style), and receive
-- personalized career recommendations based on interests + performance.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS career_sim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE career_sim;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  profile_image VARCHAR(255) NULL,
  resume_path VARCHAR(255) NULL,
  resume_updated_at TIMESTAMP NULL,
  role ENUM('student','counselor','content_manager','admin') NOT NULL DEFAULT 'student',
  status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  education_level ENUM('secondary','undergraduate','graduate','other') NULL,
  date_of_birth DATE NULL,
  email_verified_at TIMESTAMP NULL,
  last_login_at TIMESTAMP NULL,
  profile_completed_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status),
  KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE otp_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(6) NOT NULL,
  purpose ENUM('registration','password_reset') NOT NULL DEFAULT 'registration',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_otp_codes_user_purpose (user_id, purpose),
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(100) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_resets_token (token),
  KEY idx_password_resets_user (user_id),
  CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CAREER CATALOG
-- ---------------------------------------------------------------------
CREATE TABLE career_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL,
  icon VARCHAR(50) NULL,
  UNIQUE KEY uq_career_categories_name (name),
  UNIQUE KEY uq_career_categories_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE careers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  summary VARCHAR(500) NULL,
  description TEXT NULL,
  average_salary DECIMAL(12,2) NULL,
  growth_outlook ENUM('declining','stable','growing','high_growth') NULL,
  image VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_careers_slug (slug),
  KEY idx_careers_category (category_id),
  FULLTEXT KEY ft_careers_title_desc (title, summary, description),
  CONSTRAINT fk_careers_category FOREIGN KEY (category_id) REFERENCES career_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  UNIQUE KEY uq_skills_name (name)
) ENGINE=InnoDB;

CREATE TABLE career_skills (
  career_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  importance ENUM('nice_to_have','important','essential') NOT NULL DEFAULT 'important',
  PRIMARY KEY (career_id, skill_id),
  CONSTRAINT fk_career_skills_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE,
  CONSTRAINT fk_career_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SIMULATIONS (interactive workplace-task modules per career)
-- ---------------------------------------------------------------------
CREATE TABLE career_simulations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  career_id INT UNSIGNED NOT NULL,
  quiz_id BIGINT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  difficulty_level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  estimated_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  cover_image VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_simulations_career (career_id),
  KEY idx_simulations_quiz (quiz_id),
  CONSTRAINT fk_simulations_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE simulation_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  simulation_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  instructions TEXT NOT NULL,
  task_type ENUM('scenario_choice','case_study','practical_exercise','reflection') NOT NULL DEFAULT 'scenario_choice',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_score SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_simulation_tasks_simulation (simulation_id, sort_order),
  CONSTRAINT fk_simulation_tasks_simulation FOREIGN KEY (simulation_id) REFERENCES career_simulations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- for scenario_choice / case_study tasks: multiple options, each scored
CREATE TABLE task_options (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id BIGINT UNSIGNED NOT NULL,
  option_text VARCHAR(500) NOT NULL,
  score_value SMALLINT NOT NULL DEFAULT 0,
  feedback_text VARCHAR(500) NULL,
  is_best_practice TINYINT(1) NOT NULL DEFAULT 0,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_task_options_task (task_id),
  CONSTRAINT fk_task_options_task FOREIGN KEY (task_id) REFERENCES simulation_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_simulation_progress (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  simulation_id BIGINT UNSIGNED NOT NULL,
  status ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  total_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_possible_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_progress_user_simulation (user_id, simulation_id),
  KEY idx_progress_simulation (simulation_id),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_simulation FOREIGN KEY (simulation_id) REFERENCES career_simulations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_task_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  task_id BIGINT UNSIGNED NOT NULL,
  selected_option_id BIGINT UNSIGNED NULL,
  response_text TEXT NULL,
  ai_feedback TEXT NULL,
  counselor_score SMALLINT UNSIGNED NULL,
  counselor_feedback TEXT NULL,
  graded_by BIGINT UNSIGNED NULL,
  graded_at TIMESTAMP NULL,
  score_earned SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  time_spent_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_task_attempts_user_task (user_id, task_id),
  CONSTRAINT fk_task_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_task_attempts_task FOREIGN KEY (task_id) REFERENCES simulation_tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_task_attempts_option FOREIGN KEY (selected_option_id) REFERENCES task_options(id) ON DELETE SET NULL
  ,CONSTRAINT fk_task_attempt_grade_user FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE simulation_feedback (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  simulation_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(500) NULL,
  moderation_status ENUM('new','flagged','dismissed','responded') NOT NULL DEFAULT 'new',
  admin_response VARCHAR(500) NULL,
  moderated_by BIGINT UNSIGNED NULL,
  moderated_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_simulation_feedback_rating CHECK (rating BETWEEN 1 AND 5),
  UNIQUE KEY uq_feedback_user_simulation (user_id, simulation_id),
  CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_feedback_simulation FOREIGN KEY (simulation_id) REFERENCES career_simulations(id) ON DELETE CASCADE
  ,CONSTRAINT fk_feedback_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- QUIZZES (career-specific knowledge checks)
-- ---------------------------------------------------------------------
CREATE TABLE quizzes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  career_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description VARCHAR(500) NULL,
  pass_score SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  time_limit_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_quizzes_career (career_id),
  UNIQUE KEY uq_quizzes_id_career (id,career_id),
  CONSTRAINT fk_quizzes_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id BIGINT UNSIGNED NOT NULL,
  question_text VARCHAR(500) NOT NULL,
  question_type ENUM('single_choice','multiple_choice','true_false') NOT NULL DEFAULT 'single_choice',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  points SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  KEY idx_quiz_questions_quiz (quiz_id, sort_order),
  CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_options (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id BIGINT UNSIGNED NOT NULL,
  option_text VARCHAR(300) NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_quiz_options_question (question_id),
  CONSTRAINT fk_quiz_options_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  quiz_id BIGINT UNSIGNED NOT NULL,
  score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  KEY idx_quiz_attempts_user (user_id),
  KEY idx_quiz_attempts_quiz (quiz_id),
  CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_attempts_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  selected_option_id BIGINT UNSIGNED NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  points_earned SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_quiz_answers_attempt (attempt_id),
  CONSTRAINT fk_quiz_answers_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_answers_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_answers_option FOREIGN KEY (selected_option_id) REFERENCES quiz_options(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INTEREST ASSESSMENT (RIASEC-style: Realistic, Investigative, Artistic,
-- Social, Enterprising, Conventional) — drives recommendations alongside
-- simulation/quiz performance.
-- ---------------------------------------------------------------------
CREATE TABLE interest_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_text VARCHAR(300) NOT NULL,
  interest_type ENUM('realistic','investigative','artistic','social','enterprising','conventional') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE user_interest_responses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  response_value TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_interest_response_value CHECK (response_value BETWEEN 1 AND 5),
  UNIQUE KEY uq_interest_response_user_question (user_id, question_id),
  CONSTRAINT fk_interest_responses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_interest_responses_question FOREIGN KEY (question_id) REFERENCES interest_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- aggregated score per RIASEC type per user, recomputed after each assessment
CREATE TABLE user_interest_scores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  interest_type ENUM('realistic','investigative','artistic','social','enterprising','conventional') NOT NULL,
  score DECIMAL(5,2) NOT NULL DEFAULT 0,
  computed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_interest_scores_user_type (user_id, interest_type),
  CONSTRAINT fk_interest_scores_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- how strongly each career maps to each RIASEC dimension, used for matching
CREATE TABLE career_interest_mapping (
  career_id INT UNSIGNED NOT NULL,
  interest_type ENUM('realistic','investigative','artistic','social','enterprising','conventional') NOT NULL,
  weight DECIMAL(4,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (career_id, interest_type),
  CONSTRAINT fk_career_interest_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RECOMMENDATIONS
-- ---------------------------------------------------------------------
CREATE TABLE career_recommendations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  career_id INT UNSIGNED NOT NULL,
  match_score DECIMAL(5,2) NOT NULL,
  based_on ENUM('interest','performance','combined') NOT NULL DEFAULT 'combined',
  explanation TEXT NULL,
  generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_recommendations_user (user_id, match_score),
  CONSTRAINT fk_recommendations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_recommendations_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- GAMIFICATION (badges/achievements to encourage exploration)
-- ---------------------------------------------------------------------
CREATE TABLE badges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  icon VARCHAR(50) NULL,
  criteria_type ENUM('simulation_complete','quiz_pass','career_count','streak') NOT NULL,
  criteria_value SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  UNIQUE KEY uq_badges_name (name)
) ENGINE=InnoDB;

CREATE TABLE user_badges (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  badge_id INT UNSIGNED NOT NULL,
  earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_badges (user_id, badge_id),
  CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTIFICATIONS & ADMIN AUDIT LOG
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL,
  data VARCHAR(500) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user_unread (user_id, is_read),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admin_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(50) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  details VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_admin_logs_admin (admin_id),
  CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE career_simulations
  ADD CONSTRAINT fk_simulations_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_simulations_quiz_career FOREIGN KEY (quiz_id,career_id) REFERENCES quizzes(id,career_id) ON DELETE RESTRICT;

CREATE TABLE user_education (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  institution VARCHAR(150) NOT NULL,
  qualification VARCHAR(150) NOT NULL,
  field_of_study VARCHAR(150) NULL,
  start_year SMALLINT UNSIGNED NULL,
  end_year SMALLINT UNSIGNED NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_education_user (user_id),
  CONSTRAINT fk_education_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_opportunities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  career_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  company VARCHAR(150) NOT NULL,
  location VARCHAR(150) NULL,
  employment_type ENUM('internship','learnership','part_time','full_time','contract') NOT NULL,
  description TEXT NOT NULL,
  requirements TEXT NULL,
  closing_date DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_jobs_active_close (is_active,closing_date),
  CONSTRAINT fk_jobs_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE SET NULL,
  CONSTRAINT fk_jobs_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE job_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  resume_path VARCHAR(255) NOT NULL,
  cover_letter TEXT NULL,
  status ENUM('submitted','under_review','shortlisted','unsuccessful','accepted') NOT NULL DEFAULT 'submitted',
  reviewer_feedback TEXT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at TIMESTAMP NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_job_application (job_id,user_id),
  KEY idx_applications_user_status (user_id,status),
  CONSTRAINT fk_application_job FOREIGN KEY (job_id) REFERENCES job_opportunities(id) ON DELETE CASCADE,
  CONSTRAINT fk_application_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_application_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- COUNSELLING & GUIDANCE (Career Guidance System)
-- ---------------------------------------------------------------------
CREATE TABLE counselling_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  counselor_id BIGINT UNSIGNED NULL,
  topic VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  preferred_date DATE NULL,
  preferred_time_slot VARCHAR(50) NULL,
  status ENUM('pending','responded','scheduled','completed','cancelled') NOT NULL DEFAULT 'pending',
  counselor_response TEXT NULL,
  responded_by BIGINT UNSIGNED NULL,
  responded_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_counsel_req_student (student_id),
  KEY idx_counsel_req_counselor (counselor_id),
  KEY idx_counsel_req_status (status),
  CONSTRAINT fk_counsel_req_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_counsel_req_counselor FOREIGN KEY (counselor_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_counsel_req_responder FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE counselling_appointments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT UNSIGNED NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  counselor_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  appointment_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  meeting_type ENUM('online_video','in_person','phone_call') NOT NULL DEFAULT 'online_video',
  meeting_link VARCHAR(255) NULL,
  location_details VARCHAR(255) NULL,
  status ENUM('scheduled','completed','cancelled','rescheduled') NOT NULL DEFAULT 'scheduled',
  counselor_notes TEXT NULL,
  action_plan TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_counsel_appt_student (student_id),
  KEY idx_counsel_appt_counselor (counselor_id),
  KEY idx_counsel_appt_date (appointment_date),
  KEY idx_counsel_appt_status (status),
  CONSTRAINT fk_counsel_appt_req FOREIGN KEY (request_id) REFERENCES counselling_requests(id) ON DELETE SET NULL,
  CONSTRAINT fk_counsel_appt_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_counsel_appt_counselor FOREIGN KEY (counselor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE counsellor_pathway_recommendations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  counselor_id BIGINT UNSIGNED NOT NULL,
  career_id INT UNSIGNED NULL,
  pathway_title VARCHAR(180) NOT NULL,
  guidance_notes TEXT NOT NULL,
  recommended_steps TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_counsel_pathway_student (student_id),
  KEY idx_counsel_pathway_counselor (counselor_id),
  KEY idx_counsel_pathway_career (career_id),
  CONSTRAINT fk_counsel_pathway_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_counsel_pathway_counselor FOREIGN KEY (counselor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_counsel_pathway_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- default admin: email admin@careersim.test / password: Admin123!
INSERT INTO users (name, email, password, role, status, email_verified_at)
VALUES ('Admin', 'admin@careersim.test', '$2b$10$jX5SO3HCEi2k1dKHosq46eiUDMqm02118JmFSnmVxE2x0cyRiYh2W', 'admin', 'active', NOW());

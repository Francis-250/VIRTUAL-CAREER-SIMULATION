USE career_sim;
ALTER TABLE users
  ADD COLUMN resume_path VARCHAR(255) NULL AFTER profile_image,
  ADD COLUMN resume_updated_at TIMESTAMP NULL AFTER resume_path;

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

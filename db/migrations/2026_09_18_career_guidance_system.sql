-- =====================================================================
-- Career Guidance System — Counselling Requests, Appointments & Pathway Recommendations
-- =====================================================================
USE career_sim;

CREATE TABLE IF NOT EXISTS counselling_requests (
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

CREATE TABLE IF NOT EXISTS counselling_appointments (
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

CREATE TABLE IF NOT EXISTS counsellor_pathway_recommendations (
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

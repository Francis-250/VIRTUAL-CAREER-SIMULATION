USE career_sim;
ALTER TABLE users ADD COLUMN profile_completed_at TIMESTAMP NULL AFTER last_login_at;
ALTER TABLE user_task_attempts
  ADD COLUMN counselor_score SMALLINT UNSIGNED NULL AFTER ai_feedback,
  ADD COLUMN counselor_feedback TEXT NULL AFTER counselor_score,
  ADD COLUMN graded_by BIGINT UNSIGNED NULL AFTER counselor_feedback,
  ADD COLUMN graded_at TIMESTAMP NULL AFTER graded_by,
  ADD CONSTRAINT fk_task_attempt_grade_user FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE simulation_feedback
  ADD COLUMN moderation_status ENUM('new','flagged','dismissed','responded') NOT NULL DEFAULT 'new' AFTER comment,
  ADD COLUMN admin_response VARCHAR(500) NULL AFTER moderation_status,
  ADD COLUMN moderated_by BIGINT UNSIGNED NULL AFTER admin_response,
  ADD COLUMN moderated_at TIMESTAMP NULL AFTER moderated_by,
  ADD CONSTRAINT fk_feedback_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL;

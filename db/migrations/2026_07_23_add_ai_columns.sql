USE career_sim;
ALTER TABLE user_task_attempts ADD COLUMN ai_feedback TEXT NULL AFTER response_text;
ALTER TABLE career_recommendations ADD COLUMN explanation TEXT NULL AFTER based_on;

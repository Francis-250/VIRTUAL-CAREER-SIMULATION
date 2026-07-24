USE career_sim;

ALTER TABLE career_simulations
  ADD COLUMN quiz_id BIGINT UNSIGNED NULL AFTER career_id,
  ADD KEY idx_simulations_quiz (quiz_id),
  ADD CONSTRAINT fk_simulations_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE RESTRICT;

UPDATE career_simulations s
JOIN quizzes q ON q.career_id=s.career_id
SET s.quiz_id=q.id
WHERE s.quiz_id IS NULL
  AND q.id=(SELECT MIN(q2.id) FROM quizzes q2 WHERE q2.career_id=s.career_id);

ALTER TABLE career_simulations
  MODIFY quiz_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE quizzes
  ADD UNIQUE KEY uq_quizzes_id_career (id,career_id);

ALTER TABLE career_simulations
  ADD CONSTRAINT fk_simulations_quiz_career
  FOREIGN KEY (quiz_id,career_id) REFERENCES quizzes(id,career_id) ON DELETE RESTRICT;

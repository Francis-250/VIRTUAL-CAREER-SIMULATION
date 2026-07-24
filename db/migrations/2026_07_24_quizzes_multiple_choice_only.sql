USE career_sim;

-- Normalize legacy true/false records to the only quiz type supported by
-- the current manager and student interfaces. Existing answer text is kept.
UPDATE quiz_questions
SET question_type = 'single_choice'
WHERE question_type <> 'single_choice';

-- Starter content for the already-created career_sim database.
-- Safe to run repeatedly. It does not create or alter tables.
USE career_sim;

INSERT IGNORE INTO users (name,email,password,role,status,email_verified_at)
VALUES ('Content Manager','manager@careersim.test','$2y$10$52Yx29Kikj4utva0K7FGmObs2Mdpz5q6Jw19FMbpKKQQKLL7qYcGm','content_manager','active',NOW());
INSERT IGNORE INTO users (name,email,password,role,status,email_verified_at) VALUES
('Demo Student','student@careersim.test','$2y$10$5loSw2LDYu2zT9m.YEx6yemKz.dmYBMnZcIlGTVf5GCNqk4Yo5Udu','student','active',NOW()),
('Demo Counselor','counselor@careersim.test','$2y$10$eazbn9zy3LmX4jzSyza6POGRsTjClPJAL4ZIrPk886ov77rXcCMOK','counselor','active',NOW());

INSERT IGNORE INTO career_categories(name,slug,icon) VALUES
('Technology','technology','laptop'),
('Health Sciences','health-sciences','heart-pulse'),
('Business','business','graph-up-arrow'),
('Creative Arts','creative-arts','palette'),
('Engineering','engineering','gear'),
('Education','education','book');

INSERT IGNORE INTO skills(name) VALUES
('Problem Solving'),('Communication'),('Critical Thinking'),('Teamwork'),
('Programming'),('Data Analysis'),('Empathy'),('Creativity'),
('Project Management'),('Attention to Detail'),('Research'),('Leadership');

INSERT IGNORE INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,is_active)
SELECT id,'Software Developer','software-developer','Design and build software applications that solve practical problems.','Software developers analyse needs, design solutions, write and test code, and collaborate with product and design teams.',480000,'high_growth',1 FROM career_categories WHERE slug='technology';
INSERT IGNORE INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,is_active)
SELECT id,'Data Analyst','data-analyst','Turn raw data into insights that support better decisions.','Data analysts collect, clean, explore and visualise data, then communicate findings to decision makers.',420000,'high_growth',1 FROM career_categories WHERE slug='technology';
INSERT IGNORE INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,is_active)
SELECT id,'Registered Nurse','registered-nurse','Provide clinical care, education and support to patients.','Registered nurses assess patients, coordinate care, administer treatment and support families in varied healthcare settings.',360000,'growing',1 FROM career_categories WHERE slug='health-sciences';
INSERT IGNORE INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,is_active)
SELECT id,'Marketing Specialist','marketing-specialist','Plan campaigns that connect organisations with their audiences.','Marketing specialists research audiences, develop campaigns, create content and measure campaign performance.',390000,'growing',1 FROM career_categories WHERE slug='business';
INSERT IGNORE INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,is_active)
SELECT id,'Civil Engineer','civil-engineer','Design and oversee infrastructure such as roads, bridges and water systems.','Civil engineers apply mathematics, science and project management to create safe and sustainable infrastructure.',550000,'growing',1 FROM career_categories WHERE slug='engineering';
INSERT IGNORE INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,is_active)
SELECT id,'Teacher','teacher','Help learners develop knowledge, skills and confidence.','Teachers plan lessons, facilitate learning, assess progress and create supportive learning environments.',300000,'stable',1 FROM career_categories WHERE slug='education';

INSERT IGNORE INTO career_skills(career_id,skill_id,importance)
SELECT c.id,s.id,'essential' FROM careers c JOIN skills s WHERE
(c.slug='software-developer' AND s.name IN ('Programming','Problem Solving','Teamwork','Attention to Detail')) OR
(c.slug='data-analyst' AND s.name IN ('Data Analysis','Critical Thinking','Research','Communication')) OR
(c.slug='registered-nurse' AND s.name IN ('Empathy','Communication','Teamwork','Attention to Detail')) OR
(c.slug='marketing-specialist' AND s.name IN ('Creativity','Communication','Data Analysis','Project Management')) OR
(c.slug='civil-engineer' AND s.name IN ('Problem Solving','Project Management','Critical Thinking','Teamwork')) OR
(c.slug='teacher' AND s.name IN ('Communication','Empathy','Creativity','Leadership'));

INSERT IGNORE INTO career_interest_mapping(career_id,interest_type,weight)
SELECT c.id,t.interest_type,
CASE c.slug
 WHEN 'software-developer' THEN FIELD(t.interest_type,'realistic','investigative','artistic','social','enterprising','conventional') * 0 + CASE t.interest_type WHEN 'investigative' THEN 1 WHEN 'realistic' THEN .8 WHEN 'conventional' THEN .6 ELSE .3 END
 WHEN 'data-analyst' THEN CASE t.interest_type WHEN 'investigative' THEN 1 WHEN 'conventional' THEN .9 WHEN 'realistic' THEN .5 ELSE .3 END
 WHEN 'registered-nurse' THEN CASE t.interest_type WHEN 'social' THEN 1 WHEN 'realistic' THEN .7 WHEN 'investigative' THEN .6 ELSE .3 END
 WHEN 'marketing-specialist' THEN CASE t.interest_type WHEN 'enterprising' THEN 1 WHEN 'artistic' THEN .8 WHEN 'social' THEN .7 ELSE .3 END
 WHEN 'civil-engineer' THEN CASE t.interest_type WHEN 'realistic' THEN 1 WHEN 'investigative' THEN .9 WHEN 'conventional' THEN .6 ELSE .2 END
 ELSE CASE t.interest_type WHEN 'social' THEN 1 WHEN 'artistic' THEN .7 WHEN 'enterprising' THEN .5 ELSE .3 END END
FROM careers c CROSS JOIN (
 SELECT 'realistic' interest_type UNION SELECT 'investigative' UNION SELECT 'artistic'
 UNION SELECT 'social' UNION SELECT 'enterprising' UNION SELECT 'conventional'
) t;

INSERT INTO career_simulations(career_id,title,description,difficulty_level,estimated_duration_minutes,is_active)
SELECT c.id,CONCAT('A Day as a ',c.title),CONCAT('Practice decisions and tasks commonly handled by a ',LOWER(c.title),'.'),'beginner',20,1
FROM careers c WHERE NOT EXISTS (SELECT 1 FROM career_simulations s WHERE s.career_id=c.id);

INSERT INTO simulation_tasks(simulation_id,title,instructions,task_type,sort_order,max_score)
SELECT s.id,'Choose your first priority','Review the situation and choose the most professional first action.','scenario_choice',1,10
FROM career_simulations s WHERE NOT EXISTS (SELECT 1 FROM simulation_tasks t WHERE t.simulation_id=s.id);
INSERT INTO simulation_tasks(simulation_id,title,instructions,task_type,sort_order,max_score)
SELECT s.id,'Reflect on the work','Describe what you found most challenging and how you would improve your approach.','reflection',2,10
FROM career_simulations s WHERE NOT EXISTS (SELECT 1 FROM simulation_tasks t WHERE t.simulation_id=s.id AND t.sort_order=2);

INSERT INTO task_options(task_id,option_text,score_value,feedback_text,is_best_practice,sort_order)
SELECT t.id,'Clarify the objective, assess urgency, and plan the next action.',10,'Strong choice: good professionals clarify the goal and prioritise before acting.',1,1
FROM simulation_tasks t WHERE t.task_type='scenario_choice' AND NOT EXISTS(SELECT 1 FROM task_options o WHERE o.task_id=t.id);
INSERT INTO task_options(task_id,option_text,score_value,feedback_text,is_best_practice,sort_order)
SELECT t.id,'Start immediately without checking requirements.',4,'Taking initiative helps, but first confirm the objective and constraints.',0,2
FROM simulation_tasks t WHERE t.task_type='scenario_choice' AND (SELECT COUNT(*) FROM task_options o WHERE o.task_id=t.id)=1;
INSERT INTO task_options(task_id,option_text,score_value,feedback_text,is_best_practice,sort_order)
SELECT t.id,'Wait for someone else to make every decision.',1,'Workplace tasks usually require appropriate initiative and communication.',0,3
FROM simulation_tasks t WHERE t.task_type='scenario_choice' AND (SELECT COUNT(*) FROM task_options o WHERE o.task_id=t.id)=2;

INSERT INTO quizzes(career_id,title,description,pass_score,time_limit_minutes,is_active)
SELECT c.id,CONCAT(c.title,' Foundations'),'Check your understanding of professional practice for this career.',60,10,1
FROM careers c WHERE NOT EXISTS(SELECT 1 FROM quizzes q WHERE q.career_id=c.id);
INSERT INTO quiz_questions(quiz_id,question_text,question_type,sort_order,points)
SELECT q.id,'Which behaviour is most important when beginning a new professional task?','single_choice',1,1
FROM quizzes q WHERE NOT EXISTS(SELECT 1 FROM quiz_questions x WHERE x.quiz_id=q.id);
INSERT INTO quiz_questions(quiz_id,question_text,question_type,sort_order,points)
SELECT q.id,'Professional feedback should be used to improve future performance.','true_false',2,1
FROM quizzes q WHERE NOT EXISTS(SELECT 1 FROM quiz_questions x WHERE x.quiz_id=q.id AND x.sort_order=2);
INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order)
SELECT x.id,'Clarify the goal and requirements',1,1 FROM quiz_questions x WHERE x.sort_order=1 AND NOT EXISTS(SELECT 1 FROM quiz_options o WHERE o.question_id=x.id);
INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order)
SELECT x.id,'Ignore constraints and rush',0,2 FROM quiz_questions x WHERE x.sort_order=1 AND (SELECT COUNT(*) FROM quiz_options o WHERE o.question_id=x.id)=1;
INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order)
SELECT x.id,'True',1,1 FROM quiz_questions x WHERE x.sort_order=2 AND NOT EXISTS(SELECT 1 FROM quiz_options o WHERE o.question_id=x.id);
INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order)
SELECT x.id,'False',0,2 FROM quiz_questions x WHERE x.sort_order=2 AND (SELECT COUNT(*) FROM quiz_options o WHERE o.question_id=x.id)=1;

INSERT IGNORE INTO interest_questions(question_text,interest_type,is_active) VALUES
('I enjoy building, repairing, or working with tools.','realistic',1),
('I enjoy investigating how things work.','investigative',1),
('I enjoy creating original designs, stories, or visual work.','artistic',1),
('I enjoy helping, teaching, or supporting other people.','social',1),
('I enjoy persuading others and leading projects.','enterprising',1),
('I enjoy organising information and following clear procedures.','conventional',1),
('I prefer practical tasks with visible results.','realistic',1),
('I like analysing evidence to solve difficult questions.','investigative',1),
('I value imagination and self-expression in my work.','artistic',1),
('I feel energised when working closely with people.','social',1),
('I am comfortable making decisions and taking responsibility.','enterprising',1),
('I enjoy accuracy, records, schedules, and structured work.','conventional',1);

INSERT IGNORE INTO badges(name,description,icon,criteria_type,criteria_value) VALUES
('First Step','Complete your first career simulation.','flag','simulation_complete',1),
('Explorer','Complete simulations in three careers.','compass','career_count',3),
('Quiz Starter','Pass your first career quiz.','patch-check','quiz_pass',1),
('Knowledge Builder','Pass five career quizzes.','mortarboard','quiz_pass',5),
('Simulation Pro','Complete five career simulations.','trophy','simulation_complete',5);

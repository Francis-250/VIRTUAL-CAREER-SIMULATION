USE career_sim;

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS career_quiz_bank;
CREATE TEMPORARY TABLE career_quiz_bank (
  career_title VARCHAR(150) NOT NULL,
  sort_order INT UNSIGNED NOT NULL,
  question_text VARCHAR(500) NOT NULL,
  option_a VARCHAR(300) NOT NULL,
  option_b VARCHAR(300) NOT NULL,
  option_c VARCHAR(300) NOT NULL,
  option_d VARCHAR(300) NOT NULL,
  correct_option TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (career_title, sort_order)
);

INSERT INTO career_quiz_bank VALUES
('Software Developer',1,'What is the main purpose of version control in software development?','To track code changes and support collaboration','To replace automated testing','To design user interfaces','To host production databases',1),
('Software Developer',2,'Which practice helps detect defects before code is merged?','Skipping local tests','Peer code review','Deleting commit history','Using longer variable names only',2),
('Software Developer',3,'What does an API primarily allow software systems to do?','Communicate through defined interfaces','Increase monitor resolution','Compress every database table','Remove the need for authentication',1),
('Software Developer',4,'Why are automated tests valuable during refactoring?','They guarantee perfect performance','They confirm existing behaviour still works','They eliminate source control','They write all new features automatically',2),
('Software Developer',5,'Which data structure follows first-in, first-out order?','Stack','Queue','Tree','Set',2),
('Software Developer',6,'What is the safest way to store user passwords?','Plain text','Reversible encryption with a shared key','A strong one-way password hash','Inside browser JavaScript',3),
('Software Developer',7,'What should a developer do first when a production error is reported?','Rewrite the entire application','Reproduce and inspect evidence about the error','Delete the error logs','Immediately blame the last contributor',2),
('Software Developer',8,'Which SQL clause filters rows returned by a query?','WHERE','ORDER BY','GROUP BY','CREATE',1),
('Software Developer',9,'What is the purpose of input validation?','To make every field optional','To reject malformed or unsafe data','To remove database indexes','To increase file size',2),
('Software Developer',10,'Which approach best improves maintainability?','Small focused functions with clear names','Duplicating logic in many files','Hardcoding every configuration value','Avoiding documentation and tests',1),

('Data Analyst',1,'What should a data analyst verify before drawing conclusions from a dataset?','The chart colour palette','Data quality and completeness','The presentation font','The file name length',2),
('Data Analyst',2,'Which measure describes the middle value in an ordered dataset?','Mean','Median','Range','Variance',2),
('Data Analyst',3,'When is a bar chart generally most appropriate?','Comparing values across categories','Showing source code changes','Storing raw records','Encrypting confidential data',1),
('Data Analyst',4,'What does a NULL value usually represent in a dataset?','Exactly zero','A missing or unknown value','A duplicated row','A negative number',2),
('Data Analyst',5,'Why should duplicate records be investigated?','They may distort counts and calculations','They always improve accuracy','They automatically create trends','They reduce storage in every case',1),
('Data Analyst',6,'Which SQL operation combines related rows from two tables?','JOIN','DROP','RENAME','TRUNCATE',1),
('Data Analyst',7,'What is correlation?','Proof that one variable causes another','A measure of association between variables','A method for deleting outliers','A database password rule',2),
('Data Analyst',8,'What is the best response to an extreme outlier?','Always delete it immediately','Investigate its cause and document the decision','Replace it with zero without review','Hide it from all reports',2),
('Data Analyst',9,'Why should an analysis include its assumptions?','To help others interpret and reproduce the work','To make the report longer','To avoid checking the data','To guarantee the preferred conclusion',1),
('Data Analyst',10,'Which dashboard design best supports decision-making?','Clear metrics connected to the business question','Every available chart on one page','Unlabelled axes and abbreviations','Decorative animation without context',1),

('Registered Nurse',1,'What is the nurse’s first priority before administering medication?','Verify the patient and medication order','Ask another patient for confirmation','Remove the medication label','Record administration before giving it',1),
('Registered Nurse',2,'Which action best reduces the spread of infection in clinical care?','Consistent hand hygiene','Reusing disposable gloves','Leaving wounds uncovered','Sharing personal protective equipment',1),
('Registered Nurse',3,'What should a nurse do after noticing a sudden deterioration in a patient?','Wait until the next routine round','Assess the patient and escalate promptly','Edit the previous observations','Ask the patient to diagnose the problem',2),
('Registered Nurse',4,'Why are vital signs measured and trended?','To identify changes in physiological condition','To replace all clinical assessment','To determine hospital billing only','To avoid speaking with the patient',1),
('Registered Nurse',5,'What is informed consent intended to protect?','The patient’s right to understand and decide','The hospital’s marketing plan','The medicine supplier’s schedule','The visitor parking allocation',1),
('Registered Nurse',6,'Which note is most appropriate in a clinical record?','Objective, timely, and accurate documentation','Personal opinions about the patient','Information copied from another patient','Unrecorded verbal assumptions',1),
('Registered Nurse',7,'How should a nurse respond to a medication allergy alert?','Ignore it if the medicine is common','Stop and verify safety before administration','Ask the patient to remove the alert','Give half the dose automatically',2),
('Registered Nurse',8,'What is the purpose of patient handover?','Communicate essential care information and risks','Transfer accountability without information','Shorten every patient assessment','Replace written documentation completely',1),
('Registered Nurse',9,'Which approach best supports patient dignity?','Explain care and maintain privacy','Discuss the patient in public areas','Make decisions without involving the patient','Leave personal information visible',1),
('Registered Nurse',10,'What is the safest response to an unfamiliar clinical procedure?','Consult approved guidance and a qualified supervisor','Guess based on a different procedure','Proceed quickly without preparation','Ask an untrained visitor to assist',1),

('Marketing Specialist',1,'What is the purpose of defining a target audience?','Tailor messages to the people most likely to respond','Send identical messages to everyone','Avoid measuring campaign performance','Replace the product strategy',1),
('Marketing Specialist',2,'Which metric measures the percentage of viewers who click a link?','Click-through rate','Inventory turnover','Staff retention rate','Debt-to-equity ratio',1),
('Marketing Specialist',3,'What does a call to action ask an audience to do?','Take a specific next step','Ignore the campaign','Rewrite the brand guidelines','Change the company structure',1),
('Marketing Specialist',4,'Why is A/B testing used in digital marketing?','Compare the performance of two variations','Publish every idea at once','Eliminate audience segmentation','Avoid collecting results',1),
('Marketing Specialist',5,'What is brand positioning?','The distinct place a brand aims to hold in customers’ minds','The physical location of every employee','A list of accounting transactions','The order of website files',1),
('Marketing Specialist',6,'Which action is essential before using a customer testimonial?','Obtain appropriate permission','Change the customer’s meaning','Hide that it is a testimonial','Publish private details',1),
('Marketing Specialist',7,'What does conversion rate measure?','The share of users who complete a desired action','The number of colours in an advertisement','The length of a campaign title','The company’s office capacity',1),
('Marketing Specialist',8,'How should a marketer respond to poor campaign performance?','Analyse the data and test an evidence-based adjustment','Increase spending without reviewing results','Delete all historical metrics','Report only the best-performing day',1),
('Marketing Specialist',9,'What makes a marketing objective useful?','It is specific, measurable, achievable, relevant, and time-bound','It has no deadline or measure','It changes every hour','It avoids connection to business goals',1),
('Marketing Specialist',10,'Why is consistent brand voice important?','It builds recognition and trust across channels','It prevents any creative work','It guarantees every campaign succeeds','It replaces customer research',1),

('Civil Engineer',1,'What is the primary purpose of a site investigation?','Understand ground and site conditions before design','Choose the project logo','Set employee leave dates','Replace structural calculations',1),
('Civil Engineer',2,'Why are safety factors used in engineering design?','Account for uncertainty and provide a margin of safety','Reduce every material strength to zero','Avoid compliance with standards','Guarantee that inspection is unnecessary',1),
('Civil Engineer',3,'What does a structural load describe?','A force or action applied to a structure','The colour of a construction drawing','The number of project meetings','The software installation size',1),
('Civil Engineer',4,'Which document communicates dimensions and construction details?','Engineering drawing','Marketing brochure','Payroll report','Customer survey',1),
('Civil Engineer',5,'What should happen when site work differs from an approved design?','Assess the change and obtain proper technical approval','Hide the difference in the report','Continue without recording it','Ask the public to decide',1),
('Civil Engineer',6,'Why is drainage considered in road and site design?','Control water and protect infrastructure','Increase traffic noise','Remove the need for maintenance','Replace soil testing',1),
('Civil Engineer',7,'What is reinforced concrete designed to combine?','Concrete’s compressive strength and steel’s tensile strength','Timber flexibility and glass transparency','Soil density and paint durability','Water pressure and electrical resistance',1),
('Civil Engineer',8,'Which practice supports quality control during construction?','Inspect and test work against specifications','Approve all work without evidence','Change standards after completion','Discard material test results',1),
('Civil Engineer',9,'What is the purpose of an environmental impact assessment?','Identify and manage potential environmental effects','Calculate employee salaries','Select office furniture','Replace all engineering drawings',1),
('Civil Engineer',10,'What should an engineer do if a design presents an unacceptable safety risk?','Escalate it and revise the design before approval','Approve it to protect the schedule','Remove the risk from the report','Transfer the decision to an unqualified person',1),

('Teacher',1,'What is the main purpose of a lesson objective?','State what learners should know or be able to do','List classroom furniture','Replace assessment completely','Record teacher attendance',1),
('Teacher',2,'Which assessment is used during learning to guide improvement?','Formative assessment','Final certification only','Building inspection','Financial auditing',1),
('Teacher',3,'How can a teacher support learners with different readiness levels?','Differentiate tasks and support','Give every learner identical help regardless of need','Remove all challenging work','Assess only the fastest learner',1),
('Teacher',4,'What makes feedback most useful to a learner?','It is timely, specific, and actionable','It only gives a mark','It compares the learner personally with others','It avoids mentioning improvement',1),
('Teacher',5,'Why are open-ended questions useful in a lesson?','They encourage explanation and deeper thinking','They guarantee one-word answers','They prevent discussion','They remove the need to listen',1),
('Teacher',6,'What is the best response to repeated learner misunderstanding?','Use assessment evidence and reteach with another approach','Continue without checking understanding','Lower every learner’s mark immediately','Skip the topic permanently',1),
('Teacher',7,'Which practice creates an inclusive classroom?','Use respectful examples and accessible participation options','Allow only confident learners to contribute','Ignore language and learning barriers','Publicly rank learners by ability',1),
('Teacher',8,'What is classroom management intended to support?','A safe environment focused on learning','Silence without learning','Punishment as the main lesson activity','Competition for teacher attention',1),
('Teacher',9,'Why should assessment criteria be shared with learners?','Clarify expectations and support self-evaluation','Reveal private learner information','Remove the need for teaching','Guarantee identical answers',1),
('Teacher',10,'What should a teacher do when concerned about a learner’s safety?','Follow the school safeguarding and reporting procedure','Promise to keep every disclosure secret','Investigate alone outside professional procedures','Discuss it publicly with the class',1);

DELETE qq
FROM quiz_questions qq
JOIN quizzes q ON q.id = qq.quiz_id
JOIN careers c ON c.id = q.career_id
JOIN (SELECT DISTINCT career_title FROM career_quiz_bank) bank ON bank.career_title = c.title;

INSERT INTO quiz_questions (quiz_id, question_text, question_type, sort_order, points)
SELECT q.id, bank.question_text, 'single_choice', bank.sort_order, 1
FROM career_quiz_bank bank
JOIN careers c ON c.title = bank.career_title
JOIN quizzes q ON q.career_id = c.id;

INSERT INTO quiz_options (question_id, option_text, is_correct, sort_order)
SELECT qq.id,
  CASE numbers.n WHEN 1 THEN bank.option_a WHEN 2 THEN bank.option_b WHEN 3 THEN bank.option_c ELSE bank.option_d END,
  numbers.n = bank.correct_option,
  numbers.n
FROM career_quiz_bank bank
JOIN careers c ON c.title = bank.career_title
JOIN quizzes q ON q.career_id = c.id
JOIN quiz_questions qq ON qq.quiz_id = q.id AND qq.sort_order = bank.sort_order AND qq.question_text = bank.question_text
CROSS JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) numbers;

DROP TEMPORARY TABLE career_quiz_bank;
COMMIT;

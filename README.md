# CareerSim — Virtual Career Simulation System

CareerSim is a PHP and MySQL web application that helps students explore careers through RIASEC assessments, simulations, quizzes, recommendations, counselor feedback, and job applications.

## Features

- Student registration, email verification, login, and first-login profile completion
- RIASEC interest assessment with career recommendations
- Career catalog with categories, skills, simulations, and quizzes
- Multiple-choice quiz and interest-question builders
- Practical exercises and reflection tasks with optional AI feedback
- Counselor progress oversight, task grading, and application feedback
- Badges, notifications, progress tracking, and reports
- Resume upload and reusable education background
- Job browsing, applications, status tracking, and counselor review
- Groq-powered career chat, recommendation explanations, feedback, and content drafting
- Role-based administration with separate admin and content-manager responsibilities

## Roles

| Role | Responsibilities |
| --- | --- |
| Student | Complete assessments, explore careers, take simulations and quizzes, maintain a resume and education profile, and apply for jobs |
| Counselor | Review student progress, grade written tasks, review applications, and give feedback |
| Content manager | Manage careers, categories, skills, simulations, quizzes, interest questions, badges, and job opportunities |
| Admin | Manage users and view reports, recommendations, feedback, and audit logs |

Admin does not manage learning or career content. Content management is intentionally assigned to the content-manager role.

## Requirements

- PHP 8.1 or newer
- MySQL or MariaDB
- Apache with PHP enabled
- PHP extensions: `mysqli`, `curl`, `fileinfo`, `mbstring`, and `openssl`
- Composer
- Internet access for Bootstrap assets and optional Groq features

The project is designed for XAMPP on Windows, but it can run on another PHP/Apache environment with equivalent configuration.

## Installation with XAMPP

1. Place the project at:

   ```text
   C:\xampp\htdocs\Virtual career simulation system
   ```

2. Start Apache and MySQL in the XAMPP Control Panel.

3. Install PHP dependencies from the project directory:

   ```console
   composer install
   ```

4. Create the database and import the complete schema:

   Run this from Command Prompt:

   ```console
   C:\xampp\mysql\bin\mysql.exe -u root < db\schema.sql
   ```

5. Import the starter careers, questions, simulations, quizzes, and demonstration accounts:

   Run this from Command Prompt:

   ```console
   C:\xampp\mysql\bin\mysql.exe -u root career_sim < db\seed.sql
   ```

6. Install the unique ten-question career quiz banks:

   ```console
   C:\xampp\mysql\bin\mysql.exe -u root career_sim < db\migrations\2026_07_24_unique_career_quiz_banks.sql
   ```

7. Move the initial career image values into database-managed content:

   ```console
   C:\xampp\mysql\bin\mysql.exe -u root career_sim < db\migrations\2026_07_24_dynamic_career_images.sql
   ```

8. Link every simulation to its related quiz:

   ```console
   C:\xampp\mysql\bin\mysql.exe -u root career_sim < db\migrations\2026_07_24_link_simulations_to_quizzes.sql
   ```

9. Copy the example environment file:

   ```powershell
   Copy-Item .env.example .env
   ```

10. Add the required values to `.env`. Do not commit real API keys or SMTP passwords.

11. Make sure `uploads/resumes` exists and is writable by Apache.

12. Open:

   ```text
   http://localhost/Virtual%20career%20simulation%20system/
   ```

The application URL is defined by `BASE_URL` in `includes/functions.php`. Update it if the project is installed under a different directory or virtual host.

## Environment configuration

Example `.env`:

```dotenv
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=

DB_HOST=127.0.0.1
DB_USER=root
DB_PASS=
DB_NAME=career_sim

GROQ_API_KEY=
GROQ_MODEL=llama-3.3-70b-versatile
```

Groq and mail settings are loaded from `.env`. The current database connection reads server environment variables and otherwise uses the XAMPP defaults shown above.

### Groq AI

Set `GROQ_API_KEY` to enable:

- Constructive feedback on practical and reflection tasks
- Career recommendation explanations
- Career-specific question-and-answer chat
- Career-specific multiple-choice question drafting from the Simulation page
- RIASEC interest-question drafting

The default model is `llama-3.3-70b-versatile`. Change `GROQ_MODEL` if needed.

AI failures are logged server-side and return a friendly unavailable message. Core assessment, quiz, simulation, and administrative workflows continue without AI. Student email addresses, phone numbers, dates of birth, and other unnecessary personal information are not sent to Groq.

## Demonstration accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@careersim.test` | `Admin123!` |
| Content manager | `manager@careersim.test` | `Manager123!` |
| Student | `student@careersim.test` | `Student123!` |
| Counselor | `counselor@careersim.test` | `Counselor123!` |

These credentials are for local development only. Change or remove them before deploying the application.

Users with an incomplete profile are redirected to the profile-completion form after their first login.

## Complete role workflows

### Content Manager

The content manager creates all student-facing career content. This role does not manage users or administrative reports.

#### Create career content

1. Open **Careers** and select **Add career**.
2. Enter the title, category, salary, summary, description, growth outlook, and status, then upload a JPG, PNG, or WEBP career image from your computer.
3. Optionally select **Generate summary and description** after entering the title. AI fills editable draft fields; the manager must review them and explicitly save.
4. The system automatically creates a unique URL slug from the career title.
5. Save the career.
6. Open **Quizzes** and select **Add quiz**.
7. Select the career.
8. Enter only the quiz title, description, pass score, time limit, and status.
9. Save the quiz. Questions are not managed on the Quiz page.
10. Open **Simulations** and select **Create simulation with AI**.
11. Select the career.
12. Select a quiz belonging to that career.
13. Select the level. The quiz description is used automatically as the AI topic.
14. Generate and review the questions.
15. Select **Save reviewed draft** to create the simulation and save its questions.

MySQL rejects a simulation if its quiz belongs to another career.

#### Generate questions with AI

1. Open **Simulations** and select **Create simulation with AI**.
2. Select the career and its related quiz combination.
3. Confirm or change the level inherited from the simulation.
4. The system automatically uses the career, quiz title, and quiz description as AI context.
5. Generate the draft.
6. Review and edit every question, option, correct answer, and score.
7. Select **Save reviewed draft**.

Questions can only be created with AI from the Simulation page. AI drafts ten quiz-specific multiple-choice questions with four choices and one correct answer. Nothing is saved without content-manager approval. Saved questions remain editable and can be deleted from the Simulation page.

#### Manage categories and skills

1. Open **Categories** to add or edit career categories.
2. Open **Skills** to add or edit reusable career skills.
3. Connect the appropriate category and skills to career content.

#### Manage RIASEC questions

1. Open **Interest Questions**.
2. Add a statement manually or generate an AI draft.
3. Select its RIASEC type.
4. Review the wording and status.
5. Save the question.

#### Manage badges

1. Open **Badges**.
2. Add or edit the name, description, and icon.
3. Select the achievement criterion.
4. Set the required value and save.

Badge criteria include completed simulations, passed quizzes, careers explored, and activity streaks.

#### Publish jobs

1. Open **Job Opportunities** and select **Add job**.
2. Enter the title, company, location, type, description, requirements, and closing date.
3. Optionally connect the job to a career.
4. Set its active status and save.

Only active jobs that have not passed their closing date are shown to students.

### Student

The student completes career discovery activities and maintains a job-application profile.

#### Registration and profile

1. Register and complete email verification.
2. Sign in.
3. Complete the required first-login profile.
4. Open **Career profile** to upload or replace a resume.
5. Add, edit, or delete education records.

Resume uploads accept PDF, DOC, and DOCX files up to 5 MB.

#### RIASEC assessment

1. Open **Assessment**.
2. Rate every statement from 1 to 5.
3. Submit all answers.
4. The system calculates the six RIASEC scores.
5. Career recommendations are refreshed.
6. Open **My progress** to review matches and explanations.

The assessment cannot be submitted with unanswered questions.

#### Career, simulation, and quiz

1. Open **Explore**.
2. Search careers or filter by category.
3. Open a career to review its details, skills, salary, outlook, simulations, and quizzes.
4. Start a simulation.
5. Select **Choose a quiz** to view all active quizzes for the career.
6. Compare each quiz’s description, question count, time limit, pass score, and previous best score.
7. Start the quiz you want to complete.
8. Answer the multiple-choice questions before the timer expires.
9. Submit the quiz.
10. Review the score and pass result.
11. Review updated progress, badges, and recommendations.

#### Written tasks and feedback

For existing practical exercises or reflection tasks:

1. Read the task instructions.
2. Enter and submit a free-text response.
3. Review AI coaching feedback when available.
4. Review counselor scoring and feedback later in **My progress**.

AI failure never prevents the response from being saved.

#### Career chatbot

1. Open a career detail page.
2. Select **Ask about this career**.
3. Ask about the work, skills, education, or preparation path.
4. Continue the career-specific conversation in the same session.

#### Apply for jobs

1. Upload a resume and add education history under **Career profile**.
2. Open **Jobs** and find an active opportunity.
3. Select **Apply now**.
4. Add an optional cover letter and submit.
5. Open **My applications** to track the status.
6. Read counselor feedback when available.

A student cannot apply to the same job more than once.

#### Notifications and progress

Students receive notifications for submitted applications, application changes, counselor feedback, and recommendation refreshes. **My progress** displays activities, scores, recommendations, badges, AI feedback, and counselor feedback.

### Counselor

The counselor reviews students and applications. This role cannot manage users or create platform content.

#### Review student progress

1. Open **Counselor**.
2. Search for a student.
3. Open the student profile.
4. Review education, simulation progress, quiz attempts, scores, recommendations, badges, and written responses.

#### Grade written tasks

1. Open the student progress view.
2. Locate a submitted practical exercise or reflection.
3. Review the instructions, response, and AI feedback.
4. Enter a counselor score and constructive feedback.
5. Save the review.

The result remains visible to the student.

#### Review job applications

1. Open **Applications**.
2. Filter by status if needed.
3. Select **Review**.
4. Review the student, job, cover letter, education information, and submitted resume.
5. Set the status to under review, shortlisted, unsuccessful, or accepted.
6. Enter feedback explaining the decision and next step.
7. Save the review.

The student is notified automatically.

### Admin

The admin manages users and reports only. The admin cannot create or modify careers, quizzes, simulations, questions, interest content, badges, or jobs.

#### Manage users

1. Open **User Management**.
2. Search or review accounts.
3. Change an authorized account role.
4. Set the status to pending, active, or suspended.
5. Save the change.

An admin cannot remove their own active admin access.

#### View reports

The admin can review:

- Platform summary reports
- Career recommendation reports
- User and activity statistics
- Student simulation feedback
- Administrative audit logs

#### Moderate feedback

1. Open **Feedback Report**.
2. Review ratings and comments.
3. Mark feedback as new, flagged, dismissed, or responded.
4. Add an administrative response when appropriate.
5. Save the moderation result.

#### Review audit logs

1. Open **Admin Logs**.
2. Review recorded user, content, moderation, and management actions.
3. Use the logs for oversight and troubleshooting.

### Cross-role job workflow

1. The content manager publishes an active job.
2. The student maintains a resume and education profile.
3. The student applies with the saved resume.
4. The counselor reviews the application and provides a status and feedback.
5. The student receives a notification and reviews the result.
6. The admin can review platform reports but does not make the counselor’s application decision.

## Database

For a new installation, use `db/schema.sql` followed by `db/seed.sql`.

For an older existing installation, apply the migrations in this order:

1. `db/migrations/2026_07_23_add_ai_columns.sql`
2. `db/migrations/2026_07_23_complete_remaining_workflows.sql`
3. `db/migrations/2026_07_23_jobs_resume_education.sql`
4. `db/migrations/2026_07_24_quizzes_multiple_choice_only.sql`
5. `db/migrations/2026_07_24_unique_career_quiz_banks.sql`
6. `db/migrations/2026_07_24_dynamic_career_images.sql`
7. `db/migrations/2026_07_24_link_simulations_to_quizzes.sql`

Back up the database before applying migrations.

Important workflow tables include:

- `users`, `user_education`
- `interest_questions`, `user_interest_responses`, `user_interest_scores`
- `careers`, `career_categories`, `skills`, `career_skills`
- `career_simulations`, `simulation_tasks`, `user_task_attempts`
- `quizzes`, `quiz_questions`, `quiz_options`, `quiz_attempts`
- `career_recommendations`, `badges`, `user_badges`
- `job_opportunities`, `job_applications`
- `notifications`, `simulation_feedback`, `admin_logs`

## Resume uploads

- Accepted formats: PDF, DOC, and DOCX
- Maximum size: 5 MB
- Storage directory: `uploads/resumes`
- Stored filenames are randomized
- The resume path used for an application is saved with that application

Do not delete uploaded resumes that are attached to active applications unless a retention policy has been implemented.

## Security notes

- All protected pages enforce authenticated roles.
- Forms use CSRF tokens.
- Database writes use prepared statements.
- Output is HTML-escaped.
- Passwords use PHP password hashing.
- Resume types and file sizes are validated.
- AI keys are loaded from `.env`, never hardcoded.
- Administrative changes are recorded in `admin_logs`.

For production:

- Disable PHP error display and keep error logging enabled.
- Serve the application over HTTPS.
- Move uploaded resumes outside the public web root or protect them with an authorized download endpoint.
- Configure restrictive filesystem permissions.
- Replace all demonstration credentials.
- Use a dedicated database user instead of MySQL `root`.
- Set secure PHP session-cookie options.

## Troubleshooting

### Database connection unavailable

Confirm that MySQL is running, the `career_sim` database exists, and the database values match `config/database.php` or the server environment.

### AI is temporarily unavailable

Check `GROQ_API_KEY`, `GROQ_MODEL`, internet access, the PHP `curl` extension, and the PHP/Apache error log.

### Email verification or password reset is not sent

Set all SMTP values in `.env` and confirm that the SMTP provider permits authenticated TLS connections.

### Resume upload fails

Confirm that `fileinfo` is enabled, the file is no larger than 5 MB, and Apache can write to `uploads/resumes`.

### Browser reports an unexpected JSON response

Review the Apache/PHP error log. AJAX endpoints always return JSON during normal operation; an HTML response usually indicates a PHP runtime error or server configuration problem.

### RIASEC assessment does not submit

Every active question must have a rating from 1 to 5. Refresh the page after a session timeout to obtain a new CSRF token.

## Project structure

```text
admin/       Content management, reports, users, and jobs
ajax/        JSON endpoints
assessment/  RIASEC assessment
auth/        Registration, login, verification, and password reset
careers/     Career catalog and career detail
config/      Database, mail, and Groq helpers
counselor/   Student oversight, grading, and application review
dashboard/   Student progress and career profile
db/          Full schema, seed data, and migrations
includes/    Shared authentication, helpers, layout, and navigation
jobs/        Student job browsing and application tracking
quiz/        Quiz taking, submission, and results
simulation/  Simulation player and task submission
uploads/     User-uploaded files
```

## License

No license has been specified. Add a license before distributing the project publicly.

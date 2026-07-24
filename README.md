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

6. Copy the example environment file:

   ```powershell
   Copy-Item .env.example .env
   ```

7. Add the required values to `.env`. Do not commit real API keys or SMTP passwords.

8. Make sure `uploads/resumes` exists and is writable by Apache.

9. Open:

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
- Simulation-task and multiple-choice quiz drafting
- Batch quiz and RIASEC interest-question drafting

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

## Main workflows

### Student career journey

1. Register and verify the account.
2. Complete the required profile on first login.
3. Answer every RIASEC assessment question.
4. Review career matches and AI-supported explanations.
5. Explore a career, complete simulations, and take multiple-choice quizzes.
6. Review progress, badges, AI feedback, and counselor feedback.

### Job application

1. The content manager publishes an active job opportunity.
2. The student uploads or updates a PDF, DOC, or DOCX resume.
3. The student adds education records to the career profile.
4. The student applies with the saved resume and an optional cover letter.
5. A counselor reviews the resume and application.
6. The counselor updates the status and submits feedback.
7. The student receives a notification and sees the feedback under **My applications**.

### Content generation

AI generation is opened from a create/edit popup. The manager supplies the career or RIASEC type, topic, and difficulty. Generated questions must be reviewed and edited before saving. AI output is never inserted automatically.

## Database

For a new installation, use `db/schema.sql` followed by `db/seed.sql`.

For an older existing installation, apply the migrations in this order:

1. `db/migrations/2026_07_23_add_ai_columns.sql`
2. `db/migrations/2026_07_23_complete_remaining_workflows.sql`
3. `db/migrations/2026_07_23_jobs_resume_education.sql`

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

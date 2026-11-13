# Assessiva.ai

Parallel rebuild of the `student-laravel` exam platform using **Laravel 12**, **Inertia.js**, **React**, **Tailwind CSS**, a **FastAPI** AI microservice, and infrastructure hooks for **PostgreSQL**, **Redis**, **Reverb**, and future **offline sync** APIs.

## Layout

| Path | Purpose |
|------|---------|
| `laravel-app/` | Main web application (Inertia + React). |
| `ai-service/` | Python FastAPI service for AI-assisted workflows. |

## Prerequisites

- PHP 8.2+ with extensions: `pdo_pgsql` (or `pdo_mysql` / `pdo_sqlite` for local dev), `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `fileinfo`, `redis` (optional).
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) 20+ (for Vite / React)
- PostgreSQL (recommended) or another supported database
- Redis (optional; set `QUEUE_CONNECTION=redis` when ready)
- Python 3.11+ (for `ai-service`)

## Laravel app

```bash
cd laravel-app
cp .env.example .env
php artisan key:generate
```

Configure `.env`:

- Set `DB_CONNECTION=pgsql` and PostgreSQL credentials (or keep `sqlite` for quick local trials if the SQLite PDO extension is enabled).
- Set `AI_SERVICE_URL` to the FastAPI base URL (default `http://127.0.0.1:8001`).
- For queues: `QUEUE_CONNECTION=redis` and Redis host/password when Redis is available.
- For realtime: install/configure Reverb (`composer require laravel/reverb` then `php artisan reverb:install`) and set `BROADCAST_CONNECTION=reverb` plus generated `REVERB_*` keys.

Install dependencies and build the frontend:

```bash
composer install
php artisan migrate --seed
php artisan storage:link
npm install 
npm run dev
```

In a second terminal:

```bash
php artisan serve
```

### Production: Vite assets (avoid CORS / `[::1]:5173` on HTTPS)

If the live site tries to load scripts from **`http://127.0.0.1:5173`** or **`http://[::1]:5173`**, the browser will block them from **`https://…`** (mixed content + CORS). That means Laravel is still in **Vite dev mode**, not using a production build.

Do this on the server (or in CI before upload):

1. **`npm ci`** or **`npm install`**, then **`npm run build`** inside `laravel-app/` — creates **`public/build/`** (manifest + hashed JS/CSS).
2. **Remove `public/hot`** if it exists. That file is created by **`npm run dev`** and tells Laravel to use the dev server; it must **not** be on production.
3. Set **`APP_ENV=production`** and **`APP_DEBUG=false`** in production `.env`.

After deploy, pages should load assets from **`https://yoursite.com/build/...`**, not port 5173.

Seeded accounts (password **`password`** unless you change the seeder):

| Email | Role |
|-------|------|
| `admin@example.com` | Administrator |
| `lecturer@example.com` | Approved lecturer |
| `hod@example.com` | Head of Department |

### Login after migrations

- **`php artisan migrate:fresh` wipes the database** — previous users and passwords no longer exist. Run `php artisan db:seed` (or `migrate --seed`) and use the accounts in the table above, or restore a backup.
- **Non-admin accounts need `status = approved` and must not be deactivated** — if you ran migrations on an existing database, older lecturer/HoD rows may still have had `status = pending` from an earlier migration default. A follow-up migration approves legacy lecturer/HoD users who have **no** `staff_id` (they pre-date self-service registration, which always sets `staff_id`). If you still cannot sign in, check the exact error: wrong email/password shows Laravel’s generic “credentials do not match”; correct password with wrong state shows messages about pending approval, suspension, or deactivation.

### Database schema (legacy parity)

Migrations mirror `student-laravel` tables and columns, consolidated where the old app had multiple alters on the same table:

- **Laravel/Breeze**: `users` (includes `name`, `email`, `remember_token`, `timestamps`), `password_reset_tokens`, `sessions`, `cache`, `jobs` batches.
- **Domain users** (legacy + Breeze): `username`, `role`, `status`, `department_id` (FK to `departments`).
- **Schools / departments**: `schools`, `departments`.
- **Exams**: full legacy fields including `course_code`, `shuffle_questions`, submission password fields, `assessment_file`, `timestamps` (Laravel uses `updated_at`; legacy had only `created_at` on exams — harmless for app code).
- **Questions**: exam questions + `question_bank_id` (optional link to bank).
- **Attempts**: `student_level`, `student_email`, `question_order`, plus core attempt columns.
- **Answers**, **password_resets** (legacy `user_id` + token table used by old `PasswordReset` model — separate from Breeze `password_reset_tokens`).
- **Proctoring**: `exam_sessions_proctoring`, `exam_security_logs`.
- **Activity / incidents**: `exam_activity_logs`, `exam_reports`, `cheating_incidents`.
- **Offline sync**: `sync_queue`, `sync_metadata`.
- **Cheat events**, **exam_allowed_indexes**, **exam_cheating_penalties**, **app_settings**.
- **AI question bank**: `ai_generation_requests`, `question_bank`, `question_options`, `question_versions`, `official_question_reviews`, `question_usage_stats`, `question_audit_logs`.

Lecturer area: `/lecturer/dashboard`, exams under `/lecturer/exams`, and **questions** (create/edit/import + CSV template) nested under each exam.

## FastAPI AI service

```bash
cd ai-service
python -m venv .venv
.venv\Scripts\activate   # Windows
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8001
```

Endpoints are stubbed; Laravel calls them via `App\Services\AiService` using `AI_SERVICE_URL`.

## Next modules (planned)

User entry, attempts, proctoring, question bank, Super Admin workflows, sync APIs, and full parity with the legacy Blade app will be ported incrementally from `student-laravel` without modifying that project.

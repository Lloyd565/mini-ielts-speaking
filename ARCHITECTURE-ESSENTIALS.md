# Architecture Essentials
### Quick reference — condensed from `ARCHITECTURE.md`

Read this first for day-to-day implementation decisions. Fall back to the full `ARCHITECTURE.md` only when this doc doesn't cover the case.

---

## Tech Stack (locked)

| Layer | Choice |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| DB (dev/test) | SQLite |
| DB (optional prod) | MySQL 8 |
| Auth (optional) | Laravel Sanctum |
| AI | Google Gemini via Laravel `Http` client (no SDK) |
| Frontend | Vue 3 `<script setup>` + Vite, single Blade entry, no router/store needed |
| HTTP client (frontend) | Axios, wrapped once in `resources/js/api.js` |
| Testing | PHPUnit (Pest acceptable), `Http::fake()` for Gemini |

## Non-Negotiable Decisions

1. **Gemini isolation** — all calls go through `EvaluationServiceInterface` → `GeminiEvaluationService`. Controllers never call Gemini directly.
2. **Tests never touch the network** — every Gemini-dependent test uses `Http::fake()`. No exceptions.
3. **Standard response envelope everywhere** — see below. Every endpoint, success or error, returns this shape.
4. **Attempt status is always accurate** — `pending → evaluated | failed`. A failed evaluation still leaves a real, visible row; it is never silently dropped.
5. **Secrets never committed** — `.env` is git-ignored; `.env.example` has every key with an empty secret value. Verify before every commit.
6. **`user_id` is server-derived** — if auth (FR-7) is on, it comes from `$request->user()->id`, never from the request body.
7. **1 feedback per attempt** — enforced at the DB level via a unique constraint on `speaking_feedbacks.attempt_id`.
8. **Validation lives in Form Requests** — never inline in a controller.
9. **No premature infrastructure** — no queues, caching layers, or extra services unless a requirement explicitly needs them.
10. **API errors never leak framework internals** — no raw stack traces or debug HTML in any `/api/*` response.

## Directory Quick Map

```
app/Http/Controllers/Api/     → thin controllers, orchestration only
app/Http/Requests/            → all validation
app/Models/                   → SpeakingQuestion, SpeakingAttempt, SpeakingFeedback
app/Services/Contracts/       → EvaluationServiceInterface
app/Services/                 → GeminiEvaluationService
database/migrations/          → 3 core tables (+ users)
database/seeders/             → SpeakingQuestionSeeder
resources/js/components/      → AttemptList, AttemptDetail, SubmitAnswerForm
tests/Feature/                → one test file per endpoint group
docs/                         → database-schema.md, api-docs.md
```

## Database Cheat Sheet

| Table | Key columns | Relationship |
|---|---|---|
| `speaking_questions` | `part` (enum), `topic`, `prompt` | `hasMany` attempts |
| `speaking_attempts` | `user_id?`, `question_id`, `answer_text`, `status` (enum) | `belongsTo` user & question, `hasOne` feedback |
| `speaking_feedbacks` | `attempt_id` (**unique**), `band_score`, `strengths` (json), `areas_to_improve` (json), `raw_response` (json?) | `belongsTo` attempt |

## Response Envelope

Success:
```json
{ "success": true, "data": {}, "message": "OK" }
```
Error:
```json
{ "success": false, "message": "...", "errors": {} }
```

## Env Vars Cheat Sheet

```
GEMINI_API_KEY=            # empty in .env.example, real value only on developer machine / server
GEMINI_MODEL=gemini-1.5-flash
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_TIMEOUT=15
DB_CONNECTION=sqlite
```

## Commands Cheat Sheet

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run dev
php artisan serve
php artisan test
```

## Endpoint Quick Map

| Method | Path | Auth |
|---|---|---|
| GET | `/api/speaking/questions` | none |
| POST | `/api/speaking/submit` | none (or `auth:sanctum` if FR-7 is built) |
| GET | `/api/speaking/attempts` | none (or `auth:sanctum`) |
| GET | `/api/speaking/attempts/{id}` | none (or `auth:sanctum`) |
| POST | `/api/auth/register` \| `/login` \| `/logout` | optional, FR-7 only |
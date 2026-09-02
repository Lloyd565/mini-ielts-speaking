# Architecture Document

**Product:** Mini IELTS Speaking Evaluation API + Dashboard
**Version:** 1.0
**Last updated:** 2026-09-02

**Companion docs:** `PRD.md` (what/why) · `ARCHITECTURE-ESSENTIALS.md` (quick reference) · `AGENTS.md` (agent working rules)

---

## 1. System Overview

```
┌───────────────────┐        HTTP/JSON         ┌──────────────────────────────────┐
│   Vue 3 SPA        │ ───────────────────────► │           Laravel API             │
│  (resources/js)    │ ◄─────────────────────── │        routes/api.php             │
│  served via         │                          │                                    │
│  Blade + Vite        │                         │  Controller → FormRequest →       │
└───────────────────┘                          │  Eloquent Model → Service           │
                                                 │                                    │
                                                 │   ┌──────────────────────────┐    │
                                                 │   │ EvaluationServiceInterface│   │
                                                 │   └────────────┬─────────────┘    │
                                                 │                │                   │
                                                 │   ┌────────────▼─────────────┐    │
                                                 │   │ GeminiEvaluationService  │────┼──► Google Gemini API
                                                 │   │ (Laravel HTTP client)     │    │
                                                 │   └───────────────────────────┘    │
                                                 │                                    │
                                                 │        SQLite / MySQL              │
                                                 └──────────────────────────────────┘
```

The system is a monolith: one Laravel application serves both the JSON API (`/api/*`) and the compiled Vue dashboard (via a Blade view + Vite). This avoids CORS complexity and keeps the assessment scope small. The only outbound network dependency is the Gemini API, and it is fully isolated behind a service interface.

## 2. Tech Stack

| Layer | Choice | Rationale |
|---|---|---|
| Backend framework | Laravel 11, PHP 8.2+ | Required by the brief; mature ecosystem, first-class testing tools |
| Database (dev/test) | SQLite | Zero-setup, file-based — clone and run immediately |
| Database (optional prod) | MySQL 8 | Drop-in swap via `.env`, no code changes needed |
| Auth (optional) | Laravel Sanctum | Lightweight token/cookie auth, native Laravel fit for an SPA + API |
| AI integration | Google Gemini via Laravel `Http` client | No heavy SDK; trivially mockable with `Http::fake()` |
| Frontend | Vue 3 (Composition API, `<script setup>`) + Vite | Ships natively with Laravel's Vite scaffolding; no separate frontend server needed |
| HTTP client (frontend) | Axios | Standard, simple wrapper around fetch with interceptors |
| Testing | PHPUnit (Laravel default; Pest is an acceptable substitute) | `Http::fake()` support, Laravel-native test helpers |
| Code style | Laravel Pint | Zero-config PSR-12 formatting |

## 3. Project Structure

```
mini-ielts-speaking/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── SpeakingQuestionController.php
│   │   │   ├── SpeakingSubmissionController.php
│   │   │   ├── SpeakingAttemptController.php
│   │   │   └── AuthController.php                 (optional)
│   │   ├── Requests/
│   │   │   └── SubmitSpeakingAnswerRequest.php
│   │   └── Resources/
│   │       ├── SpeakingQuestionResource.php
│   │       ├── SpeakingAttemptResource.php
│   │       └── SpeakingFeedbackResource.php
│   ├── Models/
│   │   ├── SpeakingQuestion.php
│   │   ├── SpeakingAttempt.php
│   │   └── SpeakingFeedback.php
│   ├── Services/
│   │   ├── Contracts/
│   │   │   └── EvaluationServiceInterface.php
│   │   └── GeminiEvaluationService.php
│   └── Exceptions/
│       └── EvaluationFailedException.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│       └── SpeakingQuestionSeeder.php
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── api.js
│   │   └── components/
│   │       ├── AttemptList.vue
│   │       ├── AttemptDetail.vue
│   │       └── SubmitAnswerForm.vue
│   └── views/dashboard.blade.php
├── routes/api.php
├── tests/Feature/
│   ├── SpeakingQuestionTest.php
│   ├── SpeakingSubmitTest.php
│   └── SpeakingAttemptTest.php
├── docs/
│   ├── database-schema.md
│   └── api-docs.md
├── .env.example
└── README.md
```

## 4. Database Design

### 4.1 Entity Relationship

```
users (1) ──── (N) speaking_attempts (N) ──── (1) speaking_questions
                        │
                        │ (1)
                        ▼
                 speaking_feedbacks (1)
```

### 4.2 Tables

**`speaking_questions`**

| Column | Type | Constraints |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| part | enum('part1','part2','part3') | not null |
| topic | varchar(150) | not null |
| prompt | text | not null |
| created_at, updated_at | timestamp | |

**`speaking_attempts`**

| Column | Type | Constraints |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| user_id | bigint unsigned | FK → `users.id`, nullable, `nullOnDelete` (nullable so auth can remain optional) |
| question_id | bigint unsigned | FK → `speaking_questions.id`, not null, `cascadeOnDelete` |
| answer_text | text | not null |
| status | enum('pending','evaluated','failed') | not null, default `'pending'` |
| created_at, updated_at | timestamp | |
| — | index | `(user_id)`, `(question_id)`, `(status)` |

**`speaking_feedbacks`**

| Column | Type | Constraints |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| attempt_id | bigint unsigned | FK → `speaking_attempts.id`, **unique**, `cascadeOnDelete` (enforces 1:1) |
| band_score | decimal(2,1) | not null |
| strengths | json | not null |
| areas_to_improve | json | not null |
| raw_response | json | nullable (stores the raw Gemini payload for debugging/audit) |
| created_at, updated_at | timestamp | |

### 4.3 Eloquent Relationships

- `User::attempts()` → `hasMany(SpeakingAttempt::class)`
- `SpeakingQuestion::attempts()` → `hasMany(SpeakingAttempt::class)`
- `SpeakingAttempt::user()` → `belongsTo(User::class)`
- `SpeakingAttempt::question()` → `belongsTo(SpeakingQuestion::class)`
- `SpeakingAttempt::feedback()` → `hasOne(SpeakingFeedback::class)`
- `SpeakingFeedback::attempt()` → `belongsTo(SpeakingAttempt::class)`

This schema and its rationale must also be written to `docs/database-schema.md` as a standalone reference (FR-1).

## 5. API Design

### 5.1 Response Envelope (applies to every endpoint)

Success:
```json
{
  "success": true,
  "data": { },
  "message": "OK"
}
```

Success (paginated list) adds a `meta` block:
```json
{
  "success": true,
  "data": [ ],
  "meta": { "current_page": 1, "per_page": 15, "total": 9 }
}
```

Error:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": { "answer_text": ["The answer text field is required."] }
}
```

`errors` is present only for validation-style failures (422); other errors may omit it.

### 5.2 `GET /api/speaking/questions`

- **Auth:** none required.
- **Query params:** `part` (optional, one of `part1`|`part2`|`part3`).
- **200 response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "part": "part1", "topic": "Hometown", "prompt": "Describe the town or city where you grew up." }
  ],
  "message": "OK"
}
```

### 5.3 `POST /api/speaking/submit`

- **Auth:** none required for the mandatory scope; if optional auth (FR-7) is implemented, `user_id` must be derived from the authenticated user, never from the request body.
- **Body:**
```json
{ "question_id": 3, "answer_text": "In my opinion, technology has changed..." }
```
- **Validation:** `question_id` required, integer, must exist in `speaking_questions`. `answer_text` required, string, `min:20`, `max:3000`.
- **Flow:**
  1. Validate via `SubmitSpeakingAnswerRequest`.
  2. Create `speaking_attempts` row, `status = 'pending'`.
  3. Call `EvaluationServiceInterface::evaluate($question, $answerText)`.
  4. On success: create `speaking_feedbacks`, set attempt `status = 'evaluated'`, return `201`.
  5. On failure (timeout, malformed response, API error): set attempt `status = 'failed'`, return a `502`-style envelope with `success: false` — the attempt row still exists and is visible in the dashboard/history.
- **201 response:**
```json
{
  "success": true,
  "data": {
    "attempt_id": 15,
    "status": "evaluated",
    "question": { "id": 3, "part": "part2", "topic": "Technology", "prompt": "..." },
    "answer_text": "...",
    "feedback": {
      "band_score": 6.5,
      "strengths": ["Good range of vocabulary", "Coherent structure"],
      "areas_to_improve": ["Grammatical accuracy with tenses", "Add more specific examples"]
    }
  },
  "message": "Evaluation completed."
}
```

### 5.4 `GET /api/speaking/attempts` *(supports FR-5, the dashboard)*

- **Auth:** none required in the mandatory scope; scoped to the authenticated user if FR-7 is implemented.
- Paginated (`per_page` default 15), eager-loads `question` and `feedback`, ordered by `created_at desc`.

### 5.5 `GET /api/speaking/attempts/{id}` *(supports FR-5)*

- Returns full detail: question, answer text, status, feedback (if present).
- Returns a `404` envelope if the attempt does not exist (or does not belong to the authenticated user, when auth is enabled).

### 5.6 Optional auth endpoints (FR-7)

`POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout` — standard Sanctum token issuance. When enabled, `speaking/submit` and `speaking/attempts*` are protected by the `auth:sanctum` middleware.

## 6. Service Layer: Gemini Integration

### 6.1 Contract

```php
interface EvaluationServiceInterface
{
    public function evaluate(SpeakingQuestion $question, string $answerText): array;
}
```

Returns an associative array: `['band_score' => float, 'strengths' => array, 'areas_to_improve' => array, 'raw_response' => array|null]`. Throws `EvaluationFailedException` on any failure.

### 6.2 `GeminiEvaluationService`

- Built on Laravel's `Http` facade, not a third-party SDK.
- Reads config from `config/services.php` → `services.gemini.*`, sourced from env.
- Builds a prompt that: (a) includes the question prompt and the user's answer, (b) explicitly instructs Gemini to reply with **only** a JSON object matching a fixed schema (`band_score`, `strengths`, `areas_to_improve`), no prose.
- Sets an explicit timeout (`GEMINI_TIMEOUT`, default 15s) and a single retry on transient network failure.
- Parses the response defensively: strips ```` ```json ```` fences if present, then `json_decode`s. Any parse failure or missing key throws `EvaluationFailedException`.
- Never logs the API key. On failure, logs a warning with the HTTP status and a truncated response body for debugging.

### 6.3 Wiring

Bind the interface in `AppServiceProvider`:
```php
$this->app->bind(EvaluationServiceInterface::class, GeminiEvaluationService::class);
```
Controllers depend on `EvaluationServiceInterface` only — never on the concrete Gemini class.

### 6.4 Configuration (`config/services.php`)

```php
'gemini' => [
    'key'      => env('GEMINI_API_KEY'),
    'model'    => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'timeout'  => env('GEMINI_TIMEOUT', 15),
],
```

## 7. Authentication & Authorization (Optional, FR-7)

- Laravel Sanctum, token-based (simplest fit for a decoupled Vue dashboard hitting a JSON API).
- `users` table is Laravel's default.
- Protected routes grouped under `auth:sanctum` middleware.
- `speaking_attempts.user_id` is always set server-side from `$request->user()->id` — never accepted from the request payload.
- If auth is not implemented, `user_id` stays `null` for all attempts and every attempt is globally visible — acceptable for the mandatory scope per the PRD's non-goals.

## 8. Frontend Architecture (Vue Dashboard)

- Single Blade entry view (`resources/views/dashboard.blade.php`) mounts one Vue app via Vite — no separate frontend server, no CORS to configure.
- **Component tree:**
  - `App.vue` — simple tab/state switch between "Practice" and "History" (no router needed at this scale).
  - `SubmitAnswerForm.vue` — fetches questions, lets the user pick one and submit an answer, shows the returned feedback immediately.
  - `AttemptList.vue` — fetches `GET /api/speaking/attempts`, renders a table (topic, part, band score, status) with a "View" action per row.
  - `AttemptDetail.vue` — renders full answer text + strengths/areas-to-improve for a selected attempt.
- **State:** plain `ref`/`reactive` from Vue's Composition API. No Vuex/Pinia — the app's state surface is small enough that a store adds indirection without benefit.
- **API access:** a single `resources/js/api.js` module wraps Axios (`baseURL: '/api'`), used by every component — no ad hoc `axios.get()` calls scattered around.
- **Styling:** plain, tidy CSS (scoped per component or a single shared stylesheet). No UI framework required.

## 9. Testing Strategy

| Test | Verifies |
|---|---|
| `SpeakingQuestionTest` | `GET /api/speaking/questions` returns seeded data; `part` filter works. |
| `SpeakingSubmitTest` (happy path) | `Http::fake()` mocks a successful Gemini response → attempt + feedback are persisted; response matches the envelope and contains `band_score`, `strengths`, `areas_to_improve`. |
| `SpeakingSubmitTest` (validation) | Missing/short `answer_text` or invalid `question_id` → `422` with field errors; nothing is persisted. |
| `SpeakingSubmitTest` (Gemini failure) | `Http::fake()` returns an error/non-200 → attempt is persisted with `status = 'failed'`; response is a controlled error, not a 500. |
| Unit test on `GeminiEvaluationService` | JSON parsing handles both plain JSON and JSON wrapped in a markdown code fence; malformed JSON throws `EvaluationFailedException`. |
| `SpeakingAttemptTest` | `GET /api/speaking/attempts` and `/attempts/{id}` return correctly shaped, eager-loaded data. |

`Http::fake()` is bound to the Gemini base URL specifically, so the entire suite runs with zero real network calls. Model factories (`SpeakingQuestionFactory`, `SpeakingAttemptFactory`) back all tests — no hand-built fixtures.

## 10. Configuration & Environment Variables

| Variable | Purpose | Committed default |
|---|---|---|
| `APP_ENV`, `APP_KEY`, `APP_DEBUG` | Laravel standard | Laravel defaults |
| `DB_CONNECTION` | `sqlite` for dev/test | `sqlite` |
| `GEMINI_API_KEY` | Gemini credential | **empty** in `.env.example` |
| `GEMINI_MODEL` | Model name | `gemini-1.5-flash` |
| `GEMINI_BASE_URL` | API base URL | official Gemini endpoint |
| `GEMINI_TIMEOUT` | HTTP timeout (seconds) | `15` |
| `SANCTUM_STATEFUL_DOMAINS` | Only if FR-7 is implemented | n/a |

`.env` is git-ignored by default in Laravel; `.env.example` ships with every key present but every secret value empty.

## 11. Security Considerations

- All input validated through Form Request classes; nothing trusted straight off the request object.
- Mass assignment guarded via `$fillable` on every model.
- `user_id` is always server-derived, never client-supplied.
- Rate limiting on `POST /api/speaking/submit` via Laravel's built-in `throttle:api` middleware (or a dedicated, tighter throttle) to avoid abusive/expensive Gemini calls.
- Secrets never logged; `raw_response` stored in `speaking_feedbacks` is the Gemini output only, not request headers or keys.
- `.env` and any credential files excluded via `.gitignore`; verified before every commit.

## 12. Deployment Considerations (Not Required, for Context)

The Gemini call is synchronous inside the request/response cycle, which is acceptable at this scale but is the one known scaling limitation — a production version would move evaluation to a queued job (`ShouldQueue`) and have the frontend poll or use websockets for the result. Typical deployment target: PHP-FPM + Nginx (VPS) or shared hosting with `public/` as document root, `.env` configured directly on the server (never committed), `php artisan migrate --force` run on deploy, and `npm run build` producing versioned assets consumed by Blade's `@vite` directive.

## 13. Logging & Error Handling

- Domain failures from the evaluation service are represented by `EvaluationFailedException`, caught in the controller (not left to bubble into a generic 500).
- Failures are logged via `Log::warning('gemini_evaluation_failed', [...])` with enough context to debug (status code, truncated body) but never the API key.
- All API error responses use the standard envelope (§5.1); no raw exception traces are ever returned to the client, even when `APP_DEBUG=true` locally (guard the API responses explicitly, don't rely on framework debug mode).
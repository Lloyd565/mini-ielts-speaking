# Mini IELTS Speaking Evaluation — Project Guidelines

These are project-specific rules on top of Laravel Boost's general framework guidelines. They apply to every task in this repository.

## Read First

Before implementing anything, read (in this order): `PRD.md` → `ARCHITECTURE-ESSENTIALS.md` → `ARCHITECTURE.md` (full detail only when needed). These three files are the source of truth for this project's requirements and design; they are not managed by Boost and will not be regenerated or overwritten.

## Project Summary

A Laravel API + Vue 3 dashboard for a mini IELTS Speaking practice tool. Users fetch practice questions, submit a text answer, and receive AI-generated feedback (band score, strengths, areas to improve) via Google Gemini, isolated behind a service interface.

## Non-Negotiable Rules

1. All Gemini access goes through `App\Services\Contracts\EvaluationServiceInterface` → `GeminiEvaluationService`. Never call Gemini directly from a controller.
2. Every test involving Gemini uses `Http::fake()`. The test suite must pass with zero network access.
3. Every `/api/*` response follows the standard envelope:
   - Success: `{ "success": true, "data": {}, "message": "..." }`
   - Error: `{ "success": false, "message": "...", "errors": {} }`
4. `speaking_attempts.status` (`pending` → `evaluated` | `failed`) must always reflect the real outcome. A failed Gemini call still leaves a visible, persisted attempt row — never silently dropped.
5. Never commit a real `GEMINI_API_KEY` or any other secret. `.env.example` ships with empty secret values only.
6. `user_id` on an attempt is always server-derived from the authenticated user (if auth is implemented) — never accepted from the request body.
7. `speaking_feedbacks.attempt_id` is unique — enforce one feedback per attempt at the database level.
8. All validation lives in `FormRequest` classes, never inline in a controller.
9. No queues, caches, or extra services unless a requirement in `PRD.md` explicitly calls for them — this is a small assessment project.
10. API errors never leak framework internals (stack traces, debug HTML), even with `APP_DEBUG=true` locally.

## Definition of Done

- `php artisan test` passes fully, offline.
- No new secrets in tracked files.
- New/changed endpoints follow the response envelope above.
- New DB columns/tables have a migration, reflected in `docs/database-schema.md` if the schema shape changes.
- New Gemini-dependent behavior has an `Http::fake()`-based test.

## When Requirements Are Ambiguous

Prefer the simplest solution consistent with `PRD.md`'s goals and non-goals. Note any assumption made in a code comment or commit message and proceed — do not block on it.
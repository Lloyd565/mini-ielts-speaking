# Database Schema

Standalone reference for the relational schema (FR-1). Source of truth: `ARCHITECTURE.md` §4.
A dbdiagram.io-ready DBML version of this schema lives at `docs/database.dbml`.

## Entity Relationship

```
users (1) ──── (N) speaking_attempts (N) ──── (1) speaking_questions
                    │
                    │ (1)
                    ▼
             speaking_feedbacks (1)
```

- One **user** has many **attempts** (nullable — auth is optional, FR-7).
- One **question** has many **attempts**.
- One **attempt** has exactly one **feedback** (enforced by a unique constraint).

## Tables

### `speaking_questions`

| Column | Type | Constraints |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| part | enum('part1','part2','part3') | not null |
| topic | varchar(150) | not null |
| prompt | text | not null |
| created_at, updated_at | timestamp | |

### `speaking_attempts`

| Column | Type | Constraints |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| user_id | bigint unsigned | FK → `users.id`, nullable, `nullOnDelete` (nullable so auth can remain optional) |
| question_id | bigint unsigned | FK → `speaking_questions.id`, not null, `cascadeOnDelete` |
| answer_text | text | not null |
| status | enum('pending','evaluated','failed') | not null, default `'pending'` |
| created_at, updated_at | timestamp | |
| — | index | `(user_id)`, `(question_id)`, `(status)` |

### `speaking_feedbacks`

| Column | Type | Constraints |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| attempt_id | bigint unsigned | FK → `speaking_attempts.id`, **unique**, `cascadeOnDelete` (enforces 1:1) |
| band_score | decimal(2,1) | not null |
| strengths | json | not null |
| areas_to_improve | json | not null |
| raw_response | json | nullable (raw Gemini payload for debugging/audit) |
| created_at, updated_at | timestamp | |

## Eloquent Relationships

- `User::attempts()` → `hasMany(SpeakingAttempt::class)`
- `SpeakingQuestion::attempts()` → `hasMany(SpeakingAttempt::class)`
- `SpeakingAttempt::user()` → `belongsTo(User::class)`
- `SpeakingAttempt::question()` → `belongsTo(SpeakingQuestion::class)`
- `SpeakingAttempt::feedback()` → `hasOne(SpeakingFeedback::class)`
- `SpeakingFeedback::attempt()` → `belongsTo(SpeakingAttempt::class)`

## Design notes

- `speaking_attempts.user_id` is **always server-derived** from the authenticated user, never accepted from the request body. When auth is not implemented it stays `NULL` and all attempts are globally visible (acceptable per PRD non-goals).
- `speaking_attempts.status` always reflects the real outcome: `pending` → `evaluated` on success, `failed` when the Gemini call fails. A failed evaluation still leaves a visible, persisted attempt row — never silently dropped.
- `speaking_feedbacks.attempt_id` is unique at the database level, enforcing one feedback per attempt.

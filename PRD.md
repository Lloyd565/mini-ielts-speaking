# Product Requirements Document (PRD)

**Product:** Mini IELTS Speaking Evaluation API + Dashboard
**Version:** 1.0
**Status:** Draft — ready for implementation
**Last updated:** 2026-09-02

**Companion docs:** `ARCHITECTURE.md` (full technical design) · `ARCHITECTURE-ESSENTIALS.md` (quick reference) · `AGENTS.md` (agent working rules)

---

## 1. Summary

A small full-stack application that lets a user practice IELTS Speaking by answering practice questions in text form and receiving an automated, AI-generated evaluation (estimated band score, strengths, areas to improve) powered by Google Gemini. The backend is built with Laravel and exposes a REST API; a lightweight Vue.js dashboard consumes that API to let users browse questions, submit answers, and review past attempts and feedback.

This project is a technical assessment deliverable for a Backend Developer Intern role. It should be treated as production-quality in miniature: clean architecture, tests, and documentation — not a throwaway prototype.

## 2. Problem Statement

IELTS Speaking practice usually requires a human evaluator (teacher, tutor, study partner) to give feedback, which isn't always available on demand. Learners need a fast, low-friction way to:

- Get a set of realistic practice questions (Part 1, 2, 3).
- Submit an answer and receive structured, actionable feedback quickly.
- Track past attempts and review feedback over time.

## 3. Target Users

| Persona | Description | Primary Need |
|---|---|---|
| IELTS candidate (end user) | Self-studying for the IELTS Speaking module | Practice questions + fast, structured feedback on their answer |
| Assessment reviewer | Reviews this codebase as part of a hiring process | Clear evidence of backend engineering competence: data modeling, API design, third-party integration, testing, documentation |
| Agentic AI / developer building this project | Consumes this PRD + `ARCHITECTURE.md` to implement the system | Complete, unambiguous requirements it can build against without guessing |

## 4. Goals

- **G1** — Provide a simple, working end-to-end flow: list questions → submit a text answer → receive AI-generated evaluation → view it later in a dashboard.
- **G2** — Demonstrate solid relational data modeling and clean Laravel API design.
- **G3** — Isolate the third-party AI dependency (Gemini) behind a swappable service layer that can be faked in tests.
- **G4** — Ship with migrations, seeders, automated tests, and documentation so the project can be cloned and run by a stranger in under 10 minutes.
- **G5** *(optional)* — Support per-user accounts so attempts are scoped to a user.

## 5. Non-Goals / Out of Scope

- Real audio recording, speech-to-text, or pronunciation/fluency scoring — input is text only.
- Polished, pixel-perfect UI/UX design. The dashboard must be usable and clean, not visually elaborate.
- Multi-language support.
- Payment, subscription, or admin back-office tooling.
- Horizontal scaling, queues, or caching infrastructure — this is a small assessment project, not a production SaaS.
- Actually deploying the project live (deployment experience may be *described* in the README; deployment itself is not required).

## 6. Functional Requirements

### 6.1 Mandatory

| ID | Requirement |
|---|---|
| FR-1 | A relational schema exists for speaking questions, attempts, and feedback, with documented relationships. |
| FR-2 | `GET /api/speaking/questions` returns a list of speaking questions, each with `part`, `topic`, and `prompt`. Supports optional filtering by `part`. |
| FR-3 | `POST /api/speaking/submit` accepts `question_id` and `answer_text`, validates the payload, persists the attempt, requests an evaluation, persists the evaluation, and returns the result in one response. |
| FR-4 | A service layer integrates with Google Gemini to produce feedback containing at minimum: `band_score`, `strengths` (list), `areas_to_improve` (list). Configuration (API key, model, base URL) is environment-based; no credentials are ever committed to the repository. |
| FR-5 | A Vue.js dashboard displays a list of past attempts/results and a detail view showing full feedback for a given attempt. The dashboard reads real data from the backend API (no mock data shipped). |
| FR-6 | The project includes migrations, a seeder with sample questions, automated tests covering the two main endpoints, and a README with setup/run instructions. Gemini calls in tests are mocked/faked — the test suite must pass with no network access. |

### 6.2 Optional

| ID | Requirement |
|---|---|
| FR-7 | Simple register/login (e.g., Laravel Sanctum) so attempts are associated with a user account. |
| FR-8 | Short API documentation (Markdown and/or Postman/Bruno/Swagger collection) with example requests/responses for every endpoint. |
| FR-9 | README section describing prior real-world experience deploying a Laravel project to a VPS or shared hosting (descriptive only — not a requirement to deploy this project). |

## 7. Key User Stories

1. As a learner, I want to see a list of speaking questions grouped by part, so I can pick one to practice.
2. As a learner, I want to submit a written answer to a question and get feedback within a few seconds, so I know how I'm doing.
3. As a learner, I want to see my past attempts and their feedback in a dashboard, so I can track my progress.
4. As a learner *(optional)*, I want to log in so my attempts stay private to me.
5. As a reviewer, I want to run one command to set up the project and one command to run tests, so I can evaluate the work quickly.

## 8. Acceptance Criteria

- A fresh clone of the repo can be set up and run following only the README, ending with a working API and dashboard.
- `POST /api/speaking/submit` with a valid payload returns a `2xx` response containing `band_score`, `strengths`, and `areas_to_improve`, and a corresponding row exists in the database.
- Submitting an invalid payload (missing/short `answer_text`, invalid `question_id`) returns a `422` with clear validation errors — no unhandled exceptions.
- If the Gemini call fails or times out, the attempt is still saved (status `failed`) and the API returns a controlled, informative error — never a raw 500.
- The full test suite passes fully offline (Gemini is faked, never called for real).
- The dashboard displays real attempts pulled from the API and their feedback detail.
- No API keys, tokens, or secrets appear anywhere in the git history or tracked files (`.env` is git-ignored; `.env.example` has empty secret values).

## 9. Constraints & Assumptions

- Single developer/agent working within limited time — favor simplicity and clarity over completeness or scale.
- SQLite is acceptable for local development and testing to minimize setup friction.
- Gemini API availability is not guaranteed during grading; the system must degrade gracefully and tests must not depend on it.
- No design system or component library is required for the dashboard; plain, tidy CSS is sufficient.

## 10. Success Metrics (for this assessment)

- **Correctness** — endpoints behave as specified, including edge cases.
- **Code quality** — consistent structure, Form Requests for validation, service/interface pattern for Gemini, no business logic in controllers beyond orchestration.
- **Data modeling** — schema is normalized, relationships are explicit and documented.
- **Test coverage** — happy path + at least one failure/validation path per main endpoint.
- **Documentation** — a reviewer with no prior context can understand and run the project from the README alone.
# API Documentation

Base URL: `/api` (e.g. `http://localhost:8000/api`).

## Response envelope

Every endpoint returns a standard envelope.

**Success:**
```json
{
  "success": true,
  "data": {},
  "message": "OK"
}
```

**Success (paginated list)** adds a `meta` block:
```json
{
  "success": true,
  "data": [],
  "meta": { "current_page": 1, "per_page": 15, "total": 9 }
}
```

**Error:**
```json
{
  "success": false,
  "message": "...",
  "errors": {}
}
```

`errors` is present only on validation failures (`422`); other errors may omit it.

---

## `GET /api/speaking/questions`

List speaking questions, optionally filtered by part.

**Auth:** none.

**Query parameters:**

| Param | Type | Description |
|---|---|---|
| `part` | string | Optional. One of `part1`, `part2`, `part3`. |

**Example request:**
```http
GET /api/speaking/questions?part=part2
```

**Example `200 OK` response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 4,
      "part": "part2",
      "topic": "A Memorable Trip",
      "prompt": "Describe a memorable trip you have taken. You should say: where you went, who you went with, what you did there, and explain why it was memorable."
    },
    {
      "id": 5,
      "part": "part2",
      "topic": "A Person You Admire",
      "prompt": "Describe a person you admire. You should say: who this person is, how you know this person, what qualities this person has, and explain why you admire them."
    }
  ],
  "message": "OK"
}
```

**Invalid filter — `422 Unprocessable Content`:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "part": ["The selected part is invalid."]
  }
}
```

---

## `POST /api/speaking/submit`

Submit an answer and receive an AI-generated evaluation in the same response.

**Auth:** none (if FR-7 auth is implemented, `user_id` is derived server-side from the authenticated user — never from the request body).

**Rate limit:** 10 requests/minute per user/IP (`speaking-submit` limiter).

**Body:**

| Field | Type | Rules |
|---|---|---|
| `question_id` | integer | required, must exist in `speaking_questions` |
| `answer_text` | string | required, min:20, max:3000 |

**Example request:**
```http
POST /api/speaking/submit
Content-Type: application/json

{
  "question_id": 4,
  "answer_text": "Last summer I travelled to Da Lat with my closest friends. We spent three days exploring the night market, cycling around Xuan Huong lake, and trying local food. It was memorable because it was the first trip we planned entirely by ourselves."
}
```

**Example `201 Created` response:**
```json
{
  "success": true,
  "data": {
    "attempt_id": 15,
    "status": "evaluated",
    "question": {
      "id": 4,
      "part": "part2",
      "topic": "A Memorable Trip",
      "prompt": "Describe a memorable trip you have taken..."
    },
    "answer_text": "Last summer I travelled to Da Lat...",
    "feedback": {
      "band_score": 6.5,
      "strengths": [
        "Good range of vocabulary",
        "Coherent structure"
      ],
      "areas_to_improve": [
        "Grammatical accuracy with tenses",
        "Add more specific examples"
      ]
    }
  },
  "message": "Evaluation completed."
}
```

**Validation failure — `422 Unprocessable Content`** (nothing is persisted):
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "question_id": ["The selected question id is invalid."],
    "answer_text": ["The answer text field must be at least 20 characters."]
  }
}
```

**Gemini failure — `502 Bad Gateway`.** The attempt is still persisted with `status: "failed"` and remains visible in the attempts list:
```json
{
  "success": false,
  "message": "Your answer was saved, but the evaluation service is unavailable. Please try again later.",
  "data": {
    "attempt_id": 16,
    "status": "failed"
  }
}
```

---

## `GET /api/speaking/attempts`

Paginated list of past attempts, newest first, with `question` and `feedback` eager-loaded.

**Auth:** none (scoped to the authenticated user when FR-7 is implemented).

**Query parameters:**

| Param | Type | Description |
|---|---|---|
| `page` | integer | Optional, default `1`. |
| `per_page` | integer | Optional, default `15`. |

**Example request:**
```http
GET /api/speaking/attempts?per_page=2
```

**Example `200 OK` response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "status": "evaluated",
      "answer_text": "Last summer I travelled to Da Lat...",
      "question": {
        "id": 4,
        "part": "part2",
        "topic": "A Memorable Trip",
        "prompt": "Describe a memorable trip you have taken..."
      },
      "feedback": {
        "band_score": 6.5,
        "strengths": ["Good range of vocabulary", "Coherent structure"],
        "areas_to_improve": ["Grammatical accuracy with tenses", "Add more specific examples"]
      },
      "created_at": "2026-09-03T08:15:00+00:00"
    },
    {
      "id": 14,
      "status": "failed",
      "answer_text": "In my opinion, technology has changed...",
      "question": {
        "id": 7,
        "part": "part3",
        "topic": "Technology and Society",
        "prompt": "How has technology changed the way people communicate?..."
      },
      "feedback": null,
      "created_at": "2026-09-03T07:58:12+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 2,
    "total": 9
  }
}
```

`feedback` is `null` for attempts whose evaluation failed or is still pending.

---

## `GET /api/speaking/attempts/{id}`

Full detail of a single attempt: question, answer text, status, and feedback (if present).

**Auth:** none (or the owner only, when FR-7 is implemented).

**Example request:**
```http
GET /api/speaking/attempts/15
```

**Example `200 OK` response:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "status": "evaluated",
    "answer_text": "Last summer I travelled to Da Lat...",
    "question": {
      "id": 4,
      "part": "part2",
      "topic": "A Memorable Trip",
      "prompt": "Describe a memorable trip you have taken..."
    },
    "feedback": {
      "band_score": 6.5,
      "strengths": ["Good range of vocabulary", "Coherent structure"],
      "areas_to_improve": ["Grammatical accuracy with tenses", "Add more specific examples"]
    },
    "created_at": "2026-09-03T08:15:00+00:00"
  },
  "message": "OK"
}
```

**Not found — `404 Not Found`:**
```json
{
  "success": false,
  "message": "Resource not found."
}
```

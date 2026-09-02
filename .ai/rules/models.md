---
paths:
  - 'app/Models/*.php'
---

# Models

## SpeakingFeedback needs explicit $table
Eloquent pluralizes `SpeakingFeedback` to `speaking_feedback` (feedback is uncountable), but the table is `speaking_feedbacks` per ARCHITECTURE.md. The model sets `protected $table = 'speaking_feedbacks';` — do not remove it.

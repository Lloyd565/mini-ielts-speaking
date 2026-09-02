<?php

namespace App\Models;

use Database\Factories\SpeakingAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'question_id', 'answer_text', 'status'])]
class SpeakingAttempt extends Model
{
    /** @use HasFactory<SpeakingAttemptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SpeakingQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(SpeakingQuestion::class, 'question_id');
    }

    /**
     * @return HasOne<SpeakingFeedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(SpeakingFeedback::class, 'attempt_id');
    }
}

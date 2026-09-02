<?php

namespace App\Models;

use Database\Factories\SpeakingQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['part', 'topic', 'prompt'])]
class SpeakingQuestion extends Model
{
    /** @use HasFactory<SpeakingQuestionFactory> */
    use HasFactory;

    /**
     * @return HasMany<SpeakingAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(SpeakingAttempt::class, 'question_id');
    }
}

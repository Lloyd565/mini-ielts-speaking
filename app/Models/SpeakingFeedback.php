<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attempt_id', 'band_score', 'strengths', 'areas_to_improve', 'raw_response'])]
class SpeakingFeedback extends Model
{
    protected $table = 'speaking_feedbacks';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'band_score' => 'decimal:1',
            'strengths' => 'array',
            'areas_to_improve' => 'array',
            'raw_response' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SpeakingAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(SpeakingAttempt::class, 'attempt_id');
    }
}

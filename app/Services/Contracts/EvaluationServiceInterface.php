<?php

namespace App\Services\Contracts;

use App\Exceptions\EvaluationFailedException;
use App\Models\SpeakingQuestion;

interface EvaluationServiceInterface
{
    /**
     * Evaluate a spoken answer and return structured feedback.
     *
     * @return array{band_score: float, strengths: array<int, string>, areas_to_improve: array<int, string>, raw_response: array<string, mixed>|null}
     *
     * @throws EvaluationFailedException
     */
    public function evaluate(SpeakingQuestion $question, string $answerText): array;
}

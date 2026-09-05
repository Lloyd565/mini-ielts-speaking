<?php

namespace App\Services;

use App\Models\SpeakingQuestion;
use App\Services\Contracts\EvaluationServiceInterface;

/**
 * Local stand-in used when no Gemini API key is configured, so the app is
 * runnable end-to-end without credentials.
 */
class FakeEvaluationService implements EvaluationServiceInterface
{
    public function evaluate(SpeakingQuestion $question, string $answerText): array
    {
        $words = str_word_count($answerText);

        return [
            // Crude length-based band so the UI shows something that moves.
            'band_score' => min(9.0, max(4.0, round($words / 20, 1) * 0.5 + 4.0)),
            'strengths' => [
                'Answered the question on '.$question->topic.' directly.',
                'Used '.$words.' words, enough to develop the idea.',
            ],
            'areas_to_improve' => [
                'Add more specific examples.',
                'Vary sentence structure and linking words.',
            ],
            'raw_response' => ['fake' => true],
        ];
    }
}

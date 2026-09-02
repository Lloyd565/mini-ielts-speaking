<?php

namespace App\Services;

use App\Exceptions\EvaluationFailedException;
use App\Models\SpeakingQuestion;
use App\Services\Contracts\EvaluationServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GeminiEvaluationService implements EvaluationServiceInterface
{
    /**
     * Evaluate a spoken answer via the Gemini API.
     *
     * @return array{band_score: float, strengths: array<int, string>, areas_to_improve: array<int, string>, raw_response: array<string, mixed>|null}
     *
     * @throws EvaluationFailedException
     */
    public function evaluate(SpeakingQuestion $question, string $answerText): array
    {
        $rawResponse = $this->requestEvaluation($question, $answerText);

        return $this->parseEvaluation($rawResponse);
    }

    /**
     * Call Gemini and return the decoded JSON payload.
     *
     * @return array<string, mixed>
     *
     * @throws EvaluationFailedException
     */
    private function requestEvaluation(SpeakingQuestion $question, string $answerText): array
    {
        $key = config('services.gemini.key');

        if (empty($key)) {
            throw new EvaluationFailedException('Gemini API key is not configured.');
        }

        $url = sprintf(
            '%s/models/%s:generateContent',
            rtrim((string) config('services.gemini.base_url'), '/'),
            config('services.gemini.model'),
        );

        try {
            $response = Http::timeout((int) config('services.gemini.timeout', 15))
                ->retry(1, 0, throw: false)
                ->withHeaders(['x-goog-api-key' => $key])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $this->buildPrompt($question, $answerText)]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('gemini_evaluation_failed', ['reason' => 'connection', 'error' => $e->getMessage()]);

            throw new EvaluationFailedException('Could not reach the evaluation service.', previous: $e);
        }

        if (! $response->successful()) {
            Log::warning('gemini_evaluation_failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            throw new EvaluationFailedException("Evaluation service returned HTTP {$response->status()}.");
        }

        return $response->json() ?? [];
    }

    /**
     * Build the prompt instructing Gemini to answer with JSON only.
     */
    private function buildPrompt(SpeakingQuestion $question, string $answerText): string
    {
        return <<<PROMPT
        You are an IELTS Speaking examiner. Evaluate the following written answer to an IELTS Speaking {$question->part} question.

        Question topic: {$question->topic}
        Question prompt: {$question->prompt}

        Candidate's answer:
        {$answerText}

        Reply with ONLY a JSON object (no prose, no markdown) matching this exact schema:
        {
          "band_score": <number from 0.0 to 9.0 in 0.5 steps>,
          "strengths": [<2-4 short strings describing what the candidate did well>],
          "areas_to_improve": [<2-4 short strings describing what the candidate should improve>]
        }
        PROMPT;
    }

    /**
     * Extract and validate the structured feedback from the Gemini payload.
     *
     * @param  array<string, mixed>  $rawResponse
     * @return array{band_score: float, strengths: array<int, string>, areas_to_improve: array<int, string>, raw_response: array<string, mixed>}
     *
     * @throws EvaluationFailedException
     */
    private function parseEvaluation(array $rawResponse): array
    {
        $text = $rawResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! is_string($text)) {
            Log::warning('gemini_evaluation_failed', [
                'reason' => 'unexpected_structure',
                'body' => Str::limit((string) json_encode($rawResponse), 500),
            ]);

            throw new EvaluationFailedException('Evaluation service returned an unexpected response structure.');
        }

        // Gemini sometimes wraps JSON in a markdown code fence despite instructions.
        $json = trim($text);
        if (preg_match('/^```(?:json)?\s*(?<json>.*?)\s*```$/s', $json, $matches)) {
            $json = $matches['json'];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)
            || ! isset($decoded['band_score'])
            || ! is_numeric($decoded['band_score'])
            || ! isset($decoded['strengths'])
            || ! is_array($decoded['strengths'])
            || ! isset($decoded['areas_to_improve'])
            || ! is_array($decoded['areas_to_improve'])
        ) {
            Log::warning('gemini_evaluation_failed', [
                'reason' => 'parse_error',
                'body' => Str::limit($text, 500),
            ]);

            throw new EvaluationFailedException('Could not parse the evaluation response.');
        }

        return [
            'band_score' => (float) $decoded['band_score'],
            'strengths' => array_values($decoded['strengths']),
            'areas_to_improve' => array_values($decoded['areas_to_improve']),
            'raw_response' => $rawResponse,
        ];
    }
}

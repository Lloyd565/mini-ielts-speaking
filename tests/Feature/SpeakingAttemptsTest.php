<?php

namespace Tests\Feature;

use App\Models\SpeakingAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakingAttemptsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an attempt with feedback attached.
     */
    private function createEvaluatedAttempt(array $attemptAttributes = []): SpeakingAttempt
    {
        $attempt = SpeakingAttempt::factory()->create(array_merge(
            ['status' => 'evaluated'],
            $attemptAttributes,
        ));

        $attempt->feedback()->create([
            'band_score' => 6.5,
            'strengths' => ['Good range of vocabulary'],
            'areas_to_improve' => ['Grammatical accuracy with tenses'],
        ]);

        return $attempt;
    }

    public function test_it_returns_a_paginated_list_of_attempts_with_question_and_feedback(): void
    {
        $this->createEvaluatedAttempt();
        $this->createEvaluatedAttempt();
        $failedAttempt = SpeakingAttempt::factory()->create(['status' => 'failed']);

        $response = $this->getJson('/api/speaking/attempts');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'answer_text',
                        'question' => ['id', 'part', 'topic', 'prompt'],
                        'feedback',
                        'created_at',
                    ],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 15);

        // Evaluated attempts carry full feedback; failed ones expose null.
        $evaluated = collect($response->json('data'))->firstWhere('status', 'evaluated');
        $this->assertArrayHasKey('band_score', $evaluated['feedback']);
        $this->assertArrayHasKey('strengths', $evaluated['feedback']);
        $this->assertArrayHasKey('areas_to_improve', $evaluated['feedback']);

        $failed = collect($response->json('data'))->firstWhere('id', $failedAttempt->id);
        $this->assertNull($failed['feedback']);
    }

    public function test_it_orders_attempts_newest_first_and_supports_per_page(): void
    {
        $oldest = $this->createEvaluatedAttempt(['created_at' => now()->subDays(2)]);
        $newest = $this->createEvaluatedAttempt(['created_at' => now()]);

        $response = $this->getJson('/api/speaking/attempts?per_page=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);

        $this->assertNotSame($oldest->id, $response->json('data.0.id'));
    }

    public function test_it_returns_the_full_detail_of_a_single_attempt(): void
    {
        $attempt = $this->createEvaluatedAttempt();

        $response = $this->getJson("/api/speaking/attempts/{$attempt->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'answer_text',
                    'question' => ['id', 'part', 'topic', 'prompt'],
                    'feedback' => ['band_score', 'strengths', 'areas_to_improve'],
                    'created_at',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'OK',
                'data' => [
                    'id' => $attempt->id,
                    'status' => 'evaluated',
                    'question' => ['id' => $attempt->question_id],
                    'feedback' => ['band_score' => 6.5],
                ],
            ]);
    }

    public function test_it_returns_a_404_envelope_for_a_missing_attempt(): void
    {
        $response = $this->getJson('/api/speaking/attempts/999');

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
            ]);
    }
}

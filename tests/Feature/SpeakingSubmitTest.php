<?php

namespace Tests\Feature;

use App\Models\SpeakingQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpeakingSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Gemini is faked via Http::fake(), but the service still requires a configured key.
        config()->set('services.gemini.key', 'fake-test-key');
    }

    /**
     * Build a fake successful Gemini API payload.
     *
     * @return array<string, mixed>
     */
    private function fakeGeminiSuccessPayload(): array
    {
        return [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'band_score' => 6.5,
                                    'strengths' => ['Good range of vocabulary', 'Coherent structure'],
                                    'areas_to_improve' => ['Grammatical accuracy with tenses', 'Add more specific examples'],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_it_evaluates_an_answer_and_persists_attempt_and_feedback(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiSuccessPayload(), 200),
        ]);

        $question = SpeakingQuestion::factory()->create(['part' => 'part2', 'topic' => 'Technology']);
        $answerText = 'In my opinion, technology has changed communication significantly in recent years.';

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => $answerText,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'attempt_id',
                    'status',
                    'question' => ['id', 'part', 'topic', 'prompt'],
                    'answer_text',
                    'feedback' => ['band_score', 'strengths', 'areas_to_improve'],
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Evaluation completed.',
                'data' => [
                    'status' => 'evaluated',
                    'feedback' => [
                        'band_score' => 6.5,
                        'strengths' => ['Good range of vocabulary', 'Coherent structure'],
                        'areas_to_improve' => ['Grammatical accuracy with tenses', 'Add more specific examples'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('speaking_attempts', [
            'question_id' => $question->id,
            'answer_text' => $answerText,
            'status' => 'evaluated',
        ]);

        $this->assertDatabaseHas('speaking_feedbacks', [
            'attempt_id' => $response->json('data.attempt_id'),
            'band_score' => 6.5,
        ]);
    }

    public function test_it_rejects_invalid_payloads_without_persisting_anything(): void
    {
        Http::fake();

        $question = SpeakingQuestion::factory()->create();

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id + 100,
            'answer_text' => 'too short',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.',
            ])
            ->assertJsonValidationErrors(['question_id', 'answer_text']);

        $this->assertDatabaseCount('speaking_attempts', 0);
        $this->assertDatabaseCount('speaking_feedbacks', 0);
        Http::assertNothingSent();
    }

    public function test_it_marks_the_attempt_as_failed_when_gemini_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'service unavailable'], 503),
        ]);

        $question = SpeakingQuestion::factory()->create();

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'This is a perfectly valid answer that is long enough to pass validation.',
        ]);

        $response
            ->assertStatus(502)
            ->assertJson([
                'success' => false,
                'data' => ['status' => 'failed'],
            ]);

        $this->assertDatabaseHas('speaking_attempts', [
            'question_id' => $question->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseCount('speaking_feedbacks', 0);
    }
}

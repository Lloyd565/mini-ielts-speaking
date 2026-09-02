<?php

namespace Tests\Feature;

use App\Models\SpeakingQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakingQuestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_questions_in_the_standard_envelope(): void
    {
        SpeakingQuestion::factory()->count(3)->create();

        $response = $this->getJson('/api/speaking/questions');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'part', 'topic', 'prompt'],
                ],
                'message',
            ])
            ->assertJson(['success' => true, 'message' => 'OK'])
            ->assertJsonCount(3, 'data');
    }

    public function test_it_filters_questions_by_part(): void
    {
        SpeakingQuestion::factory()->count(2)->create(['part' => 'part1']);
        SpeakingQuestion::factory()->create(['part' => 'part2']);
        SpeakingQuestion::factory()->create(['part' => 'part3']);

        $response = $this->getJson('/api/speaking/questions?part=part2');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.part', 'part2');
    }

    public function test_it_rejects_an_invalid_part_filter(): void
    {
        $response = $this->getJson('/api/speaking/questions?part=part9');

        $response
            ->assertUnprocessable()
            ->assertJsonStructure(['success', 'message', 'errors' => ['part']])
            ->assertJson(['success' => false, 'message' => 'The given data was invalid.']);
    }
}

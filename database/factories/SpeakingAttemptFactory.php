<?php

namespace Database\Factories;

use App\Models\SpeakingAttempt;
use App\Models\SpeakingQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpeakingAttempt>
 */
class SpeakingAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Auth stays optional (FR-7), so attempts default to anonymous.
            'user_id' => null,
            'question_id' => SpeakingQuestion::factory(),
            'answer_text' => fake()->paragraphs(3, true),
            'status' => 'pending',
        ];
    }
}

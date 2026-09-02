<?php

namespace Database\Factories;

use App\Models\SpeakingQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpeakingQuestion>
 */
class SpeakingQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part' => fake()->randomElement(['part1', 'part2', 'part3']),
            'topic' => fake()->words(3, true),
            'prompt' => fake()->sentences(2, true),
        ];
    }
}

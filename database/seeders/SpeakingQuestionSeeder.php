<?php

namespace Database\Seeders;

use App\Models\SpeakingQuestion;
use Illuminate\Database\Seeder;

class SpeakingQuestionSeeder extends Seeder
{
    /**
     * Seed the speaking questions table.
     */
    public function run(): void
    {
        $questions = [
            [
                'part' => 'part1',
                'topic' => 'Hometown',
                'prompt' => 'Do you like your hometown? What do you enjoy most about living there?',
            ],
            [
                'part' => 'part1',
                'topic' => 'Work or Studies',
                'prompt' => 'Do you work or are you a student? What do you find most interesting about your job or studies?',
            ],
            [
                'part' => 'part1',
                'topic' => 'Reading',
                'prompt' => 'Do you like reading books? What kind of books do you prefer and why?',
            ],
            [
                'part' => 'part2',
                'topic' => 'A Memorable Trip',
                'prompt' => 'Describe a memorable trip you have taken. You should say: where you went, who you went with, what you did there, and explain why it was memorable.',
            ],
            [
                'part' => 'part2',
                'topic' => 'A Person You Admire',
                'prompt' => 'Describe a person you admire. You should say: who this person is, how you know this person, what qualities this person has, and explain why you admire them.',
            ],
            [
                'part' => 'part2',
                'topic' => 'A Useful Skill',
                'prompt' => 'Describe a new skill you would like to learn. You should say: what the skill is, why you want to learn it, how you would learn it, and explain how it would be useful to you.',
            ],
            [
                'part' => 'part3',
                'topic' => 'Technology and Society',
                'prompt' => 'How has technology changed the way people communicate? Do you think these changes are positive or negative?',
            ],
            [
                'part' => 'part3',
                'topic' => 'Media and Information',
                'prompt' => 'Do you think people today rely too much on media for information? What problems can this cause?',
            ],
        ];

        foreach ($questions as $question) {
            SpeakingQuestion::create($question);
        }
    }
}

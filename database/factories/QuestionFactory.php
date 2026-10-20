<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'sba',
            'exam' => 'all',
            'title' => rtrim(fake()->unique()->sentence(4), '.'),
            'stem' => '<p>Which is correct?</p>',
            'options' => [['text' => 'Right', 'is_correct' => true], ['text' => 'Wrong', 'is_correct' => false], ['text' => 'Also wrong', 'is_correct' => false]],
            'explanation' => '<p>Because.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }
}

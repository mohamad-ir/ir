<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClinicalCaseFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(12),
            'history' => '<p>'.fake()->paragraph().'</p>',
            'findings' => '<p>Subretinal fluid with intact RPE.</p>',
            'diagnosis' => 'Central serous chorioretinopathy',
            'discussion' => '<p>'.fake()->paragraph().'</p>',
            'teaching_points' => [['point' => 'Ask about steroid use.']],
            'quiz_options' => [['text' => 'CSR', 'is_correct' => true], ['text' => 'nAMD', 'is_correct' => false]],
            'difficulty' => 'beginner',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()->addDays(3)]);
    }
}

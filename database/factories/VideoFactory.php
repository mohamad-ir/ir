<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(10),
            'provider' => 'youtube',
            'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'duration_seconds' => 312,
            'chapters' => [['time' => '0:00', 'title' => 'Intro'], ['time' => '2:10', 'title' => 'Findings']],
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }
}

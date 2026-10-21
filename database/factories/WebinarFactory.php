<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebinarFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(12),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'starts_at' => now()->addDays(5)->setTime(18, 0),
            'duration_minutes' => 60,
            'platform' => 'zoom',
            'join_url' => 'https://zoom.us/j/9876543210?pwd=secret',
            'join_details' => 'Passcode 4242',
            'price' => 0,
            'currency' => 'EUR',
            'free_for_members' => true,
            'registration_open' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }

    public function paid(int $price = 2500): static
    {
        return $this->state(['price' => $price]);
    }
}

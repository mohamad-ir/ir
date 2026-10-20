<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'status' => 'subscribed',
            'interests' => ['weekly_case'],
            'confirmed_at' => now(),
        ];
    }
}

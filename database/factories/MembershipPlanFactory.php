<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Monthly',
            'slug' => 'monthly-'.fake()->unique()->numberBetween(1, 99999),
            'features' => [['text' => 'All courses']],
            'price' => 1500,
            'currency' => 'EUR',
            'interval' => 'month',
            'trial_days' => 0,
            'is_active' => true,
        ];
    }
}

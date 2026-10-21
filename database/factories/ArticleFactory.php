<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(15),
            'body' => '<h2>Introduction</h2><p>'.fake()->paragraphs(3, true).' [1]</p><h2>Summary</h2><p>'.fake()->paragraph().'</p>',
            'references' => [['citation' => 'Author A. A reference. Journal. 2020;1:1-2.']],
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }
}

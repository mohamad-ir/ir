<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'subtitle' => fake()->sentence(8),
            'summary' => fake()->sentence(14),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'outcomes' => [['text' => 'Read an OCT systematically']],
            'level' => 'beginner',
            'price' => 4900,
            'currency' => 'EUR',
            'included_in_membership' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ];
    }

    public function free(): static
    {
        return $this->state(['price' => 0]);
    }

    public function withLessons(int $count = 3, int $previews = 1): static
    {
        return $this->afterCreating(function (Course $course) use ($count, $previews) {
            foreach (range(1, $count) as $i) {
                Lesson::create([
                    'course_id' => $course->id, 'title' => "Lesson {$i}", 'slug' => "lesson-{$i}", 'sort_order' => $i,
                    'duration_minutes' => 10, 'is_preview' => $i <= $previews, 'body' => "<p>Notes {$i}</p>",
                    'video_provider' => 'youtube', 'video_url' => 'https://www.youtube.com/watch?v=SECRETvid0'.$i,
                ]);
            }
        });
    }
}

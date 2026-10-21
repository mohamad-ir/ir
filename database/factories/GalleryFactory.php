<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GalleryFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(3), '.');

        return ['title' => $title, 'slug' => Str::slug($title), 'summary' => fake()->sentence(10), 'layout' => 'justified', 'status' => 'published', 'published_at' => now()->subDay()];
    }

    public function withImages(int $count = 3): static
    {
        return $this->afterCreating(function (Gallery $gallery) use ($count) {
            foreach (range(1, $count) as $i) {
                $media = Media::create(['disk' => 'public', 'path' => "uploads/test/photo-{$gallery->id}-{$i}.jpg", 'filename' => "photo-{$i}.jpg", 'original_name' => "photo-{$i}.jpg",
                    'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 1000, 'width' => 1200, 'height' => 800, 'alt' => "Scan number {$i}", 'hash' => md5($gallery->id.$i)]);
                $gallery->items()->create(['media_id' => $media->id, 'caption' => "Caption {$i}", 'sort_order' => $i]);
            }
        });
    }
}

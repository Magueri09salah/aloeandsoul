<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(14),
            'body' => implode("\n\n", fake()->paragraphs(4)),
            'status' => PostStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(?\DateTimeInterface $at = null): static
    {
        return $this->state(['status' => PostStatus::Published, 'published_at' => $at ?? now()->subHour()]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => PostStatus::Published, 'published_at' => now()->addWeek()]);
    }
}

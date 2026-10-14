<?php

namespace App\Http\Presenters;

use App\Models\BlogPost;
use App\Models\Collaborator;
use Illuminate\Support\Str;

final class PostPresenter
{
    /** @return array<string, mixed> */
    public static function card(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->translated('title'),
            'slug' => $post->slug,
            'excerpt' => $post->translated('excerpt') ?: Str::limit(self::plainText((string) $post->translated('body')), 180),
            'cover' => ImagePresenter::fromArray($post->cover, (string) ($post->translated('cover_alt') ?: $post->translated('title'))),
            'published_at' => $post->published_at?->toIso8601String(),
            'reading_minutes' => $post->readingMinutes(),
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(BlogPost $post): array
    {
        return [
            ...self::card($post),
            'body_html' => ProductPresenter::markdown((string) $post->translated('body')),
            'updated_at' => $post->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function collaborator(Collaborator $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'description' => $c->translated('description'),
            'url' => $c->url,
            'image' => ImagePresenter::fromArray($c->image, $c->name),
            'image_style' => $c->image_style,
        ];
    }

    public static function plainText(string $markdown): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags(ProductPresenter::markdown($markdown))));
    }
}

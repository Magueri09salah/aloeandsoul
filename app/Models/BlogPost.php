<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\BlogPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A blog post. Visible to the public only when published and its
 * publication date has passed (a future date schedules it).
 *
 * @property PostStatus $status
 * @property array{path: string, widths: list<int>, width: int, height: int}|null $cover
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'cover_alt', 'status', 'published_at',
        'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'cover' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Published and due: what visitors may see.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isLive(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function statusLabel(): string
    {
        return $this->isScheduled() ? 'Scheduled' : $this->status->label();
    }

    /** Estimated reading time in minutes (200 words a minute, at least 1). */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 200));
    }
}

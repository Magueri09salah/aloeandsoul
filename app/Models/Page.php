<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $updated_at
 */
class Page extends Model
{
    /** Slugs the storefront links to; these pages cannot be deleted. */
    public const SYSTEM_SLUGS = ['shipping-returns', 'privacy-policy', 'terms', 'legal-notice'];

    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'is_published', 'needs_review'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'needs_review' => 'boolean',
        ];
    }
}

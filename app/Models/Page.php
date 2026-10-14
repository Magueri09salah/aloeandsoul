<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $updated_at
 */
class Page extends Model
{
    use HasTranslations;

    /** Slugs the storefront links to; these pages cannot be deleted. */
    public const SYSTEM_SLUGS = ['shipping-returns', 'privacy-policy', 'terms', 'legal-notice'];

    /** Columns with a French version ("name" → "name_fr"). */
    public const TRANSLATABLE = ['title', 'body', 'seo_title', 'seo_description'];

    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'is_published', 'needs_review', 'title_fr', 'body_fr', 'seo_title_fr', 'seo_description_fr'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'needs_review' => 'boolean',
        ];
    }
}

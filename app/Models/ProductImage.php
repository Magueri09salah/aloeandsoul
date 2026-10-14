<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property list<int> $widths
 */
class ProductImage extends Model
{
    use HasTranslations;

    /** Columns with a French version ("name" → "name_fr"). */
    public const TRANSLATABLE = ['alt'];

    protected $fillable = ['product_id', 'path', 'widths', 'width', 'height', 'alt', 'sort_order', 'alt_fr'];

    protected function casts(): array
    {
        return [
            'widths' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function urlFor(int $width): string
    {
        return Storage::disk('public')->url("{$this->path}-{$width}.webp");
    }

    /** @return list<string> files on disk for every rendition */
    public function files(): array
    {
        return array_map(fn (int $w) => "{$this->path}-{$w}.webp", $this->widths);
    }
}

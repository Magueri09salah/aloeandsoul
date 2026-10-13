<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property list<int> $widths
 */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'widths', 'width', 'height', 'alt', 'sort_order'];

    protected function casts(): array
    {
        return [
            'widths' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
        ];
    }

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

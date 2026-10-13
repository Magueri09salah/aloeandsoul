<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $updated_at
 * @property int|null $product_count
 * @property int|null $products_count
 * @property int|null $published_products_count
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const MOTIFS = ['horizon', 'circle', 'wave', 'leaf'];

    protected $fillable = [
        'name', 'slug', 'tagline', 'description', 'tint', 'motif',
        'sort_order', 'is_active', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<Product, $this> */
    public function publishedProducts(): HasMany
    {
        return $this->products()->where('status', ProductStatus::Published);
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}

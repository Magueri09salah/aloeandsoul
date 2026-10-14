<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasTranslations;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property ProductStatus $status
 * @property list<array{label: string, value: string}>|null $specifications
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property int|string|null $total_stock
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasTranslations;

    /** Columns with a French version ("name" → "name_fr"). */
    public const TRANSLATABLE = ['name', 'summary', 'description', 'benefits', 'usage', 'ingredients', 'nutrition', 'option_label', 'seo_title', 'seo_description'];

    protected $fillable = [
        'category_id', 'name', 'slug', 'summary', 'description',
        'benefits', 'usage', 'ingredients', 'nutrition', 'specifications',
        'option_label', 'status', 'is_featured', 'is_bestseller', 'sort_order',
        'seo_title', 'seo_description', 'published_at',
        'name_fr', 'summary_fr', 'description_fr', 'benefits_fr', 'usage_fr', 'ingredients_fr', 'nutrition_fr', 'option_label_fr', 'seo_title_fr', 'seo_description_fr',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<ProductImage, $this> */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    /**
     * Reasons the product cannot be published yet. Empty when ready.
     *
     * @return list<string>
     */
    public function publishBlockers(): array
    {
        $this->loadMissing(['variants', 'images', 'category']);
        $blockers = [];

        $active = $this->variants->where('is_active', true);
        if ($active->isEmpty()) {
            $blockers[] = 'Add at least one active variant.';
        } elseif ($active->contains(fn (ProductVariant $v) => $v->price_minor === null || $v->price_minor === 0)) {
            $blockers[] = 'Set a price for every active variant.';
        }

        if ($this->images->isEmpty()) {
            $blockers[] = 'Upload at least one product image.';
        }

        if (blank($this->description)) {
            $blockers[] = 'Write a product description.';
        }

        if ($this->category && ! $this->category->is_active) {
            $blockers[] = 'The category is hidden. Show the category first.';
        }

        return $blockers;
    }

    public function hasOrders(): bool
    {
        return $this->orderItems()->exists();
    }
}

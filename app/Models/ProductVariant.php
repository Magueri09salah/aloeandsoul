<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id', 'name', 'sku', 'price_minor', 'sale_price_minor', 'stock',
        'track_stock', 'low_stock_threshold', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'sale_price_minor' => 'integer',
            'stock' => 'integer',
            'track_stock' => 'boolean',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('created_at')->latest('id');
    }

    public function isOnSale(): bool
    {
        return $this->sale_price_minor !== null
            && $this->price_minor !== null
            && $this->sale_price_minor < $this->price_minor;
    }

    /** Price actually charged, or null if not priced. */
    public function effectivePriceMinor(): ?int
    {
        return $this->isOnSale() ? $this->sale_price_minor : $this->price_minor;
    }

    /** Units that can be sold right now, capped by the per-line maximum. */
    public function purchasableQuantity(int $maxPerLine): int
    {
        return $this->track_stock ? min($this->stock, $maxPerLine) : $maxPerLine;
    }

    /** Why this variant cannot be bought, or null if it can. Requires `product` loaded. */
    public function unavailableReason(): ?string
    {
        $product = $this->product;

        if ($product === null || $product->status !== ProductStatus::Published) {
            return 'This product is no longer available.';
        }
        if (! $this->is_active || $this->price_minor === null || $this->price_minor === 0) {
            return 'This option is no longer available.';
        }
        if ($this->track_stock && $this->stock < 1) {
            return 'Out of stock.';
        }

        return null;
    }

    public function isPurchasable(): bool
    {
        return $this->unavailableReason() === null;
    }

    public function isLowStock(int $defaultThreshold): bool
    {
        return $this->track_stock && $this->stock <= ($this->low_stock_threshold ?? $defaultThreshold);
    }

    /** Requires `product` loaded. */
    public function displayName(): string
    {
        return $this->name ? "{$this->product->name} — {$this->name}" : $this->product->name;
    }
}

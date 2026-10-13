<?php

namespace App\Models;

use App\Enums\InventoryReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property InventoryReason $reason
 */
class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['product_variant_id', 'change', 'stock_after', 'reason', 'order_id', 'user_id', 'note'];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
            'stock_after' => 'integer',
            'reason' => InventoryReason::class,
        ];
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

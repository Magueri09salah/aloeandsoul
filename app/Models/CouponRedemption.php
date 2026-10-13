<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['coupon_id', 'order_id', 'customer_id', 'amount_minor', 'released_at'];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'released_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

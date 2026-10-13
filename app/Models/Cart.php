<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use MassPrunable;

    protected $fillable = ['token', 'coupon_code'];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }

    /** Abandoned carts are removed after the cookie lifetime. */
    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays((int) config('store.cart.lifetime_days')));
    }
}

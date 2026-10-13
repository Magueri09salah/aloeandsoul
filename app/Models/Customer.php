<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $first_order_at
 * @property Carbon|null $last_order_at
 * @property Carbon|null $anonymized_at
 * @property int|null $orders_count
 * @property int|string|null $delivered_value_minor
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = ['phone', 'name', 'email', 'city', 'first_order_at', 'last_order_at'];

    protected function casts(): array
    {
        return [
            'first_order_at' => 'datetime',
            'last_order_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }
}

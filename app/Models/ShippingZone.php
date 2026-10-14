<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    use HasTranslations;

    /** Columns with a French version ("name" → "name_fr"). */
    public const TRANSLATABLE = ['name', 'estimate'];

    protected $fillable = ['name', 'fee_minor', 'free_over_minor', 'estimate', 'is_active', 'sort_order', 'name_fr', 'estimate_fr'];

    protected function casts(): array
    {
        return [
            'fee_minor' => 'integer',
            'free_over_minor' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /** Delivery fee for a merchandise amount (after discount). */
    public function feeFor(int $merchandiseMinor): int
    {
        if ($this->free_over_minor !== null && $merchandiseMinor >= $this->free_over_minor) {
            return 0;
        }

        return $this->fee_minor;
    }
}

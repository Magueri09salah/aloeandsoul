<?php

namespace App\Services\Checkout;

use App\Models\Order;

/**
 * Human-friendly, non-sequential order references: AS-2610-K7Q3X.
 * Five characters from a 31-symbol alphabet give ~28.6 million references
 * per month; collisions are re-rolled and the unique index is the final guard.
 */
class ReferenceGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        $prefix = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) settings()->get('store.order_prefix', 'AS'))) ?: 'AS';
        $period = now()->format('ym');

        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $reference = substr($prefix, 0, 6)."-{$period}-{$suffix}";
        } while (Order::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

<?php

namespace App\Services\Pricing;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * Decides whether a coupon applies and how much it takes off.
 */
class CouponEvaluator
{
    /**
     * Reason the coupon cannot be used, or null when it can.
     * Pass $customerId to enforce once-per-customer (known only at checkout).
     */
    public function rejectionReason(Coupon $coupon, int $subtotalMinor, string $currency, ?int $customerId = null, ?CarbonInterface $now = null): ?string
    {
        $now ??= now();

        if (! $coupon->is_active) {
            return 'This code is not active.';
        }
        if ($coupon->starts_at !== null && $now->lt($coupon->starts_at)) {
            return 'This code is not valid yet.';
        }
        if ($coupon->ends_at !== null && $now->gt($coupon->ends_at)) {
            return 'This code has expired.';
        }
        if ($coupon->max_uses !== null && $coupon->uses_count >= $coupon->max_uses) {
            return 'This code has reached its usage limit.';
        }
        if ($coupon->min_subtotal_minor !== null && $subtotalMinor < $coupon->min_subtotal_minor) {
            return 'This code needs a minimum order of '.Money::format($coupon->min_subtotal_minor, $currency).'.';
        }
        if ($coupon->once_per_customer && $customerId !== null) {
            $used = CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where('customer_id', $customerId)
                ->whereNull('released_at')
                ->exists();
            if ($used) {
                return 'This code has already been used with this phone number.';
            }
        }

        return null;
    }

    public function discountFor(Coupon $coupon, int $subtotalMinor): int
    {
        $discount = match ($coupon->type) {
            CouponType::Percent => Money::percentOf($subtotalMinor, min(100, $coupon->value)),
            CouponType::Fixed => $coupon->value,
        };

        return min($discount, $subtotalMinor);
    }
}

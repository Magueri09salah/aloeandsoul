<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\ShippingZone;
use App\Services\Pricing\CouponEvaluator;
use App\Services\Pricing\PricingService;
use App\Services\Pricing\QuoteLine;

function pricing(): PricingService
{
    return new PricingService(new CouponEvaluator);
}

function unsavedZone(int $fee, ?int $freeOver = null): ShippingZone
{
    return new ShippingZone(['name' => 'Test', 'fee_minor' => $fee, 'free_over_minor' => $freeOver, 'is_active' => true]);
}

function unsavedCoupon(array $attributes): Coupon
{
    return (new Coupon)->forceFill(['code' => 'TEST', 'is_active' => true, 'uses_count' => 0, ...$attributes]);
}

it('computes subtotal, delivery and total', function () {
    $quote = pricing()->quote([
        new QuoteLine(1, 25000, 25000, 2),
        new QuoteLine(2, 9900, 12000, 1),
    ], 'MAD', unsavedZone(3000));

    expect($quote->subtotalMinor)->toBe(59900)
        ->and($quote->discountMinor)->toBe(0)
        ->and($quote->shippingMinor)->toBe(3000)
        ->and($quote->totalMinor())->toBe(62900);
});

it('has no total until a delivery area is chosen', function () {
    $quote = pricing()->quote([new QuoteLine(1, 10000, 10000, 1)], 'MAD');

    expect($quote->shippingMinor)->toBeNull()->and($quote->totalMinor())->toBeNull();
});

it('applies free delivery from the threshold, after discount', function () {
    $zone = unsavedZone(3000, 50000);

    expect(pricing()->quote([new QuoteLine(1, 50000, 50000, 1)], 'MAD', $zone)->shippingMinor)->toBe(0)
        ->and(pricing()->quote([new QuoteLine(1, 49999, 49999, 1)], 'MAD', $zone)->shippingMinor)->toBe(3000);

    $coupon = unsavedCoupon(['type' => CouponType::Percent, 'value' => 10]);
    $quote = pricing()->quote([new QuoteLine(1, 50000, 50000, 1)], 'MAD', $zone, $coupon);
    expect($quote->discountMinor)->toBe(5000)->and($quote->shippingMinor)->toBe(3000);
});

it('applies percentage and fixed discounts', function () {
    $lines = [new QuoteLine(1, 20000, 20000, 1)];

    $pct = pricing()->quote($lines, 'MAD', unsavedZone(0), unsavedCoupon(['type' => CouponType::Percent, 'value' => 15]));
    $fixed = pricing()->quote($lines, 'MAD', unsavedZone(0), unsavedCoupon(['type' => CouponType::Fixed, 'value' => 5000]));

    expect($pct->discountMinor)->toBe(3000)->and($pct->totalMinor())->toBe(17000)
        ->and($fixed->discountMinor)->toBe(5000)->and($fixed->totalMinor())->toBe(15000);
});

it('never discounts more than the subtotal', function () {
    $quote = pricing()->quote([new QuoteLine(1, 3000, 3000, 1)], 'MAD', unsavedZone(2000), unsavedCoupon(['type' => CouponType::Fixed, 'value' => 10000]));

    expect($quote->discountMinor)->toBe(3000)->and($quote->totalMinor())->toBe(2000);
});

it('rejects coupons that are inactive, expired, used up or below the minimum', function (array $attrs, string $message) {
    $quote = pricing()->quote([new QuoteLine(1, 10000, 10000, 1)], 'MAD', unsavedZone(0), unsavedCoupon(['type' => CouponType::Percent, 'value' => 10, ...$attrs]));

    expect($quote->discountMinor)->toBe(0)
        ->and($quote->coupon)->toBeNull()
        ->and($quote->couponError)->toContain($message);
})->with([
    'inactive' => [['is_active' => false], 'not active'],
    'expired' => [['ends_at' => now()->subDay()], 'expired'],
    'not started' => [['starts_at' => now()->addDay()], 'not valid yet'],
    'used up' => [['max_uses' => 5, 'uses_count' => 5], 'usage limit'],
    'minimum' => [['min_subtotal_minor' => 20000], 'minimum order'],
]);

<?php

namespace App\Services\Pricing;

use App\Models\Coupon;
use App\Models\ShippingZone;

/**
 * The only place order totals are calculated:
 *   total = subtotal − discount + delivery fee
 * Free delivery thresholds apply to the merchandise amount after discount.
 */
class PricingService
{
    public function __construct(private readonly CouponEvaluator $coupons) {}

    /**
     * @param  list<QuoteLine>  $lines
     */
    public function quote(array $lines, string $currency, ?ShippingZone $zone = null, ?Coupon $coupon = null, ?int $customerId = null): Quote
    {
        $subtotal = array_sum(array_map(fn (QuoteLine $l) => $l->totalMinor(), $lines));

        $discount = 0;
        $couponError = null;
        $appliedCoupon = null;

        if ($coupon !== null) {
            $couponError = $this->coupons->rejectionReason($coupon, $subtotal, $currency, $customerId);
            if ($couponError === null) {
                $discount = $this->coupons->discountFor($coupon, $subtotal);
                $appliedCoupon = $coupon;
            }
        }

        $shipping = $zone?->feeFor($subtotal - $discount);

        return new Quote(
            currency: $currency,
            subtotalMinor: $subtotal,
            discountMinor: $discount,
            shippingMinor: $shipping,
            coupon: $appliedCoupon,
            couponError: $couponError,
            zone: $zone,
        );
    }
}

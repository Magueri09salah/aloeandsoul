<?php

namespace App\Services\Pricing;

use App\Models\Coupon;
use App\Models\ShippingZone;

/**
 * Server-computed totals for a set of lines.
 */
final readonly class Quote
{
    public function __construct(
        public string $currency,
        public int $subtotalMinor,
        public int $discountMinor,
        public ?int $shippingMinor,
        public ?Coupon $coupon,
        public ?string $couponError,
        public ?ShippingZone $zone,
    ) {}

    /** Total payable on delivery; null until a delivery zone is chosen. */
    public function totalMinor(): ?int
    {
        return $this->shippingMinor === null
            ? null
            : $this->subtotalMinor - $this->discountMinor + $this->shippingMinor;
    }

    /** Merchandise total after discount, before delivery. */
    public function merchandiseMinor(): int
    {
        return $this->subtotalMinor - $this->discountMinor;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'subtotal_minor' => $this->subtotalMinor,
            'discount_minor' => $this->discountMinor,
            'shipping_minor' => $this->shippingMinor,
            'total_minor' => $this->totalMinor(),
            'coupon_code' => $this->coupon?->code,
            'coupon_error' => $this->couponError,
            'zone_id' => $this->zone?->id,
            'free_shipping_remaining_minor' => $this->zone?->free_over_minor !== null && $this->shippingMinor > 0
                ? max(0, $this->zone->free_over_minor - $this->merchandiseMinor())
                : null,
        ];
    }
}

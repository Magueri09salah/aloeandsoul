<?php

namespace App\Services\Pricing;

/**
 * One priced line as read from the database (never from the browser).
 */
final readonly class QuoteLine
{
    public function __construct(
        public int $variantId,
        public int $unitPriceMinor,
        public int $regularUnitPriceMinor,
        public int $quantity,
    ) {}

    public function totalMinor(): int
    {
        return $this->unitPriceMinor * $this->quantity;
    }
}

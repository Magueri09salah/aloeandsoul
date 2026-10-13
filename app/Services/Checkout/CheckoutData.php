<?php

namespace App\Services\Checkout;

/**
 * Validated, normalised checkout input. Contains no prices: those are always
 * read from the database. $expectedTotalMinor is only compared, never used.
 */
final readonly class CheckoutData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public int $shippingZoneId,
        public string $city,
        public string $address,
        public ?string $notes,
        public string $idempotencyKey,
        public int $expectedTotalMinor,
        public ?string $ipHash,
    ) {}
}

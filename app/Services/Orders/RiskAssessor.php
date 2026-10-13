<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * Flags orders an admin may want to double-check before dispatch.
 * Flags are advisory only; no order is ever rejected because of them.
 */
class RiskAssessor
{
    public const LABELS = [
        'repeat_phone' => 'Several orders from this phone number in the last 24 hours',
        'repeat_ip' => 'Several orders from the same connection in the last 24 hours',
        'previous_failed_delivery' => 'This phone number has a previous failed delivery or return',
        'large_quantity' => 'Unusually large number of items',
    ];

    /** @return list<string> */
    public function assess(string $phone, ?string $ipHash, int $totalUnits): array
    {
        $flags = [];
        $since = now()->subDay();

        if (Order::query()->where('customer_phone', $phone)->where('created_at', '>=', $since)->count() >= 2) {
            $flags[] = 'repeat_phone';
        }

        if ($ipHash !== null && Order::query()->where('ip_hash', $ipHash)->where('created_at', '>=', $since)->count() >= 3) {
            $flags[] = 'repeat_ip';
        }

        $failed = Order::query()
            ->where('customer_phone', $phone)
            ->whereIn('status', [OrderStatus::FailedDelivery, OrderStatus::Returned])
            ->exists();
        if ($failed) {
            $flags[] = 'previous_failed_delivery';
        }

        if ($totalUnits > 15) {
            $flags[] = 'large_quantity';
        }

        return $flags;
    }
}

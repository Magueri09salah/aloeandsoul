<?php

namespace App\Enums;

/**
 * Cash collection status, deliberately independent of fulfilment status.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Collected = 'collected';
    case NotCollected = 'not_collected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Not yet collected',
            self::Collected => 'Cash collected',
            self::NotCollected => 'Will not be collected',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Whether an admin may record this payment status for an order that is
     * currently in $order status, coming from $this payment status.
     */
    public function canChangeTo(self $to, OrderStatus $order): bool
    {
        if ($to === $this) {
            return false;
        }

        return match ($to) {
            self::Collected => $order === OrderStatus::Delivered,
            self::NotCollected => in_array($order, [OrderStatus::Cancelled, OrderStatus::FailedDelivery, OrderStatus::Returned], true)
                && $this !== self::Refunded,
            self::Refunded => $this === self::Collected && $order === OrderStatus::Returned,
            // Correction of a mistaken record, never for a cancelled order.
            self::Pending => $order !== OrderStatus::Cancelled && $this !== self::Refunded,
        };
    }

    /** @return list<self> */
    public function allowedChanges(OrderStatus $order): array
    {
        return array_values(array_filter(self::cases(), fn (self $to) => $this->canChangeTo($to, $order)));
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}

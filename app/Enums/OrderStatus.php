<?php

namespace App\Enums;

/**
 * Fulfilment status of a cash-on-delivery order.
 *
 * The transition map is the single source of truth for which changes are
 * allowed; the admin UI only offers what canTransitionTo() permits and the
 * server re-checks it inside a row lock.
 */
enum OrderStatus: string
{
    case PendingConfirmation = 'PENDING_CONFIRMATION';
    case Confirmed = 'CONFIRMED';
    case Processing = 'PROCESSING';
    case ReadyToShip = 'READY_TO_SHIP';
    case Shipped = 'SHIPPED';
    case Delivered = 'DELIVERED';
    case Cancelled = 'CANCELLED';
    case FailedDelivery = 'FAILED_DELIVERY';
    case Returned = 'RETURNED';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingConfirmation => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::ReadyToShip, self::Cancelled],
            self::ReadyToShip => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::FailedDelivery],
            self::FailedDelivery => [self::Shipped, self::Returned],
            self::Delivered => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'Awaiting confirmation',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Preparing',
            self::ReadyToShip => 'Ready to ship',
            self::Shipped => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::FailedDelivery => 'Delivery failed',
            self::Returned => 'Returned',
        };
    }

    /** Short explanation shown to the customer on the tracking page. */
    public function customerMessage(): string
    {
        return match ($this) {
            self::PendingConfirmation => 'We have received your order and will contact you to confirm it.',
            self::Confirmed => 'Your order is confirmed and will be prepared shortly.',
            self::Processing => 'Your order is being prepared.',
            self::ReadyToShip => 'Your order is packed and waiting for the courier.',
            self::Shipped => 'Your order is on its way. Please have the amount due ready.',
            self::Delivered => 'Your order has been delivered. Thank you.',
            self::Cancelled => 'This order has been cancelled.',
            self::FailedDelivery => 'The courier could not deliver your order. We will contact you.',
            self::Returned => 'This order has been returned.',
        };
    }

    /** Statuses in which goods are still with us and the order can be cancelled. */
    public function isBeforeShipping(): bool
    {
        return in_array($this, [self::PendingConfirmation, self::Confirmed, self::Processing, self::ReadyToShip], true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Orders that count towards "order value" (everything not cancelled).
     *
     * @return list<self>
     */
    public static function countedInOrderValue(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s !== self::Cancelled));
    }

    /**
     * Orders that still need work before they leave.
     *
     * @return list<self>
     */
    public static function awaitingFulfilment(): array
    {
        return [self::Confirmed, self::Processing, self::ReadyToShip];
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}

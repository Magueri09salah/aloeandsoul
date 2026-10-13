<?php

namespace App\Enums;

enum InventoryReason: string
{
    case OrderPlaced = 'order_placed';
    case OrderCancelled = 'order_cancelled';
    case OrderReturned = 'order_returned';
    case Adjustment = 'adjustment';
    case Restock = 'restock';

    public function label(): string
    {
        return match ($this) {
            self::OrderPlaced => 'Order placed',
            self::OrderCancelled => 'Order cancelled',
            self::OrderReturned => 'Order returned',
            self::Adjustment => 'Manual adjustment',
            self::Restock => 'Restock',
        };
    }
}

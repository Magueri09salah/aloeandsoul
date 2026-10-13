<?php

namespace App\Http\Presenters;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Phone;

final class OrderPresenter
{
    /**
     * What a customer may see about their order. Delivery details are only
     * included on the confirmation page shown to the session that placed it.
     *
     * @return array<string, mixed>
     */
    public static function forCustomer(Order $order, bool $withDelivery = false): array
    {
        $data = [
            'reference' => $order->reference,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_message' => $order->status->customerMessage(),
            'placed_at' => $order->created_at?->toIso8601String(),
            'currency' => $order->currency,
            'items' => $order->items->map(fn (OrderItem $i) => [
                'name' => $i->product_name,
                'variant' => $i->variant_name,
                'quantity' => $i->quantity,
                'unit_price_minor' => $i->unit_price_minor,
                'line_total_minor' => $i->line_total_minor,
            ])->values()->all(),
            'subtotal_minor' => $order->subtotal_minor,
            'discount_minor' => $order->discount_minor,
            'shipping_minor' => $order->shipping_minor,
            'total_minor' => $order->total_minor,
            'coupon_code' => $order->coupon_code,
            'payment_method_label' => 'Cash on delivery',
        ];

        if ($withDelivery) {
            $data['delivery'] = [
                'name' => $order->customer_name,
                'phone' => Phone::display($order->customer_phone, settings()->country()),
                'email' => $order->customer_email,
                'zone' => $order->shipping_zone_name,
                'city' => $order->city,
                'address' => $order->address,
                'notes' => $order->delivery_notes,
            ];
        }

        return $data;
    }
}

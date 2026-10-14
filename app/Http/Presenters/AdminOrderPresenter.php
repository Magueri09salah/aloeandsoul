<?php

namespace App\Http\Presenters;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Services\Orders\RiskAssessor;
use App\Support\Phone;

final class AdminOrderPresenter
{
    /** @return array<string, mixed> */
    public static function row(Order $order): array
    {
        return [
            'reference' => $order->reference,
            'customer_name' => $order->customer_name,
            'customer_phone' => Phone::display($order->customer_phone, settings()->country()),
            'city' => $order->city,
            'total_minor' => $order->total_minor,
            'currency' => $order->currency,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'created_at' => $order->created_at?->toIso8601String(),
            'risk' => ! empty($order->risk_flags),
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(Order $order): array
    {
        $country = settings()->country();

        return [
            ...self::row($order),
            'customer' => [
                'id' => $order->customer_id,
                'name' => $order->customer_name,
                'phone' => Phone::display($order->customer_phone, $country),
                'phone_e164' => $order->customer_phone,
                'whatsapp' => Phone::whatsappDigits($order->customer_phone),
                'email' => $order->customer_email,
                // The site language the customer ordered in, so the team replies in it.
                'locale' => $order->locale,
                'orders_count' => $order->customer?->orders()->count(),
            ],
            'delivery' => [
                'zone' => $order->shipping_zone_name,
                'city' => $order->city,
                'address' => $order->address,
                'notes' => $order->delivery_notes,
                'courier_name' => $order->courier_name,
                'tracking_number' => $order->tracking_number,
            ],
            'items' => $order->items->map(fn (OrderItem $i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product_slug' => $i->product?->slug,
                'name' => $i->product_name,
                'variant' => $i->variant_name,
                'sku' => $i->sku,
                'quantity' => $i->quantity,
                'unit_price_minor' => $i->unit_price_minor,
                'regular_unit_price_minor' => $i->regular_unit_price_minor,
                'line_total_minor' => $i->line_total_minor,
            ])->values()->all(),
            'subtotal_minor' => $order->subtotal_minor,
            'discount_minor' => $order->discount_minor,
            'shipping_minor' => $order->shipping_minor,
            'coupon_code' => $order->coupon_code,
            'payment' => [
                'method' => 'Cash on delivery',
                'status' => $order->payment_status->value,
                'status_label' => $order->payment_status->label(),
                'collected_minor' => $order->payment_collected_minor,
                'collected_at' => $order->payment_collected_at?->toIso8601String(),
                'recorded_by' => $order->paymentRecordedBy?->name,
                'allowed' => array_map(
                    fn (PaymentStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                    $order->payment_status->allowedChanges($order->status),
                ),
            ],
            'transitions' => array_map(
                fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                $order->status->allowedTransitions(),
            ),
            'risk_flags' => array_map(fn (string $f) => RiskAssessor::LABELS[$f] ?? $f, $order->risk_flags ?? []),
            'events' => $order->events->map(fn (OrderEvent $e) => [
                'id' => $e->id,
                'type' => $e->type->value,
                'from' => $e->from_status,
                'to' => $e->to_status,
                'to_label' => $e->type->value === 'status' && $e->to_status ? OrderStatus::from($e->to_status)->label()
                    : ($e->type->value === 'payment' && $e->to_status ? PaymentStatus::from($e->to_status)->label() : null),
                'note' => $e->note,
                'user' => $e->user?->name,
                'created_at' => $e->created_at?->toIso8601String(),
            ])->values()->all(),
            'timestamps' => [
                'confirmed_at' => $order->confirmed_at?->toIso8601String(),
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                'privacy_consent_at' => $order->privacy_consent_at?->toIso8601String(),
            ],
        ];
    }
}

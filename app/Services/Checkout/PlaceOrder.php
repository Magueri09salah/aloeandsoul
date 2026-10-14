<?php

namespace App\Services\Checkout;

use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Services\Cart\CartService;
use App\Services\Inventory\InventoryService;
use App\Services\Orders\RiskAssessor;
use App\Services\Pricing\PricingService;
use App\Services\Pricing\QuoteLine;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Creates a cash-on-delivery order from the current cart.
 *
 * Everything happens in one transaction: if any step fails, no order, item,
 * stock change or coupon use is left behind.
 */
class PlaceOrder
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PricingService $pricing,
        private readonly InventoryService $inventory,
        private readonly RiskAssessor $risk,
        private readonly ReferenceGenerator $references,
    ) {}

    public function handle(CheckoutData $data): Order
    {
        // A repeated submission (double click, refresh, network retry) returns
        // the order that was already created for this checkout.
        if ($existing = $this->findByKey($data->idempotencyKey)) {
            return $existing;
        }

        if (! settings()->get('store.ordering_enabled')) {
            throw new DomainException((string) settings()->localizedGet('store.closed_message'));
        }

        $cart = $this->cart->current();
        if ($cart === null || $cart->items()->doesntExist()) {
            throw new DomainException(__('Your cart is empty.'), 'cart');
        }

        try {
            $order = DB::transaction(fn () => $this->create($cart->id, $data), attempts: 3);
        } catch (UniqueConstraintViolationException $e) {
            // Two identical submissions raced; the other one won.
            if ($existing = $this->findByKey($data->idempotencyKey)) {
                return $existing;
            }
            throw $e;
        }

        Log::info('order.placed', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'total_minor' => $order->total_minor,
            'items' => $order->items()->count(),
            'risk_flags' => $order->risk_flags,
        ]);

        return $order;
    }

    private function create(int $cartId, CheckoutData $data): Order
    {
        $currency = settings()->currency();
        $maxPerLine = $this->cart->maxPerLine();

        // Locking the cart serialises submissions from the same cart (e.g. two
        // tabs with different idempotency keys); the loser finds it gone.
        $cart = Cart::query()->lockForUpdate()->find($cartId);
        $items = $cart?->items()->get();
        if ($cart === null || $items->isEmpty()) {
            // An identical submission that held the lock may have just won.
            return $this->findByKey($data->idempotencyKey)
                ?? throw new DomainException(__('Your cart is empty.'), 'cart');
        }

        // Lock variant rows in a stable order so concurrent checkouts queue
        // up instead of deadlocking, then re-check everything under the lock.
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $items->pluck('product_variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lines = [];
        $totalUnits = 0;
        foreach ($items as $item) {
            /** @var ProductVariant|null $variant */
            $variant = $variants->get($item->product_variant_id);
            if ($variant === null) {
                throw new DomainException(__('An item in your cart is no longer available.'), 'cart');
            }
            if (($reason = $variant->unavailableReason()) !== null) {
                throw new DomainException("{$variant->displayName()}: {$reason}", 'cart');
            }
            $available = $variant->purchasableQuantity($maxPerLine);
            if ($item->quantity > $available) {
                throw new DomainException(__('Only :count of :item available. Please update your cart.', ['count' => $available, 'item' => $variant->displayName()]), 'cart');
            }

            $lines[] = new QuoteLine($variant->id, (int) $variant->effectivePriceMinor(), (int) $variant->price_minor, $item->quantity);
            $totalUnits += $item->quantity;
        }

        $zone = ShippingZone::query()->where('is_active', true)->find($data->shippingZoneId);
        if ($zone === null) {
            throw new DomainException(__('Choose a delivery area.'), 'shipping_zone_id');
        }

        $customer = Customer::query()->where('phone', $data->phone)->lockForUpdate()->first();
        $coupon = $cart->coupon_code
            ? Coupon::query()->where('code', $cart->coupon_code)->lockForUpdate()->first()
            : null;

        $quote = $this->pricing->quote($lines, $currency, $zone, $coupon, $customer?->id);

        if ($cart->coupon_code !== null && $quote->coupon === null) {
            throw new DomainException(($quote->couponError ?? __('This code is not valid.')).' '.__('Remove the code to continue.'), 'coupon');
        }

        if ($quote->totalMinor() !== $data->expectedTotalMinor) {
            throw new DomainException(__('Prices or delivery fees have changed since you opened this page. Please check the updated total and place your order again.'), 'total');
        }

        $now = now();
        $customer ??= new Customer(['phone' => $data->phone, 'first_order_at' => $now]);
        $customer->fill([
            'name' => $data->name,
            'email' => $data->email ?? $customer->email,
            'city' => $data->city,
            'last_order_at' => $now,
        ])->save();

        $order = new Order;
        $order->forceFill([
            'reference' => $this->references->generate(),
            'idempotency_key' => $data->idempotencyKey,
            'customer_id' => $customer->id,
            'customer_name' => $data->name,
            'customer_phone' => $data->phone,
            'customer_email' => $data->email,
            'city' => $data->city,
            'address' => $data->address,
            'delivery_notes' => $data->notes,
            'shipping_zone_id' => $zone->id,
            'shipping_zone_name' => $zone->name,
            'currency' => $currency,
            'locale' => app()->getLocale(),
            'subtotal_minor' => $quote->subtotalMinor,
            'discount_minor' => $quote->discountMinor,
            'shipping_minor' => (int) $quote->shippingMinor,
            'total_minor' => (int) $quote->totalMinor(),
            'coupon_id' => $quote->coupon?->id,
            'coupon_code' => $quote->coupon?->code,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::PendingConfirmation,
            'risk_flags' => $this->risk->assess($data->phone, $data->ipHash, $totalUnits) ?: null,
            'privacy_consent_at' => $now,
            'ip_hash' => $data->ipHash,
        ])->save();

        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            $unit = (int) $variant->effectivePriceMinor();

            OrderItem::query()->forceCreate([
                'order_id' => $order->id,
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'unit_price_minor' => $unit,
                'regular_unit_price_minor' => (int) $variant->price_minor,
                'quantity' => $item->quantity,
                'line_total_minor' => $unit * $item->quantity,
            ]);

            $this->inventory->reserve($variant, $item->quantity, $order);
        }

        if ($quote->coupon !== null) {
            CouponRedemption::query()->create([
                'coupon_id' => $quote->coupon->id,
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'amount_minor' => $quote->discountMinor,
            ]);
            Coupon::query()->whereKey($quote->coupon->id)->increment('uses_count');
        }

        OrderEvent::query()->create([
            'order_id' => $order->id,
            'type' => OrderEventType::Status,
            'from_status' => null,
            'to_status' => OrderStatus::PendingConfirmation->value,
            'note' => 'Order placed online. Payment: cash on delivery.',
        ]);

        $cart->delete();

        return $order;
    }

    private function findByKey(string $key): ?Order
    {
        return Order::query()->where('idempotency_key', $key)->first();
    }
}

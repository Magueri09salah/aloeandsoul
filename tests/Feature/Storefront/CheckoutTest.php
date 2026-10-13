<?php

use App\Enums\CouponType;
use App\Enums\InventoryReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;

it('places a cash-on-delivery order with server-computed totals', function () {
    $variant = sellableVariant(25000, stock: 10);
    $zone = zone(3000);
    addToCart($variant, 2);

    $response = $this->post('/checkout', checkoutPayload($zone));

    $order = Order::query()->with('items')->sole();
    $response->assertRedirect("/checkout/confirmation/{$order->reference}");

    expect($order->subtotal_minor)->toBe(50000)
        ->and($order->shipping_minor)->toBe(3000)
        ->and($order->total_minor)->toBe(53000)
        ->and($order->currency)->toBe('MAD')
        ->and($order->payment_method)->toBe('cod')
        ->and($order->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->status)->toBe(OrderStatus::PendingConfirmation)
        ->and($order->customer_phone)->toBe('+212612345678')
        ->and($order->reference)->toMatch('/^AS-\d{4}-[A-Z0-9]{5}$/')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items[0]->product_name)->toBe($variant->product->name)
        ->and($order->items[0]->unit_price_minor)->toBe(25000)
        ->and($order->privacy_consent_at)->not->toBeNull();

    // Stock reserved and recorded.
    expect($variant->fresh()->stock)->toBe(8)
        ->and((int) InventoryMovement::query()->where('reason', InventoryReason::OrderPlaced)->sum('change'))->toBe(-2);

    // Customer created, cart emptied.
    expect(Customer::query()->where('phone', '+212612345678')->exists())->toBeTrue();
    $this->get('/cart')->assertInertia(fn (Assert $page) => $page->where('cart.count', 0));
});

it('ignores prices and totals sent by the browser', function () {
    $variant = sellableVariant(25000);
    $zone = zone(3000);
    addToCart($variant, 1);

    $this->post('/checkout', checkoutPayload($zone, [
        'subtotal_minor' => 1,
        'total_minor' => 1,
        'unit_price_minor' => 1,
        'discount_minor' => 99999,
    ]))->assertRedirect();

    expect(Order::query()->sole()->total_minor)->toBe(28000);
});

it('refuses the order when the total changed after the page was opened', function () {
    $variant = sellableVariant(25000);
    $zone = zone(3000);
    addToCart($variant, 1);
    $payload = checkoutPayload($zone);

    $variant->update(['price_minor' => 30000]);

    $this->post('/checkout', $payload)->assertSessionHasErrors('total');
    expect(Order::query()->count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(10);
});

it('validates customer details', function (array $override, string $field) {
    addToCart(sellableVariant(), 1);

    $this->post('/checkout', checkoutPayload(zone(), $override))->assertSessionHasErrors($field);
    expect(Order::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid phone' => [['phone' => '12345'], 'phone'],
    'local number from another country' => [['phone' => '020 7946 0018'], 'phone'],
    'missing address' => [['address' => ''], 'address'],
    'short address' => [['address' => 'x'], 'address'],
    'missing city' => [['city' => ''], 'city'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
    'no consent' => [['privacy' => false], 'privacy'],
    'no zone' => [['shipping_zone_id' => ''], 'shipping_zone_id'],
    'unknown zone' => [['shipping_zone_id' => 999999], 'shipping_zone_id'],
    'long notes' => [['notes' => str_repeat('a', 501)], 'notes'],
]);

it('returns the same order when the form is submitted twice', function () {
    $variant = sellableVariant(stock: 10);
    $zone = zone();
    addToCart($variant, 1);
    $payload = checkoutPayload($zone);

    $first = $this->post('/checkout', $payload);
    $second = $this->post('/checkout', $payload);

    expect(Order::query()->count())->toBe(1)
        ->and($variant->fresh()->stock)->toBe(9);
    $first->assertRedirect($second->headers->get('Location'));
});

it('rejects an idempotency key that was not issued to this session', function () {
    addToCart(sellableVariant(), 1);
    $payload = checkoutPayload(zone(), ['idempotency_key' => '6f1c1f0e-8f0a-4c86-9a0f-6a2a4f6b7c3d']);

    $this->post('/checkout', $payload)->assertRedirect('/checkout');
    expect(Order::query()->count())->toBe(0);
});

it('does not sell more than the available stock', function () {
    $variant = sellableVariant(stock: 2);
    $zone = zone();
    addToCart($variant, 2);
    $payload = checkoutPayload($zone);

    $variant->update(['stock' => 1]); // someone else bought one meanwhile

    $this->post('/checkout', $payload)->assertSessionHasErrors('cart');
    expect(Order::query()->count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(1);
});

it('refuses products that were unpublished after being added', function () {
    $variant = sellableVariant();
    $zone = zone();
    addToCart($variant, 1);
    $payload = checkoutPayload($zone);

    $variant->product->update(['status' => 'draft']);

    $this->post('/checkout', $payload)->assertSessionHasErrors('cart');
    expect(Order::query()->count())->toBe(0);
});

it('applies a discount code and records its use', function () {
    $variant = sellableVariant(20000);
    $zone = zone(3000);
    $coupon = Coupon::factory()->create(['code' => 'WELCOME10', 'type' => CouponType::Percent, 'value' => 10]);
    addToCart($variant, 1);

    $this->post('/cart/coupon', ['code' => 'welcome10'])->assertSessionHasNoErrors();
    $this->post('/checkout', checkoutPayload($zone))->assertRedirect();

    $order = Order::query()->sole();
    expect($order->discount_minor)->toBe(2000)
        ->and($order->total_minor)->toBe(21000)
        ->and($order->coupon_code)->toBe('WELCOME10')
        ->and($coupon->fresh()->uses_count)->toBe(1);
});

it('enforces once-per-customer codes by phone number', function () {
    $zone = zone();
    Coupon::factory()->create(['code' => 'ONCE', 'once_per_customer' => true]);

    addToCart(sellableVariant(), 1);
    $this->post('/cart/coupon', ['code' => 'ONCE']);
    $this->post('/checkout', checkoutPayload($zone))->assertRedirect();

    addToCart(sellableVariant(), 1);
    $this->post('/cart/coupon', ['code' => 'ONCE']);
    $this->post('/checkout', checkoutPayload($zone, ['phone' => '+212612345678']))->assertSessionHasErrors('coupon');

    expect(Order::query()->count())->toBe(1);
});

it('rejects unknown discount codes', function () {
    addToCart(sellableVariant(), 1);

    $this->post('/cart/coupon', ['code' => 'NOPE'])->assertSessionHasErrors('coupon');
    $this->post('/cart/coupon', ['code' => '<script>'])->assertSessionHasErrors('code');
});

it('is closed when no delivery area exists or ordering is paused', function () {
    $variant = sellableVariant();
    addToCart($variant, 1);

    $this->get('/checkout')->assertInertia(fn (Assert $page) => $page->where('checkout.open', false));

    $zone = zone();
    settings()->update('store', ['ordering_enabled' => false]);
    $this->get('/checkout')->assertInertia(fn (Assert $page) => $page->where('checkout.open', false));

    settings()->update('store', ['ordering_enabled' => true]);
    $payload = checkoutPayload($zone);
    settings()->update('store', ['ordering_enabled' => false]);
    $this->post('/checkout', $payload)->assertSessionHasErrors('general');
    expect(Order::query()->count())->toBe(0);
});

it('applies free delivery above the area threshold', function () {
    addToCart(sellableVariant(30000), 2);
    $this->post('/checkout', checkoutPayload(zone(3000, 50000)))->assertRedirect();

    expect(Order::query()->sole()->shipping_minor)->toBe(0);
});

it('shows the confirmation only to the session that placed the order', function () {
    addToCart(sellableVariant(), 1);
    $this->post('/checkout', checkoutPayload(zone()));
    $order = Order::query()->sole();

    $this->get("/checkout/confirmation/{$order->reference}")
        ->assertOk()
        ->assertSee('noindex', false)
        ->assertInertia(fn (Assert $page) => $page->where('order.reference', $order->reference)->where('order.total_minor', $order->total_minor));

    $this->flushSession();
    $this->get("/checkout/confirmation/{$order->reference}")->assertNotFound();
});

it('lets customers track an order with reference and phone only', function () {
    addToCart(sellableVariant(), 1);
    $this->post('/checkout', checkoutPayload(zone()));
    $order = Order::query()->sole();
    $this->flushSession();

    $this->post('/track-order', ['reference' => $order->reference, 'phone' => '0699999999'])->assertSessionHasErrors('reference');

    $this->post('/track-order', ['reference' => strtolower($order->reference), 'phone' => '06 12 34 56 78'])->assertRedirect('/track-order');
    $this->get('/track-order')->assertInertia(fn (Assert $page) => $page
        ->where('order.reference', $order->reference)
        ->missing('order.delivery'));
});

it('rate-limits order placement per connection', function () {
    addToCart(sellableVariant(stock: 100), 1);
    $zone = zone();

    for ($i = 0; $i < 6; $i++) {
        $this->post('/checkout', ['privacy' => false]);
    }

    $this->post('/checkout', ['privacy' => false])->assertStatus(429);
});

it('flags repeat orders from the same phone for review without blocking them', function () {
    $zone = zone();
    foreach (range(1, 3) as $_) {
        addToCart(sellableVariant(), 1);
        $this->post('/checkout', checkoutPayload($zone))->assertRedirect();
    }

    expect(Order::query()->count())->toBe(3)
        ->and(Order::query()->latest('id')->first()->risk_flags)->toContain('repeat_phone');
});

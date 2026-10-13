<?php

use App\Enums\InventoryReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;

/** Place a real order through the storefront and return it with its variant. */
function placedOrder(int $quantity = 2, int $stock = 10): array
{
    $variant = sellableVariant(stock: $stock);
    addToCart($variant, $quantity);
    test()->post('/checkout', checkoutPayload(zone()))->assertRedirect();
    test()->flushSession();

    return [Order::query()->latest('id')->firstOrFail(), $variant];
}

function moveTo(Order $order, string $to, array $extra = [])
{
    return test()->post("/admin/orders/{$order->reference}/transitions", ['to' => $to, ...$extra]);
}

it('moves an order through the full cash-on-delivery lifecycle', function () {
    [$order] = placedOrder();
    $this->actingAs(staff());

    foreach (['CONFIRMED', 'PROCESSING', 'READY_TO_SHIP', 'SHIPPED'] as $status) {
        moveTo($order, $status)->assertSessionHasNoErrors();
    }
    moveTo($order, 'DELIVERED', ['collect_payment' => true])->assertSessionHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->payment_status)->toBe(PaymentStatus::Collected)
        ->and($order->payment_collected_minor)->toBe($order->total_minor)
        ->and($order->confirmed_at)->not->toBeNull()
        ->and($order->delivered_at)->not->toBeNull()
        ->and($order->events()->count())->toBe(7) // placed + 5 moves + payment
        ->and(AuditLog::query()->where('action', 'order.status_changed')->count())->toBe(5);
});

it('rejects impossible status changes', function () {
    [$order] = placedOrder();
    $this->actingAs(staff());

    moveTo($order, 'DELIVERED')->assertSessionHasErrors('status');
    moveTo($order, 'SHIPPED')->assertSessionHasErrors('status');
    moveTo($order, 'NOT_A_STATUS')->assertSessionHasErrors('to');

    expect($order->fresh()->status)->toBe(OrderStatus::PendingConfirmation);
});

it('returns reserved stock and releases the discount code when cancelled', function () {
    $variant = sellableVariant(stock: 10);
    $coupon = Coupon::factory()->create(['code' => 'TEN']);
    addToCart($variant, 3);
    $this->post('/cart/coupon', ['code' => 'TEN']);
    $this->post('/checkout', checkoutPayload(zone()));
    $order = Order::query()->sole();
    expect($variant->fresh()->stock)->toBe(7)->and($coupon->fresh()->uses_count)->toBe(1);

    $this->actingAs(staff());
    moveTo($order, 'CANCELLED', ['note' => 'Customer changed their mind'])->assertSessionHasNoErrors();

    expect($variant->fresh()->stock)->toBe(10)
        ->and($coupon->fresh()->uses_count)->toBe(0)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::NotCollected)
        ->and(InventoryMovement::query()->where('reason', InventoryReason::OrderCancelled)->value('change'))->toBe(3);
});

it('cannot cancel an order that is already with the courier', function () {
    [$order, $variant] = placedOrder();
    $this->actingAs(staff());
    foreach (['CONFIRMED', 'PROCESSING', 'READY_TO_SHIP', 'SHIPPED'] as $s) {
        moveTo($order, $s);
    }

    moveTo($order, 'CANCELLED')->assertSessionHasErrors('status');
    expect($variant->fresh()->stock)->toBe(8);
});

it('restocks returned goods only when asked', function (bool $restock, int $expectedStock) {
    [$order, $variant] = placedOrder(2, 10);
    $this->actingAs(staff());
    foreach (['CONFIRMED', 'PROCESSING', 'READY_TO_SHIP', 'SHIPPED', 'FAILED_DELIVERY'] as $s) {
        moveTo($order, $s);
    }

    moveTo($order, 'RETURNED', ['restock' => $restock])->assertSessionHasNoErrors();

    expect($variant->fresh()->stock)->toBe($expectedStock)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::NotCollected);
})->with([
    'resellable' => [true, 10],
    'damaged' => [false, 8],
]);

it('only records cash collected for delivered orders', function () {
    [$order] = placedOrder();
    $this->actingAs(staff());

    $this->post("/admin/orders/{$order->reference}/payment", ['payment_status' => 'collected'])->assertSessionHasErrors('payment_status');

    foreach (['CONFIRMED', 'PROCESSING', 'READY_TO_SHIP', 'SHIPPED', 'DELIVERED'] as $s) {
        moveTo($order, $s);
    }
    $this->post("/admin/orders/{$order->reference}/payment", ['payment_status' => 'collected', 'amount' => '100.50'])->assertSessionHasNoErrors();

    expect($order->fresh()->payment_collected_minor)->toBe(10050);
});

it('keeps order lines unchanged when the product is edited later', function () {
    [$order, $variant] = placedOrder();
    $original = $order->items()->first();

    $variant->update(['price_minor' => 99900]);
    $variant->product->update(['name' => 'Renamed product']);

    $line = $order->items()->first();
    expect($line->unit_price_minor)->toBe($original->unit_price_minor)
        ->and($line->product_name)->toBe($original->product_name)
        ->and($order->fresh()->total_minor)->toBe($order->total_minor);
});

it('adds internal notes and delivery details', function () {
    [$order] = placedOrder();
    $this->actingAs(staff());

    $this->post("/admin/orders/{$order->reference}/notes", ['note' => 'Called, confirmed for Tuesday'])->assertSessionHasNoErrors();
    $this->put("/admin/orders/{$order->reference}/delivery", ['courier_name' => 'Amana', 'tracking_number' => 'MA123'])->assertSessionHasNoErrors();

    expect($order->events()->where('type', 'note')->value('note'))->toBe('Called, confirmed for Tuesday')
        ->and($order->fresh()->tracking_number)->toBe('MA123');
});

it('prints a packing slip with the amount to collect', function () {
    [$order] = placedOrder();

    $this->actingAs(staff())->get("/admin/orders/{$order->reference}/packing-slip")
        ->assertOk()
        ->assertSee($order->reference)
        ->assertSee('Cash on delivery');
});

it('finds orders by reference, name or phone', function () {
    [$order] = placedOrder();
    $this->actingAs(staff());

    foreach ([$order->reference, 'Fatima', '0612345678', '345678'] as $term) {
        $this->get('/admin/orders?q='.urlencode($term))
            ->assertInertia(fn ($page) => $page->has('orders.data', 1));
    }
    $this->get('/admin/orders?q=Nobody')->assertInertia(fn ($page) => $page->has('orders.data', 0));
});

it('distinguishes order value from collected sales on the dashboard', function () {
    [$delivered] = placedOrder();
    [$pending] = placedOrder();
    [$cancelled] = placedOrder();
    $this->actingAs(owner());

    foreach (['CONFIRMED', 'PROCESSING', 'READY_TO_SHIP', 'SHIPPED'] as $s) {
        moveTo($delivered, $s);
    }
    moveTo($delivered, 'DELIVERED', ['collect_payment' => true]);
    moveTo($cancelled, 'CANCELLED');

    $this->get('/admin')->assertInertia(fn ($page) => $page
        ->where('metrics.collected_minor', $delivered->total_minor)
        ->where('metrics.order_value_minor', $delivered->total_minor + $pending->total_minor)
        ->where('metrics.cancelled', 1)
        ->where('metrics.awaiting_confirmation', 1));
});

it('lets the owner anonymise a customer while keeping order totals', function () {
    [$order] = placedOrder();
    $owner = owner();
    foreach (['CONFIRMED', 'PROCESSING', 'READY_TO_SHIP', 'SHIPPED', 'DELIVERED'] as $s) {
        $this->actingAs($owner);
        moveTo($order, $s);
    }

    $this->actingAs(manager())->post("/admin/customers/{$order->customer_id}/anonymize")->assertForbidden();
    $this->actingAs($owner)->post("/admin/customers/{$order->customer_id}/anonymize")->assertSessionHasNoErrors();

    $order->refresh();
    expect($order->customer_name)->toBe('Anonymised customer')
        ->and($order->customer_phone)->not->toContain('612345678')
        ->and($order->address)->toBe('Removed')
        ->and($order->total_minor)->toBeGreaterThan(0);
});

it('adjusts stock manually with a reason', function () {
    $variant = sellableVariant(stock: 5);

    $this->actingAs(manager())->put("/admin/inventory/{$variant->id}", ['stock' => 20, 'note' => 'Delivery received'])->assertSessionHasNoErrors();
    $this->actingAs(staff())->put("/admin/inventory/{$variant->id}", ['stock' => 0])->assertForbidden();
    $this->actingAs(manager())->put("/admin/inventory/{$variant->id}", ['stock' => -1])->assertSessionHasErrors('stock');

    expect(ProductVariant::query()->find($variant->id)->stock)->toBe(20)
        ->and(InventoryMovement::query()->where('note', 'Delivery received')->value('change'))->toBe(15);
});

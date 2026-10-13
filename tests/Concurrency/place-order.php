<?php

/**
 * Worker used by tests/Feature/ConcurrencyTest.php: boots the app in its own
 * PHP process and places an order for the given cart, so several processes
 * really compete for the same stock in MySQL at the same time.
 *
 * Usage: php place-order.php <cartToken> <idempotencyKey> <zoneId> <expectedTotal> <phone> <startAtMicrotime>
 */

use App\Exceptions\DomainException;
use App\Services\Checkout\CheckoutData;
use App\Services\Checkout\PlaceOrder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $token, $key, $zoneId, $expected, $phone, $startAt] = $argv;

$request = Request::create('/checkout', 'POST');
$request->cookies->set((string) config('store.cart.cookie'), $token);
$app->instance('request', $request);

// Start all workers at the same instant to maximise contention.
while (microtime(true) < (float) $startAt) {
    usleep(500);
}

try {
    $order = $app->make(PlaceOrder::class)->handle(new CheckoutData(
        name: 'Concurrent Buyer',
        phone: $phone,
        email: null,
        shippingZoneId: (int) $zoneId,
        city: 'Marrakech',
        address: '1 Test Street',
        notes: null,
        idempotencyKey: $key,
        expectedTotalMinor: (int) $expected,
        ipHash: null,
    ));
    echo 'OK '.$order->reference;
} catch (DomainException $e) {
    echo 'REFUSED '.$e->getMessage();
}

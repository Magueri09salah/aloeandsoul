<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/*
| Real concurrency: separate PHP processes, each with its own MySQL
| connection, try to buy the last units at the same moment. Data must be
| committed for other connections to see it, so this file truncates tables
| instead of wrapping each test in a transaction.
*/
uses(DatabaseTruncation::class);

beforeEach(function () {
    if (config('database.default') !== 'mysql') {
        $this->markTestSkipped('Row locking is only meaningful on MySQL/MariaDB.');
    }
});

/**
 * @return list<string> one output line per worker
 */
function race(ProductVariant $variant, int $buyers, int $quantityEach): array
{
    $zone = zone(0);
    $total = $variant->price_minor * $quantityEach;
    $startAt = microtime(true) + 3.0;
    $env = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => config('database.connections.mysql.database'),
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
    ];

    $pool = Process::pool(function ($pool) use ($variant, $buyers, $quantityEach, $zone, $total, $startAt, $env) {
        for ($i = 0; $i < $buyers; $i++) {
            $cart = Cart::query()->create(['token' => bin2hex(random_bytes(32))]);
            $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $quantityEach]);

            $pool->path(base_path())->env($env)->timeout(120)->command([
                PHP_BINARY,
                base_path('tests/Concurrency/place-order.php'),
                $cart->token,
                (string) Str::uuid(),
                (string) $zone->id,
                (string) $total,
                '+2126'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                (string) $startAt,
            ]);
        }
    })->start()->wait();

    return $pool->collect()->map(fn ($result) => trim($result->output()).trim($result->errorOutput()))->values()->all();
}

it('sells the last unit to exactly one of several simultaneous buyers', function () {
    $variant = sellableVariant(10000, stock: 1);

    $outcomes = race($variant, 5, 1);

    $won = array_filter($outcomes, fn ($o) => str_starts_with($o, 'OK'));
    $refused = array_filter($outcomes, fn ($o) => str_starts_with($o, 'REFUSED'));

    expect($won)->toHaveCount(1, implode("\n", $outcomes))
        ->and($refused)->toHaveCount(4)
        ->and($variant->fresh()->stock)->toBe(0)
        ->and(Order::query()->count())->toBe(1);
});

it('never takes stock below zero under contention', function () {
    $variant = sellableVariant(10000, stock: 5);

    $outcomes = race($variant, 6, 2);
    $won = count(array_filter($outcomes, fn ($o) => str_starts_with($o, 'OK')));

    expect($won)->toBe(2, implode("\n", $outcomes))
        ->and($variant->fresh()->stock)->toBe(1)
        ->and((int) Order::query()->withSum('items', 'quantity')->get()->sum('items_sum_quantity'))->toBe(4);
});

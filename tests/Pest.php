<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
| Feature tests run against the MySQL test database (see phpunit.xml) inside
| a transaction per test. The concurrency test commits real data instead.
*/
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
uses(TestCase::class)->in('Concurrency');

/** A published, sellable product with one variant. */
function sellableVariant(int $priceMinor = 25000, int $stock = 10, array $variant = [], array $product = []): ProductVariant
{
    $p = Product::factory()->published()->create([
        'category_id' => Category::factory()->create()->id,
        ...$product,
    ]);
    ProductImage::query()->create([
        'product_id' => $p->id, 'path' => 'products/test/img', 'widths' => [480], 'width' => 800, 'height' => 1000, 'alt' => $p->name,
    ]);

    return ProductVariant::factory()->create([
        'product_id' => $p->id,
        'price_minor' => $priceMinor,
        'stock' => $stock,
        ...$variant,
    ])->load('product');
}

function zone(int $feeMinor = 3000, ?int $freeOver = null): ShippingZone
{
    return ShippingZone::factory()->create(['fee_minor' => $feeMinor, 'free_over_minor' => $freeOver]);
}

/** Add to cart over HTTP and keep the cart cookie for the rest of the test. */
function addToCart(ProductVariant $variant, int $quantity = 1): TestResponse
{
    $response = test()->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => $quantity]);
    if ($token = Cart::query()->latest('id')->value('token')) {
        test()->withCookie(config('store.cart.cookie'), $token);
    }

    return $response;
}

/** Props of an Inertia response. */
function inertiaProps(TestResponse $response): array
{
    return $response->viewData('page')['props'];
}

/**
 * Open the checkout page (issues the idempotency key) and return the fields
 * needed to submit it, as a browser would.
 *
 * @return array<string, mixed>
 */
function checkoutPayload(ShippingZone $zone, array $overrides = []): array
{
    $props = inertiaProps(test()->get('/checkout?zone='.$zone->id)->assertOk());

    return [
        'name' => 'Fatima Zahra',
        'phone' => '06 12 34 56 78',
        'email' => '',
        'shipping_zone_id' => $zone->id,
        'city' => 'Marrakech',
        'address' => '12 Rue de la Kasbah, Medina',
        'notes' => '',
        'privacy' => true,
        'idempotency_key' => $props['idempotencyKey'],
        'expected_total' => $props['quote']['total_minor'],
        ...$overrides,
    ];
}

function owner(): User
{
    return User::factory()->owner()->create();
}

function manager(): User
{
    return User::factory()->manager()->create();
}

function staff(): User
{
    return User::factory()->staff()->create();
}

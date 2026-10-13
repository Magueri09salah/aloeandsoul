<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

it('adds a product to a new guest cart and sets an http-only cookie', function () {
    $variant = sellableVariant();

    $response = $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);

    $response->assertRedirect();
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === config('store.cart.cookie'));
    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and((int) CartItem::query()->sum('quantity'))->toBe(2);
});

it('shows current database prices in the cart, not what was added', function () {
    $variant = sellableVariant(10000);
    addToCart($variant, 2);
    $variant->update(['price_minor' => 15000]);

    $this->get('/cart')->assertInertia(fn (Assert $page) => $page
        ->where('cart.subtotal_minor', 30000)
        ->where('cart.lines.0.unit_price_minor', 15000));
});

it('clamps quantities to available stock and the per-line maximum', function () {
    $variant = sellableVariant(stock: 3);

    addToCart($variant, 2);
    addToCart($variant, 5);
    expect(CartItem::query()->value('quantity'))->toBe(3);

    addToCart($variant, 1)->assertSessionHasErrors('quantity');
    expect(CartItem::query()->value('quantity'))->toBe(3);
});

it('rejects out-of-stock, draft and unpriced products', function () {
    $out = sellableVariant(stock: 0);
    $draft = ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);
    $unpriced = sellableVariant(variant: ['price_minor' => null]);

    $this->post('/cart/items', ['variant_id' => $out->id, 'quantity' => 1])->assertSessionHasErrors('quantity');
    $this->post('/cart/items', ['variant_id' => $draft->id, 'quantity' => 1])->assertSessionHasErrors('quantity');
    $this->post('/cart/items', ['variant_id' => $unpriced->id, 'quantity' => 1])->assertSessionHasErrors('quantity');
    expect(CartItem::query()->count())->toBe(0);
});

it('validates quantity input', function () {
    $variant = sellableVariant();

    $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 0])->assertSessionHasErrors('quantity');
    $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 'lots'])->assertSessionHasErrors('quantity');
    $this->post('/cart/items', ['variant_id' => 'x', 'quantity' => 1])->assertSessionHasErrors('variant_id');
});

it('updates and removes lines', function () {
    $variant = sellableVariant();
    addToCart($variant, 1);
    $item = CartItem::query()->firstOrFail();

    $this->patch("/cart/items/{$item->id}", ['quantity' => 4])->assertRedirect();
    expect($item->fresh()->quantity)->toBe(4);

    $this->delete("/cart/items/{$item->id}")->assertRedirect();
    expect(CartItem::query()->count())->toBe(0);
});

it('never lets one visitor change another visitor’s cart', function () {
    $variant = sellableVariant();
    $otherCart = Cart::query()->create(['token' => str_repeat('a', 64)]);
    $foreign = $otherCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    addToCart($variant, 1);

    $this->patch("/cart/items/{$foreign->id}", ['quantity' => 9])->assertNotFound();
    $this->delete("/cart/items/{$foreign->id}")->assertNotFound();
    expect($foreign->fresh()->quantity)->toBe(1);
});

it('flags lines that became unavailable', function () {
    $variant = sellableVariant(stock: 5);
    addToCart($variant, 4);
    $variant->update(['stock' => 2]);

    $this->get('/cart')->assertInertia(fn (Assert $page) => $page
        ->where('cart.has_issues', true)
        ->where('cart.lines.0.issue', 'Only 2 available. Reduce the quantity to continue.'));
});

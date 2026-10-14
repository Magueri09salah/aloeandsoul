<?php

namespace App\Services\Cart;

use App\Exceptions\DomainException;
use App\Http\Presenters\ImagePresenter;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Services\Pricing\QuoteLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Server-side guest cart identified by a random token stored in an
 * encrypted, HTTP-only cookie. Prices and stock are always read fresh.
 */
class CartService
{
    private ?Cart $cart = null;

    private bool $resolved = false;

    public function __construct(private readonly Request $request) {}

    public function current(): ?Cart
    {
        if ($this->resolved) {
            return $this->cart;
        }
        $this->resolved = true;

        $token = $this->request->cookie((string) config('store.cart.cookie'));
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return $this->cart = Cart::query()->where('token', $token)->first();
    }

    public function currentOrCreate(): Cart
    {
        $cart = $this->current();
        if ($cart !== null) {
            return $cart;
        }

        $cart = Cart::query()->create(['token' => bin2hex(random_bytes(32))]);
        $this->queueCookie($cart->token);
        $this->cart = $cart;

        return $cart;
    }

    /**
     * Add units of a variant. Quantity is clamped to stock and the per-line
     * maximum. Returns the quantity now in the cart.
     */
    public function add(ProductVariant $variant, int $quantity): int
    {
        $variant->loadMissing('product');
        if (($reason = $variant->unavailableReason()) !== null) {
            throw new DomainException($reason, 'quantity');
        }

        $cart = $this->currentOrCreate();
        $max = $variant->purchasableQuantity($this->maxPerLine());

        /** @var CartItem|null $item */
        $item = $cart->items()->where('product_variant_id', $variant->id)->first();
        $existing = $item !== null ? $item->quantity : 0;

        if ($existing >= $max) {
            throw new DomainException($this->limitMessage($variant, $max), 'quantity');
        }

        $newQuantity = min($existing + max(1, $quantity), $max);

        if ($item) {
            $item->update(['quantity' => $newQuantity]);
        } else {
            $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $newQuantity]);
            $cart->touch();
        }

        return $newQuantity;
    }

    public function update(CartItem $item, int $quantity): void
    {
        $this->assertOwned($item);

        if ($quantity < 1) {
            $this->remove($item);

            return;
        }

        $variant = $item->variant()->with('product')->firstOrFail();
        $max = $variant->purchasableQuantity($this->maxPerLine());

        if ($quantity > $max && $quantity > $item->quantity) {
            throw new DomainException($this->limitMessage($variant, $max), 'quantity');
        }

        $item->update(['quantity' => min($quantity, max($max, 1))]);
    }

    public function remove(CartItem $item): void
    {
        $this->assertOwned($item);
        $item->delete();
    }

    public function applyCoupon(string $code): Coupon
    {
        $cart = $this->current();
        if ($cart === null || $cart->items()->doesntExist()) {
            throw new DomainException(__('Add something to your cart first.'), 'coupon');
        }

        $coupon = Coupon::query()->where('code', Coupon::normalizeCode($code))->first();
        if ($coupon === null) {
            throw new DomainException(__('This code is not valid.'), 'coupon');
        }

        $cart->update(['coupon_code' => $coupon->code]);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        $this->current()?->update(['coupon_code' => null]);
    }

    public function coupon(): ?Coupon
    {
        $code = $this->current()?->coupon_code;

        return $code ? Coupon::query()->where('code', $code)->first() : null;
    }

    /**
     * Cart lines with current prices, for display.
     *
     * @return array{
     *     lines: list<array<string, mixed>>,
     *     count: int,
     *     subtotal_minor: int,
     *     has_issues: bool,
     *     coupon_code: string|null
     * }
     */
    public function view(): array
    {
        $cart = $this->current();
        if ($cart === null) {
            return ['lines' => [], 'count' => 0, 'subtotal_minor' => 0, 'has_issues' => false, 'coupon_code' => null];
        }

        $cart->loadMissing(['items.variant.product.primaryImage', 'items.variant.product.category']);
        $maxPerLine = $this->maxPerLine();

        $lines = [];
        $subtotal = 0;
        $count = 0;
        $hasIssues = false;

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            $product = $variant->product;
            $issue = $variant->unavailableReason();
            $max = $issue === null ? $variant->purchasableQuantity($maxPerLine) : 0;

            if ($issue === null && $item->quantity > $max) {
                $issue = __('Only :count available. Reduce the quantity to continue.', ['count' => $max]);
            }

            $price = $variant->effectivePriceMinor();
            $lineTotal = $price !== null ? $price * $item->quantity : 0;

            if ($issue === null) {
                $subtotal += $lineTotal;
                $count += $item->quantity;
            } else {
                $hasIssues = true;
            }

            $lines[] = [
                'id' => $item->id,
                'variant_id' => $variant->id,
                'product_name' => $product->translated('name'),
                'product_slug' => $product->slug,
                'variant_name' => $variant->translated('name'),
                'option_label' => $product->translated('option_label'),
                'image' => $product->primaryImage ? ImagePresenter::present($product->primaryImage, (string) $product->translated('name')) : null,
                'tint' => $product->category->tint,
                'unit_price_minor' => $price,
                'regular_price_minor' => $variant->price_minor,
                'quantity' => $item->quantity,
                'max_quantity' => max($max, $item->quantity),
                'line_total_minor' => $lineTotal,
                'issue' => $issue,
            ];
        }

        return [
            'lines' => $lines,
            'count' => $count,
            'subtotal_minor' => $subtotal,
            'has_issues' => $hasIssues,
            'coupon_code' => $cart->coupon_code,
        ];
    }

    /**
     * Quote lines for currently purchasable cart items.
     *
     * @return list<QuoteLine>
     */
    public function quoteLines(): array
    {
        $cart = $this->current();
        if ($cart === null) {
            return [];
        }

        $cart->loadMissing('items.variant.product');

        $lines = [];
        foreach ($cart->items as $item) {
            $variant = $item->variant;
            if (! $variant->isPurchasable() || $item->quantity > $variant->purchasableQuantity($this->maxPerLine())) {
                continue;
            }
            $lines[] = new QuoteLine($variant->id, (int) $variant->effectivePriceMinor(), (int) $variant->price_minor, $item->quantity);
        }

        return $lines;
    }

    public function forget(): void
    {
        $this->current()?->delete();
        Cookie::queue(Cookie::forget((string) config('store.cart.cookie')));
        $this->cart = null;
    }

    public function maxPerLine(): int
    {
        return max(1, (int) settings()->get('store.max_quantity_per_line', 10));
    }

    private function limitMessage(ProductVariant $variant, int $max): string
    {
        return $variant->track_stock && $variant->stock <= $max
            ? __('Only :count available. You already have the maximum in your cart.', ['count' => $max])
            : __('You can order up to :count of this item at a time.', ['count' => $max]);
    }

    private function assertOwned(CartItem $item): void
    {
        if ($this->current()?->id !== $item->cart_id) {
            // Never reveal whether another cart's item exists.
            abort(404);
        }
    }

    private function queueCookie(string $token): void
    {
        Cookie::queue(Cookie::make(
            name: (string) config('store.cart.cookie'),
            value: $token,
            minutes: (int) config('store.cart.lifetime_days') * 24 * 60,
            path: '/',
            domain: config('session.domain'),
            secure: (bool) config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));
    }
}

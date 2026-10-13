<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CartController extends Controller
{
    public function show(): Response
    {
        return $this->render('cart', [], Seo::private('Your cart'));
    }

    public function store(CartItemRequest $request, CartService $cart): RedirectResponse
    {
        $variant = ProductVariant::query()->with('product')->find($request->integer('variant_id'));
        if ($variant === null) {
            return back()->withErrors(['quantity' => 'This product is no longer available.']);
        }

        $cart->add($variant, $request->integer('quantity'));

        // The cart drawer opening is the confirmation; no toast needed.
        return back();
    }

    public function update(CartItemRequest $request, CartService $cart, CartItem $item): RedirectResponse
    {
        $cart->update($item, $request->integer('quantity'));

        return back();
    }

    public function destroy(CartService $cart, CartItem $item): RedirectResponse
    {
        $cart->remove($item);

        return back();
    }

    public function applyCoupon(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/']], [
            'code.regex' => 'This code is not valid.',
            'code.required' => 'Enter a discount code.',
        ]);

        $coupon = $cart->applyCoupon($validated['code']);

        return back()->with('success', "Code {$coupon->code} added.");
    }

    public function removeCoupon(CartService $cart): RedirectResponse
    {
        $cart->removeCoupon();

        return back();
    }
}

<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\OrderPresenter;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\Cart\CartService;
use App\Services\Checkout\PlaceOrder;
use App\Services\Pricing\PricingService;
use App\Services\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;

class CheckoutController extends Controller
{
    private const KEYS = 'checkout.keys';

    private const PLACED = 'checkout.placed_orders';

    public function show(Request $request, CartService $cart, PricingService $pricing): Response|RedirectResponse
    {
        $view = $cart->view();
        if ($view['lines'] === []) {
            return redirect()->route('cart');
        }

        $zones = ShippingZone::query()->active()->get();
        $zoneId = filter_var($request->query('zone'), FILTER_VALIDATE_INT) ?: null;
        $zone = $zoneId ? $zones->firstWhere('id', $zoneId) : ($zones->count() === 1 ? $zones->first() : null);

        $quote = $pricing->quote($cart->quoteLines(), settings()->currency(), $zone, $cart->coupon());

        return $this->render('checkout/index', [
            // Lazy: partial reloads (changing delivery area) keep the page's key.
            'idempotencyKey' => fn () => $this->issueKey($request),
            'quote' => $quote->toArray(),
            'zones' => $zones->map(fn (ShippingZone $z) => [
                'id' => $z->id,
                'name' => $z->name,
                'fee_minor' => $z->fee_minor,
                'free_over_minor' => $z->free_over_minor,
                'estimate' => $z->estimate,
            ]),
            'checkout' => [
                'open' => (bool) settings()->get('store.ordering_enabled') && $zones->isNotEmpty(),
                'closed_message' => settings()->get('store.closed_message'),
                'cod_instructions' => settings()->get('checkout.cod_instructions'),
                'confirmation_note' => settings()->get('checkout.confirmation_note'),
            ],
            'country' => settings()->get('store.country'),
        ], Seo::private('Checkout'));
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder, CartService $cart): RedirectResponse
    {
        $data = $request->toData();

        $issued = (array) $request->session()->get(self::KEYS, []);
        $alreadyPlaced = Order::query()->where('idempotency_key', $data->idempotencyKey)->value('id');
        if (! in_array($data->idempotencyKey, $issued, true) && $alreadyPlaced === null) {
            return redirect()->route('checkout')->with('error', 'This checkout page has expired. Please check your order and try again.');
        }
        // A key can only retrieve an order placed from this same session.
        if ($alreadyPlaced !== null && ! in_array($alreadyPlaced, (array) $request->session()->get(self::PLACED, []), true)) {
            abort(404);
        }

        $order = $placeOrder->handle($data);

        $cart->forget();
        $request->session()->put(self::KEYS, array_values(array_diff($issued, [$data->idempotencyKey])));
        $placed = array_slice(array_unique([...(array) $request->session()->get(self::PLACED, []), $order->id]), -10);
        $request->session()->put(self::PLACED, $placed);

        return redirect()->route('checkout.confirmation', $order);
    }

    /**
     * One key per checkout page view; resubmitting with the same key returns
     * the same order instead of creating a duplicate.
     */
    private function issueKey(Request $request): string
    {
        $key = (string) Str::uuid();
        $keys = array_slice([...(array) $request->session()->get(self::KEYS, []), $key], -5);
        $request->session()->put(self::KEYS, $keys);

        return $key;
    }

    public function confirmation(Request $request, Order $order): Response
    {
        // Only the browser session that placed the order can see this page.
        abort_unless(in_array($order->id, (array) $request->session()->get(self::PLACED, []), true), 404);

        $order->load('items');

        return $this->render('checkout/confirmation', [
            'order' => OrderPresenter::forCustomer($order, withDelivery: true),
            'checkout' => [
                'cod_instructions' => settings()->get('checkout.cod_instructions'),
                'confirmation_note' => settings()->get('checkout.confirmation_note'),
            ],
        ], Seo::private('Thank you for your order'));
    }
}

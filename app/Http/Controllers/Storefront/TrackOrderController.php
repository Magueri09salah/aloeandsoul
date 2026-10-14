<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\OrderPresenter;
use App\Http\Requests\Storefront\TrackOrderRequest;
use App\Models\Order;
use App\Services\Seo\Seo;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Order status lookup by reference + phone number. Both must match; the same
 * generic message is shown whichever one is wrong.
 */
class TrackOrderController extends Controller
{
    public function show(Request $request): Response
    {
        $orderId = $request->session()->get('tracked_order');
        $order = $orderId ? Order::query()->with('items')->find($orderId) : null;

        return $this->render('track-order', [
            'order' => $order ? OrderPresenter::forCustomer($order) : null,
        ], Seo::private(__('Track your order')));
    }

    public function lookup(TrackOrderRequest $request): RedirectResponse
    {
        $phone = Phone::normalize((string) $request->validated('phone'), settings()->country());

        $order = $phone === null ? null : Order::query()
            ->where('reference', $request->validated('reference'))
            ->where('customer_phone', $phone)
            ->first();

        if ($order === null) {
            return back()->withErrors(['reference' => __('We could not find an order with this reference and phone number.')])->withInput();
        }

        return redirect(localized_route('track-order'))->with('tracked_order', $order->id);
    }
}

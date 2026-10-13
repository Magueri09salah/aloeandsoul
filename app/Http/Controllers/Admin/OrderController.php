<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Presenters\AdminOrderPresenter;
use App\Http\Requests\Admin\OrderActionRequest;
use App\Models\Order;
use App\Services\Orders\OrderWorkflow;
use App\Services\Orders\PaymentRecorder;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ViewOrders->value);

        $filters = [
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '',
            // 'to_fulfil' = everything confirmed but not yet with the courier.
            'status' => $request->query('status') === 'to_fulfil' ? 'to_fulfil' : OrderStatus::tryFrom((string) $request->query('status'))?->value,
            'payment' => PaymentStatus::tryFrom((string) $request->query('payment'))?->value,
            'from' => $this->date($request->query('from')),
            'to' => $this->date($request->query('to')),
            'flagged' => $request->boolean('flagged'),
        ];
        $tz = (string) config('store.display_timezone');

        $orders = Order::query()
            ->when($filters['q'] !== '', function (Builder $q) use ($filters) {
                $term = $filters['q'];
                $like = '%'.addcslashes($term, '%_\\').'%';
                $phone = Phone::normalize($term, settings()->country());
                $digits = (string) preg_replace('/\D/', '', $term);
                $q->where(fn (Builder $w) => $w
                    ->where('reference', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    // Partial phone search only with enough digits to be meaningful.
                    ->when(strlen($digits) >= 4, fn ($w2) => $w2->orWhere('customer_phone', 'like', '%'.$digits.'%'))
                    ->when($phone, fn ($w2) => $w2->orWhere('customer_phone', $phone)));
            })
            ->when($filters['status'] === 'to_fulfil', fn (Builder $q) => $q->whereIn('status', OrderStatus::awaitingFulfilment()))
            ->when($filters['status'] && $filters['status'] !== 'to_fulfil', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['payment'], fn (Builder $q, string $p) => $q->where('payment_status', $p))
            ->when($filters['from'], fn (Builder $q, string $d) => $q->where('created_at', '>=', CarbonImmutable::parse($d, $tz)->startOfDay()->utc()))
            ->when($filters['to'], fn (Builder $q, string $d) => $q->where('created_at', '<=', CarbonImmutable::parse($d, $tz)->endOfDay()->utc()))
            ->when($filters['flagged'], fn (Builder $q) => $q->whereNotNull('risk_flags'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/orders/index', [
            'orders' => $orders->through(fn (Order $o) => AdminOrderPresenter::row($o)),
            'filters' => $filters,
            'statuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'counts' => Order::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        Gate::authorize(Permission::ViewOrders->value);

        $order->load(['items.product:id,slug', 'events.user:id,name', 'customer', 'paymentRecordedBy:id,name']);

        return Inertia::render('admin/orders/show', [
            'order' => AdminOrderPresenter::detail($order),
            'canManage' => $request->user()->can(Permission::ManageOrders->value),
        ]);
    }

    public function transition(OrderActionRequest $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $to = OrderStatus::from($request->validated('to'));
        $workflow->transition(
            $order,
            $to,
            $request->user(),
            $request->validated('note'),
            restock: $request->boolean('restock', true),
            collectPayment: $request->boolean('collect_payment'),
        );

        return back()->with('success', "Order marked as “{$to->label()}”.");
    }

    public function payment(OrderActionRequest $request, Order $order, PaymentRecorder $recorder): RedirectResponse
    {
        $to = PaymentStatus::from($request->validated('payment_status'));
        $recorder->record($order, $to, $request->user(), $request->amountMinor(), $request->validated('note'));

        return back()->with('success', "Payment recorded as “{$to->label()}”.");
    }

    public function note(OrderActionRequest $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $workflow->addNote($order, $request->user(), (string) $request->validated('note'));

        return back()->with('success', 'Note added.');
    }

    public function delivery(OrderActionRequest $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $workflow->updateDelivery($order, $request->user(), $request->validated('courier_name'), $request->validated('tracking_number'));

        return back()->with('success', 'Delivery details saved.');
    }

    public function packingSlip(Order $order): View
    {
        Gate::authorize(Permission::ViewOrders->value);

        $order->load('items');

        return view('admin.packing-slip', [
            'order' => $order,
            'store' => settings()->group('store'),
            'contact' => settings()->group('contact'),
            'phone' => Phone::display($order->customer_phone, settings()->country()),
            'placedAt' => $order->created_at->setTimezone((string) config('store.display_timezone')),
        ]);
    }

    private function date(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }
}

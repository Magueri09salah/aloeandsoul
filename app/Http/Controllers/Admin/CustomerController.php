<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Presenters\AdminOrderPresenter;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Audit\AuditLogger;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ViewCustomers->value);

        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
        $country = settings()->country();

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['orders as delivered_value_minor' => fn (Builder $o) => $o->where('status', OrderStatus::Delivered)], 'total_minor')
            ->when($q !== '', function (Builder $query) use ($q, $country) {
                $digits = (string) preg_replace('/\D/', '', $q);
                $phone = Phone::normalize($q, $country);
                $query->where(fn (Builder $w) => $w
                    ->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')
                    ->when(strlen($digits) >= 4, fn ($w2) => $w2->orWhere('phone', 'like', '%'.$digits.'%'))
                    ->when($phone, fn ($w2) => $w2->orWhere('phone', $phone)));
            })
            ->latest('last_order_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/customers/index', [
            'customers' => $customers->through(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->isAnonymized() ? '—' : Phone::display($c->phone, $country),
                'city' => $c->city,
                'orders_count' => $c->orders_count,
                'delivered_value_minor' => (int) $c->delivered_value_minor,
                'last_order_at' => $c->last_order_at?->toIso8601String(),
                'anonymized' => $c->isAnonymized(),
            ]),
            'filters' => ['q' => $q],
            'currency' => settings()->currency(),
        ]);
    }

    public function show(Request $request, Customer $customer): Response
    {
        Gate::authorize(Permission::ViewCustomers->value);

        $orders = $customer->orders()->get();

        return Inertia::render('admin/customers/show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->isAnonymized() ? null : Phone::display($customer->phone, settings()->country()),
                'whatsapp' => $customer->isAnonymized() ? null : Phone::whatsappDigits($customer->phone),
                'email' => $customer->email,
                'city' => $customer->city,
                'first_order_at' => $customer->first_order_at?->toIso8601String(),
                'last_order_at' => $customer->last_order_at?->toIso8601String(),
                'anonymized_at' => $customer->anonymized_at?->toIso8601String(),
            ],
            'stats' => [
                'orders' => $orders->count(),
                'delivered' => $orders->where('status', OrderStatus::Delivered)->count(),
                'failed_or_returned' => $orders->whereIn('status', [OrderStatus::FailedDelivery, OrderStatus::Returned])->count(),
                'cancelled' => $orders->where('status', OrderStatus::Cancelled)->count(),
                'collected_minor' => (int) $orders->sum('payment_collected_minor'),
            ],
            'orders' => $orders->map(fn (Order $o) => AdminOrderPresenter::row($o)),
            'currency' => settings()->currency(),
            'canAnonymize' => $request->user()->can(Permission::AnonymizeCustomers->value),
        ]);
    }

    /**
     * Right-to-erasure: replace personal data on the customer and all of their
     * orders. Amounts and line items are kept for accounting.
     */
    public function anonymize(Request $request, Customer $customer, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize(Permission::AnonymizeCustomers->value);

        $open = $customer->orders()->whereNotIn('status', [
            OrderStatus::Delivered, OrderStatus::Cancelled, OrderStatus::Returned,
        ])->exists();
        if ($open) {
            return back()->with('error', 'This customer has orders still in progress. Complete or cancel them first.');
        }

        DB::transaction(function () use ($customer) {
            $placeholder = 'anon-'.$customer->id;
            Order::query()->where('customer_id', $customer->id)->update([
                'customer_name' => 'Anonymised customer',
                'customer_phone' => $placeholder,
                'customer_email' => null,
                'address' => 'Removed',
                'delivery_notes' => null,
                'ip_hash' => null,
            ]);
            $customer->forceFill([
                'name' => 'Anonymised customer',
                'phone' => $placeholder,
                'email' => null,
                'anonymized_at' => now(),
            ])->save();
        });

        $audit->log('customer.anonymized', $customer);

        return back()->with('success', 'Personal data removed. Order totals are kept for your records.');
    }
}

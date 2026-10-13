<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const RANGES = ['today' => 0, '7d' => 6, '30d' => 29, '90d' => 89];

    public function __invoke(Request $request): Response
    {
        Gate::authorize(Permission::ViewDashboard->value);

        $range = array_key_exists((string) $request->query('range'), self::RANGES) ? (string) $request->query('range') : '30d';
        $tz = (string) config('store.display_timezone');
        $today = CarbonImmutable::now($tz)->startOfDay();
        $from = $today->subDays(self::RANGES[$range]);
        $fromUtc = $from->utc();

        $inRange = fn () => Order::query()->where('created_at', '>=', $fromUtc);

        $metrics = [
            'orders_today' => Order::query()->where('created_at', '>=', $today->utc())->count(),
            'awaiting_confirmation' => Order::query()->where('status', OrderStatus::PendingConfirmation)->count(),
            'awaiting_fulfilment' => Order::query()->whereIn('status', OrderStatus::awaitingFulfilment())->count(),
            'out_for_delivery' => Order::query()->where('status', OrderStatus::Shipped)->count(),
            'orders_in_range' => $inRange()->count(),
            'delivered' => Order::query()->where('status', OrderStatus::Delivered)->where('delivered_at', '>=', $fromUtc)->count(),
            'cancelled' => Order::query()->where('status', OrderStatus::Cancelled)->where('cancelled_at', '>=', $fromUtc)->count(),
            // Value of orders placed (not cancelled) — NOT revenue.
            'order_value_minor' => (int) $inRange()->where('status', '!=', OrderStatus::Cancelled)->sum('total_minor'),
            // Cash actually collected on delivered orders — the real sales figure.
            'collected_minor' => (int) Order::query()
                ->where('payment_status', PaymentStatus::Collected)
                ->where('payment_collected_at', '>=', $fromUtc)
                ->sum('payment_collected_minor'),
            'awaiting_collection_minor' => (int) Order::query()
                ->where('status', OrderStatus::Delivered)
                ->where('payment_status', PaymentStatus::Pending)
                ->sum('total_minor'),
        ];

        $bestsellers = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $fromUtc)
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('units')
            ->limit(5)
            ->get([
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.line_total_minor) as value_minor'),
            ])
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name' => $row->product_name,
                'units' => (int) $row->units,
                'value_minor' => (int) $row->value_minor,
            ]);

        $defaultThreshold = (int) settings()->get('store.low_stock_threshold', 5);
        $lowStock = ProductVariant::query()
            ->with('product:id,name,status')
            ->whereHas('product', fn ($q) => $q->where('status', ProductStatus::Published))
            ->where('is_active', true)
            ->where('track_stock', true)
            ->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [$defaultThreshold])
            ->orderBy('stock')
            ->limit(8)
            ->get()
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'product_id' => $v->product_id,
                'name' => $v->displayName(),
                'stock' => $v->stock,
            ]);

        $recent = Order::query()->latest()->limit(8)->get()->map(fn (Order $o) => [
            'reference' => $o->reference,
            'customer_name' => $o->customer_name,
            'city' => $o->city,
            'total_minor' => $o->total_minor,
            'currency' => $o->currency,
            'status' => $o->status->value,
            'status_label' => $o->status->label(),
            'payment_status' => $o->payment_status->value,
            'created_at' => $o->created_at?->toIso8601String(),
            'risk' => ! empty($o->risk_flags),
        ]);

        // Daily series for the selected range, bucketed in the store timezone.
        $series = [];
        for ($d = $from; $d->lte($today); $d = $d->addDay()) {
            $series[$d->toDateString()] = ['date' => $d->toDateString(), 'orders' => 0, 'value_minor' => 0];
        }
        $inRange()->where('status', '!=', OrderStatus::Cancelled)->get(['created_at', 'total_minor'])
            ->each(function (Order $o) use (&$series, $tz) {
                $day = $o->created_at->setTimezone($tz)->toDateString();
                if (isset($series[$day])) {
                    $series[$day]['orders']++;
                    $series[$day]['value_minor'] += $o->total_minor;
                }
            });

        return Inertia::render('admin/dashboard', [
            'range' => $range,
            'currency' => settings()->currency(),
            'metrics' => $metrics,
            'series' => array_values($series),
            'bestsellers' => $bestsellers,
            'lowStock' => $lowStock,
            'recentOrders' => $recent,
            'checklist' => $this->checklist($request),
        ]);
    }

    /** @return list<array{label: string, done: bool, href: string|null}> */
    private function checklist(Request $request): array
    {
        $user = $request->user();
        $contact = settings()->group('contact');

        return [
            [
                'label' => 'Publish at least one product (price, image and description)',
                'done' => Product::query()->where('status', ProductStatus::Published)->exists(),
                'href' => $user->can(Permission::ManageCatalog->value) ? route('admin.products.index', ['status' => 'draft']) : null,
            ],
            [
                'label' => 'Add delivery areas and fees',
                'done' => ShippingZone::query()->where('is_active', true)->exists(),
                'href' => $user->can(Permission::ManageMarketing->value) ? route('admin.zones.index') : null,
            ],
            [
                'label' => 'Review the legal and policy pages',
                'done' => Page::query()->where('needs_review', true)->doesntExist(),
                'href' => $user->can(Permission::ManageContent->value) ? route('admin.pages.index') : null,
            ],
            [
                'label' => 'Check contact details shown on the site',
                'done' => filled($contact['email']) && filled($contact['phone']),
                'href' => $user->can(Permission::ManageSettings->value) ? route('admin.settings.edit') : null,
            ],
            [
                'label' => 'Turn on two-factor authentication for your account',
                'done' => $user->hasTwoFactorEnabled(),
                'href' => route('admin.profile'),
            ],
        ];
    }
}

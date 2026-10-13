<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ShippingZoneController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        Gate::authorize(Permission::ManageMarketing->value);
        $currency = settings()->currency();

        return Inertia::render('admin/zones/index', [
            'zones' => ShippingZone::query()->orderBy('sort_order')->orderBy('name')->get()->map(fn (ShippingZone $z) => [
                'id' => $z->id,
                'name' => $z->name,
                'fee' => Money::toDecimalString($z->fee_minor, $currency),
                'free_over' => Money::toDecimalString($z->free_over_minor, $currency),
                'estimate' => $z->estimate,
                'is_active' => $z->is_active,
                'sort_order' => $z->sort_order,
            ]),
            'currency' => $currency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageMarketing->value);
        $zone = ShippingZone::query()->create($this->validated($request));
        $this->audit->log('zone.created', $zone, ['name' => $zone->name, 'fee_minor' => $zone->fee_minor]);

        return back()->with('success', 'Delivery area added.');
    }

    public function update(Request $request, ShippingZone $zone): RedirectResponse
    {
        Gate::authorize(Permission::ManageMarketing->value);
        $zone->fill($this->validated($request));
        $changes = AuditLogger::diff($zone);
        $zone->save();
        $this->audit->log('zone.updated', $zone, $changes);

        return back()->with('success', 'Delivery area saved.');
    }

    public function destroy(ShippingZone $zone): RedirectResponse
    {
        Gate::authorize(Permission::ManageMarketing->value);

        if (Order::query()->where('shipping_zone_id', $zone->id)->exists()) {
            $zone->update(['is_active' => false]);

            return back()->with('success', 'This area is used by past orders, so it was turned off instead of deleted.');
        }

        $this->audit->log('zone.deleted', $zone, ['name' => $zone->name]);
        $zone->delete();

        return back()->with('success', 'Delivery area deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'fee' => ['required', 'string', 'max:12'],
            'free_over' => ['nullable', 'string', 'max:12'],
            'estimate' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ]);

        $currency = settings()->currency();
        try {
            $fee = Money::parse($data['fee'], $currency);
            $freeOver = filled($data['free_over'] ?? null) ? Money::parse($data['free_over'], $currency) : null;
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['fee' => 'Enter amounts like 30 or 29.90.']);
        }

        return [
            'name' => trim($data['name']),
            'fee_minor' => (int) $fee,
            'free_over_minor' => $freeOver,
            'estimate' => $data['estimate'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}

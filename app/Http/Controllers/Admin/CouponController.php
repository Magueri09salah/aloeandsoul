<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CouponController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        Gate::authorize(Permission::ManageMarketing->value);
        $currency = settings()->currency();
        $tz = (string) config('store.display_timezone');

        return Inertia::render('admin/coupons/index', [
            'coupons' => Coupon::query()->withCount(['redemptions as active_redemptions' => fn ($q) => $q->whereNull('released_at')])
                ->latest()->get()->map(fn (Coupon $c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'description' => $c->description,
                    'type' => $c->type->value,
                    'value' => $c->type === CouponType::Percent ? (string) $c->value : Money::toDecimalString($c->value, $currency),
                    'min_subtotal' => Money::toDecimalString($c->min_subtotal_minor, $currency),
                    'max_uses' => $c->max_uses,
                    'uses_count' => $c->uses_count,
                    'once_per_customer' => $c->once_per_customer,
                    'starts_at' => $c->starts_at?->setTimezone($tz)->format('Y-m-d\TH:i'),
                    'ends_at' => $c->ends_at?->setTimezone($tz)->format('Y-m-d\TH:i'),
                    'is_active' => $c->is_active,
                    'expired' => $c->ends_at !== null && $c->ends_at->isPast(),
                ]),
            'currency' => $currency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageMarketing->value);
        $coupon = new Coupon;
        $coupon->fill($this->validated($request))->save();
        $this->audit->log('coupon.created', $coupon, ['code' => $coupon->code]);

        return back()->with('success', "Code {$coupon->code} created.");
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        Gate::authorize(Permission::ManageMarketing->value);
        $coupon->fill($this->validated($request, $coupon));
        $changes = AuditLogger::diff($coupon);
        $coupon->save();
        $this->audit->log('coupon.updated', $coupon, $changes);

        return back()->with('success', "Code {$coupon->code} saved.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        Gate::authorize(Permission::ManageMarketing->value);

        if ($coupon->redemptions()->exists()) {
            return back()->with('error', 'This code has been used on orders, so it cannot be deleted. Turn it off instead.');
        }
        $this->audit->log('coupon.deleted', $coupon, ['code' => $coupon->code]);
        $coupon->delete();

        return back()->with('success', 'Code deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => Coupon::normalizeCode((string) $request->input('code'))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'description' => ['nullable', 'string', 'max:160'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'string', 'max:12'],
            'min_subtotal' => ['nullable', 'string', 'max:12'],
            'max_uses' => ['nullable', 'integer', 'between:1,1000000'],
            'once_per_customer' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ], [
            'code.regex' => 'Use letters, numbers, dashes and underscores only.',
            'code.unique' => 'This code already exists.',
            'ends_at.after' => 'The end date must be after the start date.',
        ]);

        $currency = settings()->currency();
        $tz = (string) config('store.display_timezone');
        $type = CouponType::from($data['type']);

        try {
            $value = $type === CouponType::Percent ? (int) $data['value'] : Money::parse($data['value'], $currency);
            $min = filled($data['min_subtotal'] ?? null) ? Money::parse($data['min_subtotal'], $currency) : null;
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['value' => 'Enter an amount like 50 or 49.90.']);
        }

        if ($type === CouponType::Percent && (! ctype_digit($data['value']) || $value < 1 || $value > 100)) {
            throw ValidationException::withMessages(['value' => 'Enter a whole percentage between 1 and 100.']);
        }
        if ($type === CouponType::Fixed && $value < 1) {
            throw ValidationException::withMessages(['value' => 'Enter an amount greater than zero.']);
        }

        return [
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'type' => $type,
            'value' => $value,
            'min_subtotal_minor' => $min,
            'max_uses' => $data['max_uses'] ?? null,
            'once_per_customer' => $request->boolean('once_per_customer'),
            'starts_at' => filled($data['starts_at'] ?? null) ? CarbonImmutable::parse($data['starts_at'], $tz)->utc() : null,
            'ends_at' => filled($data['ends_at'] ?? null) ? CarbonImmutable::parse($data['ends_at'], $tz)->utc() : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

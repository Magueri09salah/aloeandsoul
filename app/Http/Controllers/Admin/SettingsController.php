<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Audit\AuditLogger;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use libphonenumber\PhoneNumberUtil;

/**
 * Store-wide settings (owner only).
 */
class SettingsController extends Controller
{
    public const CURRENCIES = ['MAD', 'EUR', 'USD', 'GBP'];

    public function edit(): Response
    {
        Gate::authorize(Permission::ManageSettings->value);

        return Inertia::render('admin/settings', [
            'settings' => [
                'store' => settings()->group('store'),
                'checkout' => settings()->group('checkout'),
                'contact' => settings()->group('contact'),
                'social' => settings()->group('social'),
                'seo' => settings()->group('seo'),
            ],
            'currencies' => self::CURRENCIES,
            'hasOrders' => Order::query()->exists(),
        ]);
    }

    public function update(Request $request, string $group, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize(Permission::ManageSettings->value);

        $values = match ($group) {
            'store' => $this->store($request),
            'checkout' => $request->validate([
                'cod_instructions' => ['required', 'string', 'max:300'],
                'confirmation_note' => ['nullable', 'string', 'max:300'],
                'delivery_info' => ['nullable', 'string', 'max:300'],
            ]),
            'contact' => $this->contact($request),
            'social' => $request->validate(collect(['instagram', 'facebook', 'tiktok', 'youtube'])
                ->mapWithKeys(fn ($k) => [$k => ['nullable', 'string', 'max:255', 'url:https']])->all()),
            'seo' => $request->validate([
                'title' => ['required', 'string', 'max:70'],
                'description' => ['required', 'string', 'max:170'],
            ]),
        };

        $values = array_map(fn ($v) => $v ?? '', $values);
        $changes = settings()->update($group, $values);
        $audit->log("settings.{$group}_updated", null, $changes);

        return back()->with('success', 'Settings saved.');
    }

    /** @return array<string, mixed> */
    private function store(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'currency' => ['required', Rule::in(self::CURRENCIES)],
            'country' => ['required', 'string', 'size:2', Rule::in(PhoneNumberUtil::getInstance()->getSupportedRegions())],
            'country_name' => ['required', 'string', 'max:60'],
            'order_prefix' => ['required', 'string', 'max:6', 'regex:/^[A-Z0-9]+$/'],
            'low_stock_threshold' => ['required', 'integer', 'between:0,1000'],
            'max_quantity_per_line' => ['required', 'integer', 'between:1,99'],
            'ordering_enabled' => ['boolean'],
            'closed_message' => ['required', 'string', 'max:300'],
        ], ['order_prefix.regex' => 'Use capital letters and numbers only (e.g. AS).']);

        $data['ordering_enabled'] = $request->boolean('ordering_enabled');
        $data['low_stock_threshold'] = (int) $data['low_stock_threshold'];
        $data['max_quantity_per_line'] = (int) $data['max_quantity_per_line'];

        return $data;
    }

    /** @return array<string, mixed> */
    private function contact(Request $request): array
    {
        $data = $request->validate([
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'locations' => ['array', 'max:6'],
            'locations.*' => ['required', 'string', 'max:40'],
            'hours' => ['nullable', 'string', 'max:120'],
        ]);

        $country = settings()->country();
        foreach (['phone', 'whatsapp'] as $key) {
            if (filled($data[$key] ?? null)) {
                $normalized = Phone::normalize($data[$key], $country);
                if ($normalized === null) {
                    throw ValidationException::withMessages([$key => 'Enter a valid phone number.']);
                }
                $data[$key] = $normalized;
            }
        }
        $data['locations'] = array_values($data['locations'] ?? []);

        return $data;
    }
}

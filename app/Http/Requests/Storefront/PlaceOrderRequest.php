<?php

namespace App\Http\Requests\Storefront;

use App\Services\Checkout\CheckoutData;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v) ?? '') : $v;

        $this->merge([
            'name' => $clean($this->input('name')),
            'city' => $clean($this->input('city')),
            'address' => $clean($this->input('address')),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) ?: null : null,
            'notes' => is_string($this->input('notes')) ? trim($this->input('notes')) ?: null : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'shipping_zone_id' => ['required', 'integer', 'min:1'],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'privacy' => ['accepted'],
            'idempotency_key' => ['required', 'uuid'],
            'expected_total' => ['required', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('phone')) {
                    return;
                }
                if (Phone::normalize((string) $this->input('phone'), settings()->country()) === null) {
                    $validator->errors()->add('phone', 'Enter a valid phone number so the courier can reach you.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter your full name.',
            'phone.required' => 'Enter a phone number so the courier can reach you.',
            'shipping_zone_id.required' => 'Choose a delivery area.',
            'city.required' => 'Enter your city.',
            'address.required' => 'Enter your delivery address.',
            'address.min' => 'Enter your full delivery address (street, number, building).',
            'privacy.accepted' => 'Please accept the privacy policy to place your order.',
            'idempotency_key.*' => 'This checkout page has expired. Please reload the page.',
            'expected_total.*' => 'This checkout page has expired. Please reload the page.',
        ];
    }

    public function toData(): CheckoutData
    {
        return new CheckoutData(
            name: (string) $this->validated('name'),
            phone: (string) Phone::normalize((string) $this->validated('phone'), settings()->country()),
            email: $this->validated('email'),
            shippingZoneId: (int) $this->validated('shipping_zone_id'),
            city: (string) $this->validated('city'),
            address: (string) $this->validated('address'),
            notes: $this->validated('notes'),
            idempotencyKey: mb_strtolower((string) $this->validated('idempotency_key')),
            expectedTotalMinor: (int) $this->validated('expected_total'),
            ipHash: ip_hash($this->ip()),
        );
    }
}

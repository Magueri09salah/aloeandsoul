<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * Validation for every order mutation (status, payment, note, delivery).
 * The rule set is picked from the route name.
 */
class OrderActionRequest extends AdminRequest
{
    protected function permission(): Permission
    {
        return Permission::ManageOrders;
    }

    protected function prepareForValidation(): void
    {
        $this->nullIfBlank('note', 'courier_name', 'tracking_number', 'amount');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'admin.orders.transition' => [
                'to' => ['required', Rule::enum(OrderStatus::class)],
                'note' => ['nullable', 'string', 'max:1000'],
                'restock' => ['boolean'],
                'collect_payment' => ['boolean'],
            ],
            'admin.orders.payment' => [
                'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
                'amount' => ['nullable', 'string', 'max:15'],
                'note' => ['nullable', 'string', 'max:1000'],
            ],
            'admin.orders.note' => [
                'note' => ['required', 'string', 'max:2000'],
            ],
            'admin.orders.delivery' => [
                'courier_name' => ['nullable', 'string', 'max:80'],
                'tracking_number' => ['nullable', 'string', 'max:80'],
            ],
            default => [],
        };
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->filled('amount') && $this->amountMinor() === null) {
                $validator->errors()->add('amount', 'Enter an amount like 249.50.');
            }
        }];
    }

    public function amountMinor(): ?int
    {
        if (! $this->filled('amount')) {
            return null;
        }

        try {
            return Money::parse((string) $this->input('amount'), $this->route('order')->currency);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function messages(): array
    {
        return ['note.required' => 'Write a note first.'];
    }
}

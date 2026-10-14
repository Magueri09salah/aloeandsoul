<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reference'))) {
            $this->merge(['reference' => strtoupper(trim($this->input('reference')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]{1,6}-\d{4}-[A-Z0-9]{5}$/'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference.regex' => __('Order references look like AS-2610-K7Q3X.'),
        ];
    }
}

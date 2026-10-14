<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

/** Add to cart (variant + quantity) or change a line's quantity. */
class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $adding = $this->isMethod('post');

        return [
            'variant_id' => $adding ? ['required', 'integer', 'min:1'] : ['prohibited'],
            'quantity' => ['required', 'integer', $adding ? 'min:1' : 'min:0', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.max' => __('Please choose a smaller quantity.'),
        ];
    }
}

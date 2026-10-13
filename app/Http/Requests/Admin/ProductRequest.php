<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class ProductRequest extends AdminRequest
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }

    protected function prepareForValidation(): void
    {
        $this->nullIfBlank('summary', 'description', 'option_label', 'seo_title', 'seo_description');

        $name = is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name');
        $slug = is_string($this->input('slug')) ? trim($this->input('slug')) : '';
        $this->merge([
            'name' => $name,
            'slug' => $slug !== '' ? Str::slug($slug) : (is_string($name) ? Str::slug($name) : null),
        ]);

        $variants = $this->input('variants');
        if (is_array($variants)) {
            $this->merge(['variants' => array_map(function ($v) {
                if (! is_array($v)) {
                    return $v;
                }
                foreach (['name', 'sku', 'price', 'sale_price'] as $key) {
                    if (isset($v[$key]) && is_string($v[$key])) {
                        $v[$key] = trim($v[$key]) === '' ? null : trim($v[$key]);
                    }
                }

                return $v;
            }, $variants)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($product?->id)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'specifications' => ['nullable', 'array', 'max:20'],
            'specifications.*.label' => ['required', 'string', 'max:60'],
            'specifications.*.value' => ['required', 'string', 'max:160'],
            'option_label' => ['nullable', 'string', 'max:40'],
            'is_featured' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:-10000,10000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['nullable', 'string', 'max:80'],
            'variants.*.sku' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9._\-]+$/', 'distinct:ignore_case'],
            'variants.*.price' => ['nullable', 'string', 'max:12'],
            'variants.*.sale_price' => ['nullable', 'string', 'max:12'],
            'variants.*.stock' => ['required', 'integer', 'between:0,1000000'],
            'variants.*.track_stock' => ['boolean'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'between:0,10000'],
            'variants.*.is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Product|null $product */
            $product = $this->route('product');
            $ownIds = $product ? $product->variants()->pluck('id')->all() : [];
            $variants = (array) $this->input('variants');
            $currency = settings()->currency();

            foreach ($variants as $i => $v) {
                if (! empty($v['id']) && ! in_array((int) $v['id'], $ownIds, true)) {
                    $validator->errors()->add("variants.{$i}.id", 'This option does not belong to this product.');
                }

                if (count($variants) > 1 && blank($v['name'] ?? null)) {
                    $validator->errors()->add("variants.{$i}.name", 'Name each option (e.g. Vanilla, 500 ml).');
                }

                $price = $this->parse($v['price'] ?? null, $currency);
                $sale = $this->parse($v['sale_price'] ?? null, $currency);
                if ($price === false) {
                    $validator->errors()->add("variants.{$i}.price", 'Enter a price like 249 or 249.50.');
                }
                if ($sale === false) {
                    $validator->errors()->add("variants.{$i}.sale_price", 'Enter a price like 199 or 199.50.');
                }
                if (is_int($sale) && (! is_int($price) || $sale >= $price)) {
                    $validator->errors()->add("variants.{$i}.sale_price", 'The sale price must be lower than the regular price.');
                }

                if (! empty($v['sku'])) {
                    $taken = ProductVariant::query()
                        ->where('sku', $v['sku'])
                        ->when(! empty($v['id']), fn ($q) => $q->whereKeyNot((int) $v['id']))
                        ->exists();
                    if ($taken) {
                        $validator->errors()->add("variants.{$i}.sku", 'This SKU is already used by another product.');
                    }
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Another product already uses this web address.',
            'slug.regex' => 'Use lowercase letters, numbers and dashes only.',
            'variants.*.sku.distinct' => 'Each option needs a different SKU.',
            'variants.*.sku.regex' => 'SKUs can contain letters, numbers, dots, dashes and underscores.',
            'specifications.*.label.required' => 'Give this detail a label (e.g. Volume).',
            'specifications.*.value.required' => 'Enter a value (e.g. 1 litre).',
        ];
    }

    /** @return array<string, mixed> */
    public function productData(): array
    {
        $data = $this->safe()->except(['variants']);
        $data['is_featured'] = $this->boolean('is_featured');
        $data['is_bestseller'] = $this->boolean('is_bestseller');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['specifications'] = array_values($data['specifications'] ?? []) ?: null;

        return $data;
    }

    /**
     * @return list<array{id: int|null, name: string|null, sku: string|null, price_minor: int|null, sale_price_minor: int|null, stock: int, track_stock: bool, low_stock_threshold: int|null, is_active: bool, sort_order: int}>
     */
    public function variantsData(): array
    {
        $currency = settings()->currency();

        return array_values(array_map(fn (array $v, int $i) => [
            'id' => ! empty($v['id']) ? (int) $v['id'] : null,
            'name' => $v['name'] ?? null,
            'sku' => $v['sku'] ?? null,
            'price_minor' => $this->parse($v['price'] ?? null, $currency) ?: null,
            'sale_price_minor' => $this->parse($v['sale_price'] ?? null, $currency) ?: null,
            'stock' => (int) $v['stock'],
            'track_stock' => (bool) ($v['track_stock'] ?? true),
            'low_stock_threshold' => isset($v['low_stock_threshold']) && $v['low_stock_threshold'] !== '' ? (int) $v['low_stock_threshold'] : null,
            'is_active' => (bool) ($v['is_active'] ?? true),
            'sort_order' => $i,
        ], $this->validated('variants'), array_keys($this->validated('variants'))));
    }

    /** null when blank, false when invalid, int minor units otherwise. */
    private function parse(mixed $value, string $currency): int|false|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Money::parse((string) $value, $currency);
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}

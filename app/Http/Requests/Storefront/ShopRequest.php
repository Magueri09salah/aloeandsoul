<?php

namespace App\Http\Requests\Storefront;

use App\Services\Catalog\ProductQuery;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

/**
 * Catalogue filters from the query string. Every value is whitelisted or
 * bounded here; anything invalid is ignored (not an error) so a mangled
 * shared link still shows products.
 */
class ShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{q: string|null, availability: string|null, on_sale: bool, min_price: int|null, max_price: int|null, sort: string}
     */
    public function filters(): array
    {
        $q = $this->query('q');
        $q = is_string($q) ? trim($q) : null;

        return [
            'q' => $q !== null && $q !== '' && mb_strlen($q) <= 100 ? $q : null,
            'availability' => $this->query('availability') === 'in_stock' ? 'in_stock' : null,
            'on_sale' => in_array($this->query('on_sale'), ['1', 'true'], true),
            'min_price' => $this->price('min_price'),
            'max_price' => $this->price('max_price'),
            'sort' => in_array($this->query('sort'), ProductQuery::SORTS, true) ? (string) $this->query('sort') : 'featured',
        ];
    }

    public function hasRefinements(): bool
    {
        $f = $this->filters();

        return $f['q'] !== null || $f['availability'] !== null || $f['on_sale']
            || $f['min_price'] !== null || $f['max_price'] !== null || $f['sort'] !== 'featured';
    }

    public function page(): int
    {
        $page = filter_var($this->query('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);

        return $page === false ? 1 : $page;
    }

    private function price(string $key): ?int
    {
        $value = $this->query($key);
        if (! is_string($value) || $value === '' || strlen($value) > 12) {
            return null;
        }

        try {
            return Money::parse($value, settings()->currency());
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}

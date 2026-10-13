<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin form requests re-check the permission themselves, in addition to the
 * route middleware, so authorisation travels with the operation.
 */
abstract class AdminRequest extends FormRequest
{
    abstract protected function permission(): Permission;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->permission()->value);
    }

    /** Trim strings and turn empty strings into null for the given keys. */
    protected function nullIfBlank(string ...$keys): void
    {
        $merge = [];
        foreach ($keys as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $value = trim($value);
                $merge[$key] = $value === '' ? null : $value;
            }
        }
        $this->merge($merge);
    }
}

<?php

use App\Services\Settings\Settings;
use App\Support\Locales;

if (! function_exists('settings')) {
    function settings(): Settings
    {
        return app(Settings::class);
    }
}

if (! function_exists('ip_hash')) {
    /** Keyed hash of an IP address: lets us spot repeat abuse without storing the IP. */
    function ip_hash(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('store.ip_hash_key'));
    }
}

if (! function_exists('localized_route')) {
    /**
     * URL of a storefront route in the current language (or $locale):
     * localized_route('shop') is /shop in English and /fr/shop in French.
     */
    function localized_route(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale ??= app()->getLocale();

        return route($locale === Locales::DEFAULT ? $name : "{$locale}.{$name}", $parameters, $absolute);
    }
}

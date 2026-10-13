<?php

use App\Services\Settings\Settings;

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

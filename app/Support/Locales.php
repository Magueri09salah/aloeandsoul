<?php

namespace App\Support;

/**
 * The storefront languages. English is the default and lives at the root
 * (/shop); every other language is served under its own prefix (/fr/shop).
 * Content columns hold English; translations live in "<column>_<locale>".
 */
final class Locales
{
    public const DEFAULT = 'en';

    /** Code => name of the language in that language (for the switcher). */
    public const SUPPORTED = [
        'en' => 'English',
        'fr' => 'Français',
    ];

    /** Open Graph / Intl regional codes. */
    public const REGIONS = [
        'en' => 'en_GB',
        'fr' => 'fr_FR',
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    /**
     * Languages other than the default, i.e. the ones with a URL prefix and "_xx" columns.
     *
     * @return list<string>
     */
    public static function translations(): array
    {
        return array_values(array_diff(self::codes(), [self::DEFAULT]));
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::SUPPORTED);
    }

    /** The language a path belongs to, from its first segment ("/fr/shop" → fr). */
    public static function fromPath(string $path): string
    {
        $first = explode('/', trim(explode('?', $path, 2)[0], '/'))[0];

        return in_array($first, self::translations(), true) ? $first : self::DEFAULT;
    }

    /** "/fr/shop" → "/shop" (a path without query string). */
    public static function stripPrefix(string $path): string
    {
        $trimmed = trim($path, '/');
        $segments = $trimmed === '' ? [] : explode('/', $trimmed);
        if ($segments !== [] && in_array($segments[0], self::translations(), true)) {
            array_shift($segments);
        }

        return '/'.implode('/', $segments);
    }

    /** The same path in another language: ("/shop?q=a", fr) → "/fr/shop?q=a", ("/fr", en) → "/". */
    public static function path(string $path, string $locale): string
    {
        [$pathOnly, $query] = array_pad(explode('?', $path, 2), 2, null);
        $base = self::stripPrefix($pathOnly);
        $localized = $locale === self::DEFAULT ? $base : '/'.$locale.($base === '/' ? '' : $base);

        return $localized.($query !== null && $query !== '' ? '?'.$query : '');
    }

    /** The same absolute URL in another language. */
    public static function url(string $url, string $locale): string
    {
        $parts = parse_url($url);
        $origin = isset($parts['host'])
            ? ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : '';
        $path = self::path(($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : ''), $locale);

        return $origin.$path.(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}

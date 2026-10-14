<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Grouped key/value settings stored as JSON, merged over config('store.defaults').
 *
 * Reads are served from the cache (database store on shared hosting) and the
 * cache is flushed on every write, so admins see changes immediately.
 */
class Settings
{
    private const CACHE_KEY = 'settings.v1';

    /** @var array<string, array<string, mixed>>|null */
    private ?array $resolved = null;

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        /** @var array<string, array<string, mixed>> $stored */
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->pluck('value', 'key')
            ->map(fn ($value) => is_array($value) ? $value : [])
            ->all());

        $resolved = [];
        foreach ($this->defaults() as $group => $values) {
            $resolved[$group] = array_replace($values, Arr::only($stored[$group] ?? [], array_keys($values)));
        }

        return $this->resolved = $resolved;
    }

    /** @return array<string, mixed> */
    public function group(string $group): array
    {
        $all = $this->all();

        if (! array_key_exists($group, $all)) {
            throw new InvalidArgumentException("Unknown settings group [{$group}].");
        }

        return $all[$group];
    }

    /** Dot access: settings()->get('store.currency'). */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * A group as the storefront shows it in the current language: a filled
     * "<key>_fr" replaces "<key>" (also inside lists such as the story
     * chapters) and the translation keys are dropped.
     *
     * @return array<string, mixed>
     */
    public function localized(string $group, ?string $locale = null): array
    {
        return self::localize($this->group($group), $locale ?? app()->getLocale());
    }

    /** Dot access in the current language: settings()->localizedGet('checkout.cod_instructions'). */
    public function localizedGet(string $key, mixed $default = null): mixed
    {
        [$group, $rest] = array_pad(explode('.', $key, 2), 2, null);
        if ($rest === null || ! array_key_exists($group, $this->all())) {
            return $default;
        }

        return Arr::get($this->localized($group), $rest, $default);
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public static function localize(array $values, string $locale): array
    {
        $suffixes = array_map(fn (string $l) => "_{$l}", Locales::translations());

        $localized = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && Str::endsWith($key, $suffixes)) {
                continue;
            }
            if (is_string($key) && $locale !== Locales::DEFAULT) {
                $translation = $values["{$key}_{$locale}"] ?? null;
                if (is_string($translation) && trim($translation) !== '') {
                    $value = $translation;
                }
            }
            if (is_array($value)) {
                $value = array_map(fn ($item) => is_array($item) ? self::localize($item, $locale) : $item, $value);
            }
            $localized[$key] = $value;
        }

        return $localized;
    }

    public function currency(): string
    {
        return (string) $this->get('store.currency', 'MAD');
    }

    public function country(): string
    {
        return (string) $this->get('store.country', 'MA');
    }

    /**
     * Replace values in a group. Unknown keys are ignored so a request can
     * never create arbitrary settings.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, array{from: mixed, to: mixed}> changed keys
     */
    public function update(string $group, array $values): array
    {
        $current = $this->group($group);
        $next = array_replace($current, Arr::only($values, array_keys($current)));

        $changes = [];
        foreach ($next as $key => $value) {
            if ($current[$key] !== $value) {
                $changes[$key] = ['from' => $current[$key], 'to' => $value];
            }
        }

        Setting::query()->updateOrCreate(['key' => $group], ['value' => $next]);
        $this->flush();

        return $changes;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->resolved = null;
    }

    /** @return array<string, array<string, mixed>> */
    private function defaults(): array
    {
        return config('store.defaults');
    }
}

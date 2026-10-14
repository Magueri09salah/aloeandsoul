<?php

namespace App\Models\Concerns;

use App\Support\Locales;

/**
 * Text columns with translations stored next to them ("name" → "name_fr").
 * Models list them in a TRANSLATABLE constant. The English column is the
 * source; an empty translation falls back to it.
 */
trait HasTranslations
{
    /** The value of $attribute in $locale (default: the current one), falling back to English. */
    public function translated(string $attribute, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        if ($locale !== Locales::DEFAULT && in_array($attribute, static::TRANSLATABLE, true)) {
            $value = $this->getAttribute("{$attribute}_{$locale}");
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return $this->getAttribute($attribute);
    }

    /** Whether a translation exists for $attribute (an empty English source needs none). */
    public function hasTranslation(string $attribute, string $locale): bool
    {
        $source = $this->getAttribute($attribute);
        if (! is_string($source) || trim($source) === '') {
            return true;
        }

        $value = $this->getAttribute("{$attribute}_{$locale}");

        return is_string($value) && trim($value) !== '';
    }

    /**
     * "name_fr", "summary_fr"… for every translatable column and language.
     *
     * @return list<string>
     */
    public static function translationColumns(): array
    {
        $columns = [];
        foreach (Locales::translations() as $locale) {
            foreach (static::TRANSLATABLE as $attribute) {
                $columns[] = "{$attribute}_{$locale}";
            }
        }

        return $columns;
    }
}

<?php

namespace Database\Seeders\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Seeders add French texts to existing records only where the French column
 * is still empty, so re-running them never overwrites an edit made in the admin.
 */
trait FillsBlankTranslations
{
    /** @param  array<string, string|null>  $values */
    protected static function fillBlank(Model $model, array $values): void
    {
        foreach ($values as $column => $value) {
            if ($value !== null && blank($model->getAttribute($column))) {
                $model->setAttribute($column, $value);
            }
        }

        if ($model->isDirty()) {
            $model->save();
        }
    }
}

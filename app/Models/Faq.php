<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasTranslations;

    /** Columns with a French version ("name" → "name_fr"). */
    public const TRANSLATABLE = ['question', 'answer'];

    protected $fillable = ['question', 'answer', 'sort_order', 'is_published', 'show_on_home', 'question_fr', 'answer_fr'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }
}

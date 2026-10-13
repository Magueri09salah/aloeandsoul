<?php

namespace App\Models;

use Database\Factories\CollaboratorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A person, brand or business Aloe & Soul works with, shown on the home page.
 *
 * @property array{path: string, widths: list<int>, width: int, height: int}|null $image
 */
class Collaborator extends Model
{
    /** @use HasFactory<CollaboratorFactory> */
    use HasFactory;

    /** photo: cropped to fill the circle; logo: shown whole on white. */
    public const IMAGE_STYLES = ['photo', 'logo'];

    protected $fillable = ['name', 'description', 'url', 'image_style', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return [
            'image' => 'array',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true)->orderBy('sort_order')->orderBy('id');
    }
}

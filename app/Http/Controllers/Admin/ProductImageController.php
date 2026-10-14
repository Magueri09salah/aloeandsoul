<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Audit\AuditLogger;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProductImageController extends Controller
{
    public const MAX_IMAGES = 12;

    public function __construct(private readonly AuditLogger $audit) {}

    public function store(Request $request, Product $product, ImageProcessor $processor): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        $remaining = self::MAX_IMAGES - $product->images()->count();
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:'.max(0, $remaining)],
            // `image` checks the real file contents (MIME sniffing), not the
            // extension; SVG is excluded. GD then re-encodes every upload.
            'images.*' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max((int) config('store.images.max_kilobytes'))
                    ->dimensions(Rule::dimensions()
                        ->minWidth((int) config('store.images.min_width'))
                        ->maxWidth((int) config('store.images.max_dimension'))
                        ->maxHeight((int) config('store.images.max_dimension'))),
            ],
        ], [
            'images.max' => $remaining > 0 ? "You can add {$remaining} more image(s) to this product." : 'This product already has the maximum number of images.',
            'images.*.dimensions' => 'Images must be at least '.config('store.images.min_width').' px wide.',
            'images.*.max' => 'Each image must be smaller than '.(int) (config('store.images.max_kilobytes') / 1024).' MB.',
        ]);

        $next = (int) $product->images()->max('sort_order') + 1;
        foreach ($request->file('images') as $i => $file) {
            $stored = $processor->store($file, "products/{$product->id}");
            $product->images()->create([...$stored, 'alt' => $product->name, 'sort_order' => $next + $i]);
        }

        $this->audit->log('product.images_added', $product, ['count' => count($request->file('images'))]);

        return back()->with('success', 'Images uploaded.');
    }

    /** Reorder images and edit their alternative text. */
    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        $data = $request->validate([
            'images' => ['required', 'array', 'max:'.self::MAX_IMAGES],
            'images.*.id' => ['required', 'integer', Rule::exists('product_images', 'id')->where('product_id', $product->id)],
            'images.*.alt' => ['nullable', 'string', 'max:160'],
            'images.*.alt_fr' => ['nullable', 'string', 'max:160'],
        ]);

        DB::transaction(function () use ($data, $product) {
            foreach (array_values($data['images']) as $position => $row) {
                ProductImage::query()->where('product_id', $product->id)->whereKey($row['id'])
                    ->update(['alt' => ($row['alt'] ?? null) ?: null, 'alt_fr' => ($row['alt_fr'] ?? null) ?: null, 'sort_order' => $position]);
            }
        });

        return back()->with('success', 'Images updated.');
    }

    public function destroy(Product $product, ProductImage $image, ImageProcessor $processor): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        if ($product->status === ProductStatus::Published && $product->images()->count() === 1) {
            return back()->with('error', 'A published product needs at least one image. Upload another image first, or unpublish the product.');
        }

        $image->delete();
        $processor->delete($image->path, $image->widths);
        $this->audit->log('product.image_removed', $product, ['image_id' => $image->id]);

        return back()->with('success', 'Image removed.');
    }
}

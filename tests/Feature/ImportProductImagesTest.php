<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('attaches the bundled photographs, including extra variant images, without publishing', function () {
    $gel = Product::factory()->create(['slug' => 'aloe-vera-gel', 'name' => 'Aloe Vera Gel', 'status' => ProductStatus::Draft]);
    $c9 = Product::factory()->create(['slug' => 'c9', 'name' => 'C9', 'status' => ProductStatus::Draft]);

    $this->artisan('catalog:import-images')->assertSuccessful();

    $gelImages = $gel->images()->get();
    $c9Images = $c9->images()->get();

    expect($gelImages)->toHaveCount(1)
        ->and($c9Images)->toHaveCount(2)
        ->and($c9Images[1]->alt)->toBe('C9, Chocolate')
        ->and($gel->fresh()->status)->toBe(ProductStatus::Draft);

    // Square packshots are padded to the 4:5 product format.
    expect(round($gelImages[0]->height / $gelImages[0]->width, 2))->toBe(1.25);
    Storage::disk('public')->assertExists($gelImages[0]->path.'-480.webp');
});

it('keeps existing images unless asked to replace them', function () {
    $gel = Product::factory()->create(['slug' => 'aloe-vera-gel']);
    ProductImage::query()->create(['product_id' => $gel->id, 'path' => 'products/x/own', 'widths' => [480], 'width' => 800, 'height' => 1000, 'alt' => 'Own photo']);

    $this->artisan('catalog:import-images')->assertSuccessful();
    expect($gel->images()->pluck('alt')->all())->toBe(['Own photo']);

    $this->artisan('catalog:import-images', ['--replace' => true])->assertSuccessful();
    expect($gel->images()->pluck('alt')->all())->toBe([$gel->name]);
});

it('changes nothing in a dry run', function () {
    Product::factory()->create(['slug' => 'aloe-vera-gel']);

    $this->artisan('catalog:import-images', ['--dry-run' => true])->assertSuccessful();

    expect(ProductImage::query()->count())->toBe(0);
});

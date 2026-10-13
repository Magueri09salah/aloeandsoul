<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductSheet;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('public');
});

/** A draft product as CatalogSeeder creates it: a name, no text, price or reference. */
function emptyProduct(string $slug, array $variants = [null]): Product
{
    $product = Product::factory()->create(['slug' => $slug, 'summary' => null, 'description' => null, 'status' => ProductStatus::Draft]);
    foreach ($variants as $i => $name) {
        ProductVariant::factory()->create(['product_id' => $product->id, 'name' => $name, 'sku' => null, 'price_minor' => null, 'stock' => 0, 'sort_order' => $i]);
    }

    return $product;
}

it('fills empty products with the translated information, Moroccan price, reference and photos, without publishing', function () {
    config(['store.images.widths' => [480]]); // one rendition per photo keeps the test fast
    $gel = emptyProduct('aloe-vera-gel');
    $c9 = emptyProduct('c9', ['Vanilla', 'Chocolate']);
    ProductImage::query()->create(['product_id' => $c9->id, 'path' => 'products/x/own', 'widths' => [480], 'width' => 800, 'height' => 1000, 'alt' => 'Own photo']);

    $this->artisan('catalog:import')
        ->expectsOutputToContain('no price')
        ->assertSuccessful();

    $gel->refresh()->load('variants', 'images');
    expect($gel->status)->toBe(ProductStatus::Draft)
        ->and($gel->summary)->toContain('99.7% aloe vera')
        ->and($gel->benefits)->toContain('vitamin C')
        ->and($gel->usage)->toContain('40 ml diluted in 240 ml')
        ->and($gel->ingredients)->toContain('Stabilised aloe vera gel')
        ->and($gel->seo_title)->toStartWith('Forever Aloe Vera Gel')
        ->and($gel->specifications)->toBe([['label' => 'Size', 'value' => '1 litre']])
        ->and($gel->variants[0]->price_minor)->toBe(42500)
        ->and($gel->variants[0]->sku)->toBe('815')
        ->and($gel->images)->toHaveCount(5)
        ->and($gel->images[0]->alt)->toBe('Forever Aloe Vera Gel 1 litre bottle');

    // The first photo is padded to the 4:5 product tile; the others stay whole.
    expect(round($gel->images[0]->height / $gel->images[0]->width, 2))->toBe(1.25)
        ->and($gel->images[1]->height)->toBe($gel->images[1]->width);
    Storage::disk('public')->assertExists($gel->images[0]->path.'-480.webp');

    // C9: each flavour gets its own reference; it has no Moroccan price; its own photo is kept.
    $c9->refresh()->load('variants', 'images');
    expect($c9->variants->pluck('sku', 'name')->all())->toBe(['Vanilla' => '475', 'Chocolate' => '476'])
        ->and($c9->variants->pluck('price_minor')->filter()->all())->toBe([])
        ->and($c9->summary)->toContain('9-day programme')
        ->and($c9->images->pluck('alt')->all())->toBe(['Own photo']);
});

it('keeps what the owner already filled in unless asked to overwrite', function () {
    $lotion = emptyProduct('heat-lotion');
    $lotion->update(['description' => 'My own words.', 'specifications' => [['label' => 'Size', 'value' => '4 fl oz']]]);
    $lotion->variants[0]->update(['price_minor' => 19900, 'sale_price_minor' => 15000, 'sku' => 'MINE']);
    ProductImage::query()->create(['product_id' => $lotion->id, 'path' => 'products/x/own', 'widths' => [480], 'width' => 800, 'height' => 1000, 'alt' => 'Own photo']);

    $this->artisan('catalog:import')->assertSuccessful();

    $lotion->refresh()->load('variants', 'images');
    expect($lotion->description)->toBe('My own words.')
        ->and($lotion->summary)->toContain('warming massage lotion')
        ->and($lotion->specifications)->toBe([['label' => 'Size', 'value' => '4 fl oz']])
        ->and($lotion->variants[0]->only('price_minor', 'sale_price_minor', 'sku'))->toBe(['price_minor' => 19900, 'sale_price_minor' => 15000, 'sku' => 'MINE'])
        ->and($lotion->images->pluck('alt')->all())->toBe(['Own photo']);

    $this->artisan('catalog:import', ['--overwrite' => true, '--replace-images' => true])->assertSuccessful();

    $lotion->refresh()->load('variants', 'images');
    expect($lotion->description)->toStartWith('Aloe Heat Lotion is a massage cream')
        ->and($lotion->specifications)->toBe([['label' => 'Size', 'value' => '118 ml']])
        ->and($lotion->variants[0]->only('price_minor', 'sale_price_minor', 'sku'))->toBe(['price_minor' => 24200, 'sale_price_minor' => null, 'sku' => '64'])
        ->and($lotion->images->pluck('alt')->all())->toBe(['Aloe Heat Lotion tube']);
});

it('does not take a reference that another product already uses', function () {
    ProductVariant::factory()->create(['sku' => '64']);
    $lotion = emptyProduct('heat-lotion');

    $this->artisan('catalog:import')
        ->expectsOutputToContain('reference 64 is already used')
        ->assertSuccessful();

    expect($lotion->variants()->value('sku'))->toBeNull()
        ->and($lotion->variants()->value('price_minor'))->toBe(24200);
});

it('changes nothing in a dry run', function () {
    $lotion = emptyProduct('heat-lotion');

    $this->artisan('catalog:import', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect($lotion->fresh()->summary)->toBeNull()
        ->and($lotion->variants()->value('price_minor'))->toBeNull()
        ->and(ProductImage::query()->count())->toBe(0);
});

it('bundles valid information for every catalogue product', function () {
    $catalogue = collect(CatalogSeeder::CATALOG)
        ->flatMap(fn (array $category) => $category['products'])
        ->map(fn ($entry) => Str::slug(is_array($entry) ? $entry['name'] : $entry))
        ->sort()->values()->all();
    $folders = collect(glob(database_path('assets/products/*/details.md')))->map(fn ($f) => basename(dirname($f)))->sort()->values()->all();

    expect($folders)->toBe($catalogue);

    foreach ($folders as $slug) {
        $dir = database_path("assets/products/{$slug}");
        $sheet = ProductSheet::fromFile("{$dir}/details.md");

        // Same limits as the admin product form.
        expect(mb_strlen((string) $sheet->section('summary')))->toBeBetween(1, 255, $slug)
            ->and(mb_strlen((string) $sheet->section('seo title')))->toBeBetween(1, 70, $slug)
            ->and(mb_strlen((string) $sheet->section('seo description')))->toBeBetween(1, 170, $slug)
            ->and($sheet->meta('reference') ?? $sheet->variants())->not->toBeEmpty();

        $listed = array_column($sheet->images(), 'file');
        $files = array_map('basename', glob("{$dir}/*.webp"));
        sort($listed);
        expect($listed)->toBe($files, "{$slug}: every image is listed once with its alternative text");
    }
});

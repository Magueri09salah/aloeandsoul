<?php

use App\Enums\InventoryReason;
use App\Enums\ProductStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function productPayload(array $overrides = []): array
{
    return [
        'name' => 'Aloe Vera Gel',
        'slug' => '',
        'category_id' => Category::factory()->create()->id,
        'summary' => 'Aloe vera drink, 1 litre.',
        'description' => 'Official description.',
        'specifications' => [['label' => 'Volume', 'value' => '1 litre']],
        'option_label' => '',
        'is_featured' => true,
        'is_bestseller' => false,
        'sort_order' => 0,
        'seo_title' => '',
        'seo_description' => '',
        'variants' => [[
            'id' => null, 'name' => '', 'sku' => 'AVG-1L', 'price' => '289.50', 'sale_price' => '',
            'stock' => 12, 'track_stock' => true, 'low_stock_threshold' => '', 'is_active' => true,
        ]],
        ...$overrides,
    ];
}

it('creates a product as a draft with prices in minor units and a stock ledger entry', function () {
    $this->actingAs(manager())->post('/admin/products', productPayload())->assertSessionHasNoErrors();

    $product = Product::query()->with('variants')->sole();
    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->slug)->toBe('aloe-vera-gel')
        ->and($product->variants[0]->price_minor)->toBe(28950)
        ->and($product->variants[0]->stock)->toBe(12)
        ->and(InventoryMovement::query()->where('reason', InventoryReason::Restock)->value('change'))->toBe(12)
        ->and(AuditLog::query()->where('action', 'product.created')->exists())->toBeTrue();
});

it('validates prices, sale prices and SKUs', function () {
    sellableVariant(variant: ['sku' => 'TAKEN']);
    $user = manager();

    $this->actingAs($user)->post('/admin/products', productPayload(['variants' => [[
        'name' => '', 'sku' => 'TAKEN', 'price' => '12.345', 'sale_price' => '20', 'stock' => 1, 'track_stock' => true, 'is_active' => true,
    ]]]))->assertSessionHasErrors(['variants.0.price', 'variants.0.sale_price', 'variants.0.sku']);

    $this->actingAs($user)->post('/admin/products', productPayload(['variants' => [
        ['name' => 'Vanilla', 'sku' => 'DUP', 'price' => '100', 'stock' => 1],
        ['name' => 'Chocolate', 'sku' => 'dup', 'price' => '100', 'stock' => 1],
    ]]))->assertSessionHasErrors('variants.1.sku');

    $this->actingAs($user)->post('/admin/products', productPayload(['variants' => [
        ['name' => '', 'price' => '100', 'stock' => 1],
        ['name' => '', 'price' => '100', 'stock' => 1],
    ]]))->assertSessionHasErrors(['variants.0.name', 'variants.1.name']);
});

it('rejects duplicate web addresses', function () {
    Product::factory()->create(['slug' => 'aloe-vera-gel']);

    $this->actingAs(manager())->post('/admin/products', productPayload())->assertSessionHasErrors('slug');
});

it('blocks publishing until price, image and description exist', function () {
    $product = Product::factory()->create(['description' => null]);
    $product->variants()->create(['price_minor' => null, 'stock' => 0]);

    $this->actingAs(manager())->post("/admin/products/{$product->id}/status", ['action' => 'publish'])->assertSessionHasErrors('general');
    expect($product->fresh()->status)->toBe(ProductStatus::Draft);
});

it('publishes a complete product', function () {
    $variant = sellableVariant();
    $variant->product->update(['status' => ProductStatus::Draft]);

    $this->actingAs(manager())->post("/admin/products/{$variant->product_id}/status", ['action' => 'publish'])->assertSessionHasNoErrors();
    expect($variant->product->fresh()->status)->toBe(ProductStatus::Published);
});

it('will not save a live product into an unsellable state', function () {
    $variant = sellableVariant(24900);
    $payload = productPayload([
        'name' => $variant->product->name,
        'slug' => $variant->product->slug,
        'category_id' => $variant->product->category_id,
        'variants' => [['id' => $variant->id, 'name' => '', 'sku' => $variant->sku, 'price' => '', 'stock' => 10, 'track_stock' => true, 'is_active' => true]],
    ]);

    $this->actingAs(manager())->put("/admin/products/{$variant->product_id}", $payload)->assertSessionHasErrors('general');
    expect($variant->fresh()->price_minor)->toBe(24900);
});

it('records stock edits from the product form in the ledger', function () {
    $variant = sellableVariant(stock: 10);
    $payload = productPayload([
        'name' => $variant->product->name,
        'slug' => $variant->product->slug,
        'category_id' => $variant->product->category_id,
        'variants' => [['id' => $variant->id, 'name' => '', 'sku' => $variant->sku, 'price' => '250', 'stock' => 4, 'track_stock' => true, 'is_active' => true]],
    ]);

    $this->actingAs(manager())->put("/admin/products/{$variant->product_id}", $payload)->assertSessionHasNoErrors();

    expect($variant->fresh()->stock)->toBe(4)
        ->and(InventoryMovement::query()->where('product_variant_id', $variant->id)->value('change'))->toBe(-6);
});

it('refuses variant ids that belong to another product', function () {
    $mine = sellableVariant();
    $theirs = sellableVariant();
    $payload = productPayload([
        'name' => $mine->product->name, 'slug' => $mine->product->slug, 'category_id' => $mine->product->category_id,
        'variants' => [['id' => $theirs->id, 'name' => '', 'price' => '1', 'stock' => 0, 'track_stock' => true, 'is_active' => true]],
    ]);

    $this->actingAs(manager())->put("/admin/products/{$mine->product_id}", $payload)->assertSessionHasErrors('variants.0.id');
    expect($theirs->fresh()->price_minor)->toBe(25000);
});

it('archives instead of deleting products that were ordered', function () {
    $variant = sellableVariant();
    addToCart($variant, 1);
    $this->post('/checkout', checkoutPayload(zone()));

    $this->actingAs(manager())->delete("/admin/products/{$variant->product_id}")->assertSessionHas('error');
    expect(Product::query()->find($variant->product_id))->not->toBeNull();

    $this->actingAs(manager())->post("/admin/products/{$variant->product_id}/status", ['action' => 'archive']);
    expect($variant->product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('re-encodes uploaded images to WebP renditions and rejects non-images', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $user = manager();

    $this->actingAs($user)->post("/admin/products/{$product->id}/images", [
        'images' => [UploadedFile::fake()->image('photo.jpg', 1200, 1500)],
    ])->assertSessionHasNoErrors();

    $image = ProductImage::query()->sole();
    expect($image->widths)->toBe([480, 960])
        ->and($image->width)->toBe(1200);
    Storage::disk('public')->assertExists("{$image->path}-480.webp");
    Storage::disk('public')->assertExists("{$image->path}-960.webp");

    $this->actingAs($user)->post("/admin/products/{$product->id}/images", [
        'images' => [UploadedFile::fake()->createWithContent('shell.php.jpg', '<?php system($_GET["c"]); ?>')],
    ])->assertSessionHasErrors('images.0');

    $this->actingAs($user)->post("/admin/products/{$product->id}/images", [
        'images' => [UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')],
    ])->assertSessionHasErrors('images.0');

    expect(ProductImage::query()->count())->toBe(1);
});

it('renders product descriptions without raw HTML or script links', function () {
    $variant = sellableVariant(product: [
        'description' => "Safe **bold**\n\n<script>alert(1)</script>\n\n[click](javascript:alert(1)) <img src=x onerror=alert(1)>",
    ]);

    $html = inertiaProps($this->get('/products/'.$variant->product->slug))['product']['description_html'];

    expect($html)->toContain('<strong>bold</strong>')
        ->not->toContain('<script')
        ->not->toContain('javascript:')
        ->not->toContain('onerror');
});

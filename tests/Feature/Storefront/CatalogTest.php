<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the home page with brand metadata', function () {
    sellableVariant(product: ['is_featured' => true, 'name' => 'Aloe Vera Gel']);

    $this->get('/')
        ->assertOk()
        ->assertSee('<title data-inertia>Aloe &amp; Soul — Nourish the Body. Inspire the Soul.</title>', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertInertia(fn (Assert $page) => $page->component('home')->has('featured', 1)->has('journeys', 3));
});

it('lists only published products with a price', function () {
    $published = sellableVariant(product: ['name' => 'Visible Product']);
    Product::factory()->create(['name' => 'Draft Product', 'status' => ProductStatus::Draft]);
    $noPrice = Product::factory()->published()->create(['name' => 'No Price']);
    ProductVariant::factory()->create(['product_id' => $noPrice->id, 'price_minor' => null]);

    $this->get('/shop')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('shop/index')
            ->has('products.data', 1)
            ->where('products.data.0.name', $published->product->name));
});

it('shows each category page with its own metadata', function () {
    $variant = sellableVariant();
    $category = $variant->product->category;

    $this->get("/shop/{$category->slug}")
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url("/shop/{$category->slug}").'"', false)
        ->assertSee('<meta name="robots" content="index,follow"', false)
        ->assertInertia(fn (Assert $page) => $page->where('category.slug', $category->slug)->has('products.data', 1));
});

it('hides inactive categories', function () {
    $category = Category::factory()->create(['is_active' => false]);

    $this->get("/shop/{$category->slug}")->assertNotFound();
});

it('marks searched and filtered listings as noindex with a clean canonical', function () {
    $this->get('/shop?q=aloe&sort=price_asc')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow"', false)
        ->assertSee('<link rel="canonical" href="'.url('/shop').'"', false);
});

it('searches, filters and sorts products', function () {
    sellableVariant(10000, product: ['name' => 'Aloe Body Wash']);
    sellableVariant(30000, product: ['name' => 'Aloe Vera Gel']);
    sellableVariant(20000, stock: 0, product: ['name' => 'Bee Propolis']);

    $this->get('/shop?q=aloe&sort=price_desc')
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 2)
            ->where('products.data.0.name', 'Aloe Vera Gel')
            ->where('products.data.1.name', 'Aloe Body Wash'));

    $this->get('/shop?availability=in_stock')->assertInertia(fn (Assert $page) => $page->has('products.data', 2));
    $this->get('/shop?min_price=150&max_price=250')->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', 'Bee Propolis'));
});

it('ignores malformed filter values instead of failing', function () {
    sellableVariant();

    $this->get('/shop?sort=DROP%20TABLE&min_price=abc&page=-4&on_sale[]=x')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters.sort', 'featured')->has('products.data', 1));
});

it('shows a published product with structured data and no invented ratings', function () {
    $variant = sellableVariant(24950, product: ['name' => 'Aloe Berry']);

    $this->get('/products/'.$variant->product->slug)
        ->assertOk()
        ->assertSee('"@type":"Product"', false)
        ->assertSee('"price":"249.50"', false)
        ->assertSee('"priceCurrency":"MAD"', false)
        ->assertSee('"availability":"https://schema.org/InStock"', false)
        ->assertDontSee('aggregateRating', false)
        ->assertDontSee('"review"', false)
        ->assertInertia(fn (Assert $page) => $page->component('product/show')->where('product.variants.0.price_minor', 24950));
});

it('returns 404 for drafts and 410 for archived products', function () {
    $draft = Product::factory()->create(['status' => ProductStatus::Draft]);
    $archived = Product::factory()->archived()->create();

    $this->get("/products/{$draft->slug}")->assertNotFound();
    $this->get("/products/{$archived->slug}")->assertStatus(410)
        ->assertSee('noindex', false);
});

it('lists only public pages in the sitemap', function () {
    $published = sellableVariant();
    $draft = Product::factory()->create(['status' => ProductStatus::Draft]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(url('/products/'.$published->product->slug))
        ->assertDontSee($draft->slug)
        ->assertDontSee('/admin');
});

it('blocks all crawling outside production', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
});

it('redirects the old site URLs permanently', function () {
    $this->get('/my-wellness-journey')->assertStatus(301)->assertRedirect('/our-story#wellness');
    $this->get('/page')->assertStatus(301)->assertRedirect('/shop');
});

it('renders the founder story verbatim from settings', function () {
    $this->get('/our-story')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('story')
            ->where('story.chapters.0.key', 'wellness')
            ->where('story.chapters.0.paragraphs.2', 'I wasn’t looking for a business. I was simply looking for answers.'));
});

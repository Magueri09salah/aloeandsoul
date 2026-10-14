<?php

use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Inertia\Testing\AssertableInertia as Assert;

describe('French storefront', function () {
    it('serves every storefront page under /fr with the French language set', function () {
        $variant = sellableVariant(product: ['is_featured' => true]);
        $product = $variant->product;
        Page::query()->create(['slug' => 'terms', 'title' => 'Terms of sale', 'body' => 'Terms.', 'is_published' => true]);

        foreach (['/fr', '/fr/shop', "/fr/shop/{$product->category->slug}", "/fr/products/{$product->slug}", '/fr/cart', '/fr/our-story', '/fr/blog', '/fr/faq', '/fr/contact', '/fr/track-order', '/fr/terms'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<html lang="fr">', false)
                ->assertInertia(fn (Assert $page) => $page->where('store.locale', 'fr'));
        }

        $this->get('/shop')->assertSee('<html lang="en">', false)->assertInertia(fn (Assert $page) => $page->where('store.locale', 'en'));
    });

    it('links every indexable page to its other language for search engines', function () {
        $this->get('/fr/shop')
            ->assertSee('<link rel="canonical" href="'.url('/fr/shop').'"', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/shop').'">', false)
            ->assertSee('<link rel="alternate" hreflang="fr" href="'.url('/fr/shop').'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/shop').'">', false)
            ->assertSee('<meta property="og:locale" content="fr_FR">', false);
    });

    it('gives the language switcher the same page in each language, query included', function () {
        $this->get('/shop?q=aloe')->assertInertia(fn (Assert $page) => $page
            ->where('alternates.en', url('/shop?q=aloe'))
            ->where('alternates.fr', url('/fr/shop?q=aloe')));

        $this->get('/fr')->assertInertia(fn (Assert $page) => $page
            ->where('alternates.en', url('/'))
            ->where('alternates.fr', url('/fr')));
    });

    it('shows products in French and falls back to English for missing translations', function () {
        $variant = sellableVariant(product: [
            'name' => 'Toothpaste',
            'name_fr' => 'Dentifrice',
            'summary' => 'Fresh breath.',
            'description' => 'A gentle gel.',
            'description_fr' => 'Un gel doux.',
            'benefits' => 'Cleans well.',
            'specifications' => [['label' => 'Size', 'value' => '130 g', 'label_fr' => 'Contenance', 'value_fr' => '130 g']],
        ]);
        $variant->product->category->update(['name' => 'Hygiene', 'name_fr' => 'Hygiène']);

        $this->get("/fr/products/{$variant->product->slug}")
            ->assertOk()
            ->assertSee('<title data-inertia>Dentifrice — Aloe &amp; Soul</title>', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.name', 'Dentifrice')
                ->where('product.summary', 'Fresh breath.')
                ->where('product.category.name', 'Hygiène')
                ->where('product.sections.0.title', 'Description')
                ->where('product.sections.0.html', "<p>Un gel doux.</p>\n")
                ->where('product.sections.1.title', 'Bienfaits')
                ->where('product.sections.1.html', "<p>Cleans well.</p>\n")
                ->where('product.specifications.0', ['label' => 'Contenance', 'value' => '130 g'])
                ->where('product.size', '130 g')
                ->where('breadcrumbs.0.name', 'Accueil')
                ->where('breadcrumbs.1.url', url('/fr/shop')));

        $this->get("/products/{$variant->product->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.name', 'Toothpaste')
                ->where('product.specifications.0', ['label' => 'Size', 'value' => '130 g']));
    });

    it('uses the French versions of the editable site texts', function () {
        $this->get('/fr')->assertInertia(fn (Assert $page) => $page
            ->where('hero.heading', 'Nourrir le corps. Inspirer l’âme.')
            ->where('store.tagline', 'Nourrir le corps. Inspirer l’âme.'));

        settings()->update('home', ['hero_heading' => 'Welcome', 'hero_heading_fr' => '']);

        // An empty French text shows the English one.
        $this->get('/fr')->assertInertia(fn (Assert $page) => $page->where('hero.heading', 'Welcome'));
    });

    it('opens announcement links to the shop in the visitor’s language', function () {
        settings()->update('announcement', ['enabled' => true, 'text' => 'New in', 'text_fr' => 'Nouveau', 'link_label' => 'See', 'link_url' => '/shop/beauty']);

        $this->get('/fr/faq')->assertInertia(fn (Assert $page) => $page
            ->where('announcement.text', 'Nouveau')
            ->where('announcement.link_url', '/fr/shop/beauty'));
    });

    it('finds products by their French name', function () {
        sellableVariant(product: ['name' => 'Toothpaste', 'name_fr' => 'Dentifrice']);
        sellableVariant(product: ['name' => 'Shampoo', 'name_fr' => 'Shampooing']);

        $this->get('/fr/shop?q=dentifrice')->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Dentifrice'));
    });

    it('shows validation messages in French', function () {
        $this->post('/fr/contact', ['name' => 'Amina', 'email' => 'not-an-email', 'message' => 'Bonjour'])
            ->assertSessionHasErrors([
                'email' => 'Le champ adresse e-mail doit être une adresse e-mail valide.',
                'message' => 'Donnez-nous un peu plus de détails pour que nous puissions vous aider.',
            ]);
    });

    it('places an order in French, remembers the language and confirms in French', function () {
        $variant = sellableVariant(20000);
        $zone = zone(3000);
        $zone->update(['name' => 'Other cities', 'name_fr' => 'Autres villes']);
        addToCart($variant);

        $props = inertiaProps($this->get('/fr/checkout?zone='.$zone->id)->assertOk());
        expect($props['zones'][0]['name'])->toBe('Autres villes');

        $response = $this->post('/fr/checkout', [
            ...checkoutPayload($zone),
            'idempotency_key' => $props['idempotencyKey'],
        ]);

        $order = Order::query()->sole();
        expect($order->locale)->toBe('fr')
            ->and($order->shipping_zone_name)->toBe('Other cities');
        $response->assertRedirect(url("/fr/checkout/confirmation/{$order->reference}"));

        $this->get("/fr/checkout/confirmation/{$order->reference}")->assertInertia(fn (Assert $page) => $page
            ->where('order.status_label', 'En attente de confirmation')
            ->where('order.payment_method_label', 'Paiement à la livraison')
            ->where('order.delivery.zone', 'Autres villes'));
    });

    it('explains checkout errors in French', function () {
        $variant = sellableVariant();
        $zone = zone();
        addToCart($variant);

        $this->post('/fr/checkout', checkoutPayload($zone, ['name' => '', 'address' => '']))
            ->assertSessionHasErrors([
                'name' => 'Saisissez votre nom complet.',
                'address' => 'Saisissez votre adresse de livraison.',
            ]);
    });

    it('shows the not-found page in French', function () {
        $this->get('/fr/does-not-exist')
            ->assertNotFound()
            ->assertSee('<html lang="fr">', false)
            ->assertInertia(fn (Assert $page) => $page->component('error')->where('store.locale', 'fr'));
    });

    it('lists both languages in the sitemap', function () {
        $product = sellableVariant()->product;

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url("/fr/products/{$product->slug}").'</loc>', false)
            ->assertSee('<xhtml:link rel="alternate" hreflang="fr" href="'.url('/fr').'"/>', false)
            ->assertSee('<xhtml:link rel="alternate" hreflang="x-default" href="'.url("/products/{$product->slug}").'"/>', false);
    });
});

describe('French content in the admin', function () {
    it('saves the French product texts', function () {
        $product = sellableVariant()->product;
        $variant = $product->variants()->first();

        $this->actingAs(owner())
            ->put("/admin/products/{$product->id}", [
                'name' => $product->name,
                'slug' => $product->slug,
                'category_id' => $product->category_id,
                'name_fr' => 'Nom français',
                'description_fr' => 'Une description.',
                'seo_title_fr' => '',
                'specifications' => [['label' => 'Size', 'value' => '1 l', 'label_fr' => 'Contenance', 'value_fr' => '']],
                'variants' => [[
                    'id' => $variant->id, 'name' => '', 'name_fr' => '', 'sku' => 'T-1', 'price' => '250', 'sale_price' => '',
                    'stock' => $variant->stock, 'track_stock' => true, 'low_stock_threshold' => '', 'is_active' => true,
                ]],
            ])
            ->assertSessionHasNoErrors();

        $product->refresh();
        expect($product->name_fr)->toBe('Nom français')
            ->and($product->description_fr)->toBe('Une description.')
            ->and($product->seo_title_fr)->toBeNull()
            // An empty French value is not stored, so the row falls back to English.
            ->and($product->specifications)->toBe([['label' => 'Size', 'value' => '1 l', 'label_fr' => 'Contenance']]);
    });

    it('saves the French home page texts', function () {
        $this->actingAs(owner())
            ->put('/admin/content/home', [
                'announcement' => ['enabled' => true, 'text' => 'Hello', 'text_fr' => 'Bonjour', 'link_label' => '', 'link_url' => ''],
                'home' => [
                    'hero_heading' => 'Welcome', 'hero_heading_fr' => 'Bienvenue', 'hero_image_alt' => 'Photo',
                    'featured_heading' => 'Chosen', 'ritual_steps' => [],
                ],
            ])
            ->assertSessionHasNoErrors();

        expect(settings()->get('home.hero_heading_fr'))->toBe('Bienvenue')
            ->and(settings()->get('announcement.text_fr'))->toBe('Bonjour');
    });

    it('shows the language a customer ordered in', function () {
        $order = Order::factory()->create(['locale' => 'fr']);

        $this->actingAs(owner())
            ->get("/admin/orders/{$order->reference}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.customer.locale', 'fr'));
    });

    it('keeps the admin in English', function () {
        $this->actingAs(owner())->get('/admin')->assertSee('<html lang="en">', false);
    });
});

it('adds French names to an existing catalogue without overwriting edits', function () {
    $toothpaste = Product::factory()->create(['slug' => 'toothpaste', 'name' => 'Toothpaste']);
    $renamed = Product::factory()->create(['slug' => 'shampoo', 'name' => 'Daily Shampoo']);
    $translated = Product::factory()->create(['slug' => 'deodorant', 'name' => 'Deodorant', 'name_fr' => 'Mon déodorant']);

    $this->seed(CatalogSeeder::class);

    expect($toothpaste->fresh()->name_fr)->toBe('Dentifrice')
        // English name changed in the admin: the stock translation would no longer match it.
        ->and($renamed->fresh()->name_fr)->toBeNull()
        ->and($translated->fresh()->name_fr)->toBe('Mon déodorant')
        ->and(Product::query()->where('slug', 'c9')->first()?->variants->pluck('name_fr')->all())->toBe(['Vanille', 'Chocolat']);
});

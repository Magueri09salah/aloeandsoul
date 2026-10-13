<?php

use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Page;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;

/*
| Every admin screen must open without errors when the shop has real data
| (strict mode turns lazy loading and missing attributes into exceptions).
*/

beforeEach(function () {
    $this->seed([PageSeeder::class, FaqSeeder::class]);

    $variant = sellableVariant(product: ['is_featured' => true]);
    addToCart($variant, 1);
    $this->post('/checkout', checkoutPayload(zone()))->assertRedirect();
    $this->flushSession();

    Coupon::factory()->create();
    ContactMessage::query()->create(['name' => 'Amina', 'email' => 'a@example.com', 'message' => 'Hello there, a question']);
    NewsletterSubscriber::query()->create(['email' => 'reader@example.com', 'consented_at' => now(), 'consent_text' => 'I agree']);

    settings()->update('home', ['ritual_enabled' => true, 'ritual_steps' => [['product_id' => $variant->product_id, 'note' => 'Morning']]]);

    $this->variant = $variant;
    $this->actingAs(owner());
});

it('opens every admin page', function () {
    $order = Order::query()->sole();
    $pages = [
        '/admin',
        '/admin?range=today',
        '/admin?range=90d',
        '/admin/profile',
        '/admin/orders',
        '/admin/orders?status=to_fulfil&flagged=1&q=Fatima',
        "/admin/orders/{$order->reference}",
        "/admin/orders/{$order->reference}/packing-slip",
        '/admin/products',
        '/admin/products?status=draft&sort=stock',
        '/admin/products/create',
        "/admin/products/{$this->variant->product_id}/edit",
        '/admin/categories',
        '/admin/inventory',
        '/admin/inventory?level=low',
        "/admin/inventory/{$this->variant->id}/history",
        '/admin/customers',
        '/admin/customers/'.Customer::query()->value('id'),
        '/admin/coupons',
        '/admin/delivery-zones',
        '/admin/content/home',
        '/admin/content/story',
        '/admin/pages',
        '/admin/pages/'.Page::query()->value('id').'/edit',
        '/admin/faqs',
        '/admin/messages',
        '/admin/messages?unread=1',
        '/admin/subscribers',
        '/admin/settings',
        '/admin/users',
        '/admin/audit-log',
    ];

    foreach ($pages as $url) {
        $response = $this->get($url);
        expect($response->status())->toBe(200, "{$url} returned {$response->status()}");
    }
});

it('opens every storefront page with the same data', function () {
    $product = $this->variant->product;

    foreach (['/', '/shop', "/shop/{$product->category->slug}", "/products/{$product->slug}", '/cart', '/our-story', '/faq', '/contact', '/track-order', '/privacy-policy', '/terms', '/legal-notice', '/shipping-returns'] as $url) {
        $response = $this->get($url);
        expect($response->status())->toBe(200, "{$url} returned {$response->status()}");
    }
});

it('saves the home page content with a routine section', function () {
    $this->put('/admin/content/home', [
        'announcement' => ['enabled' => true, 'text' => 'Free delivery in Marrakech', 'link_label' => 'Shop', 'link_url' => '/shop'],
        'home' => [
            'hero_heading' => 'Nourish the body.',
            'hero_text' => 'Quote',
            'hero_attribution' => 'Founder',
            'hero_image_alt' => 'Founder in the dunes',
            'intro_heading' => 'Intro',
            'intro_text' => 'Text',
            'featured_heading' => 'Chosen by the founder',
            'ritual_enabled' => true,
            'ritual_heading' => 'Morning routine',
            'ritual_intro' => '',
            'ritual_steps' => [['product_id' => $this->variant->product_id, 'note' => 'First']],
            'newsletter_enabled' => true,
        ],
    ])->assertSessionHasNoErrors();

    expect(settings()->get('announcement.text'))->toBe('Free delivery in Marrakech');
    $this->get('/')->assertOk()->assertSee('Morning routine');
});

it('keeps FAQs and pages editable', function () {
    $faq = Faq::query()->first();
    $page = Page::query()->first();

    $this->put("/admin/faqs/{$faq->id}", ['question' => 'Updated?', 'answer' => 'Yes.', 'is_published' => true])->assertSessionHasNoErrors();
    $this->put("/admin/pages/{$page->id}", ['title' => $page->title, 'body' => 'New body', 'is_published' => true, 'needs_review' => false])->assertSessionHasNoErrors();

    expect($faq->fresh()->question)->toBe('Updated?')
        ->and($page->fresh()->needs_review)->toBeFalse();
});

<?php

use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

it('sends baseline security headers', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Request-Id');
});

it('sends a content security policy outside local development', function () {
    $this->app['env'] = 'production';

    $csp = $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->not->toContain('unsafe-eval');
});

it('keeps checkout, cart and tracking out of caches and search engines', function () {
    $this->get('/cart')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/track-order')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('protects every web route with CSRF verification', function () {
    // Laravel skips the check itself while running tests, so assert it is wired in.
    $web = app(Kernel::class)->getMiddlewareGroups()['web'];

    expect($web)->toContain(ValidateCsrfToken::class);
});

it('accepts contact messages and drops bot submissions', function () {
    $this->post('/contact', ['name' => 'Amina', 'email' => 'amina@example.com', 'message' => 'Do you deliver to Agadir?'])->assertSessionHasNoErrors();
    $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy cheap things now', 'website' => 'http://spam'])->assertSessionHasErrors('website');

    expect(ContactMessage::query()->count())->toBe(1)
        ->and(ContactMessage::query()->value('ip_hash'))->toHaveLength(64);
});

it('rate-limits public forms', function () {
    foreach (range(1, 3) as $i) {
        $this->post('/contact', ['name' => 'Amina', 'email' => 'a@example.com', 'message' => "Message number {$i} here"]);
    }

    $this->post('/contact', ['name' => 'Amina', 'email' => 'a@example.com', 'message' => 'One more message'])->assertStatus(429);
});

it('requires explicit consent for the newsletter and does not reveal existing subscribers', function () {
    $this->post('/newsletter', ['email' => 'reader@example.com'])->assertSessionHasErrors('consent');

    $first = $this->post('/newsletter', ['email' => 'reader@example.com', 'consent' => '1']);
    $again = $this->post('/newsletter', ['email' => 'READER@example.com', 'consent' => '1']);

    expect(NewsletterSubscriber::query()->count())->toBe(1)
        ->and(NewsletterSubscriber::query()->value('consent_text'))->toContain('unsubscribe');
    $first->assertSessionHas('success', 'Thank you for subscribing.');
    $again->assertSessionHas('success', 'Thank you for subscribing.');
});

it('does not store raw IP addresses with orders', function () {
    addToCart(sellableVariant(), 1);
    $this->post('/checkout', checkoutPayload(zone()));

    $order = Order::query()->sole();
    expect($order->ip_hash)->toHaveLength(64)->not->toBe('127.0.0.1');
});

it('rejects javascript links in the announcement bar', function () {
    $this->actingAs(owner())->put('/admin/content/home', [
        'announcement' => ['enabled' => true, 'text' => 'Hi', 'link_label' => 'Go', 'link_url' => 'javascript:alert(1)'],
        'home' => ['hero_heading' => 'H', 'hero_image_alt' => 'A', 'featured_heading' => 'F'],
    ])->assertSessionHasErrors('announcement.link_url');
});

it('shows a friendly 404 inside the storefront layout', function () {
    $this->get('/definitely-not-here')->assertNotFound()->assertSee('"component":"error"', false);
});

<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\ContentController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SeoController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\TrackOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/shop', [ShopController::class, 'index'])->middleware('throttle:search')->name('shop');
Route::get('/shop/{category:slug}', [ShopController::class, 'category'])->middleware('throttle:search')->name('shop.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart');
Route::middleware('throttle:cart')->group(function () {
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
});

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/checkout/confirmation/{order:reference}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::get('/track-order', [TrackOrderController::class, 'show'])->name('track-order');
Route::post('/track-order', [TrackOrderController::class, 'lookup'])->middleware('throttle:track-order')->name('track-order.lookup');

Route::get('/our-story', [ContentController::class, 'story'])->name('story');
Route::get('/faq', [ContentController::class, 'faq'])->name('faq');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');
Route::post('/newsletter', [ContactController::class, 'subscribe'])->middleware('throttle:forms')->name('newsletter.subscribe');

Route::get('/{slug}', [ContentController::class, 'page'])
    ->whereIn('slug', ['shipping-returns', 'privacy-policy', 'terms', 'legal-notice'])
    ->name('page');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Permanent redirects from the previous (Zyro) site.
Route::permanentRedirect('/my-wellness-journey', '/our-story#wellness');
Route::permanentRedirect('/busnises-journey', '/our-story#business');
Route::permanentRedirect('/my-freedom-journey', '/our-story#freedom');
Route::permanentRedirect('/blog-list', '/our-story');
Route::permanentRedirect('/page', '/shop');

/*
|--------------------------------------------------------------------------
| Admin (login, logout, password reset and 2FA routes come from Fortify)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin.active', 'inertia.encrypt'])
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

        Route::get('/profile', [Admin\ProfileController::class, 'show'])->name('profile');

        // Orders
        Route::middleware('can:orders.view')->group(function () {
            Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
            Route::get('/orders/{order}/packing-slip', [Admin\OrderController::class, 'packingSlip'])->name('orders.packing-slip');
        });
        Route::middleware('can:orders.manage')->group(function () {
            Route::post('/orders/{order}/transitions', [Admin\OrderController::class, 'transition'])->name('orders.transition');
            Route::post('/orders/{order}/payment', [Admin\OrderController::class, 'payment'])->name('orders.payment');
            Route::post('/orders/{order}/notes', [Admin\OrderController::class, 'note'])->name('orders.note');
            Route::put('/orders/{order}/delivery', [Admin\OrderController::class, 'delivery'])->name('orders.delivery');
        });

        // Catalogue
        Route::middleware('can:catalog.view')->group(function () {
            Route::get('/products', [Admin\ProductController::class, 'index'])->name('products.index');
            Route::get('/inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
            Route::get('/inventory/{variant}/history', [Admin\InventoryController::class, 'history'])->name('inventory.history');
        });
        Route::middleware('can:catalog.manage')->group(function () {
            Route::get('/products/create', [Admin\ProductController::class, 'create'])->name('products.create');
            Route::post('/products', [Admin\ProductController::class, 'store'])->name('products.store');
            Route::get('/products/{product}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
            Route::put('/products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
            Route::post('/products/{product}/status', [Admin\ProductController::class, 'status'])->name('products.status');
            Route::delete('/products/{product}', [Admin\ProductController::class, 'destroy'])->name('products.destroy');

            Route::post('/products/{product}/images', [Admin\ProductImageController::class, 'store'])->name('products.images.store');
            Route::put('/products/{product}/images', [Admin\ProductImageController::class, 'update'])->name('products.images.update');
            Route::delete('/products/{product}/images/{image}', [Admin\ProductImageController::class, 'destroy'])->scopeBindings()->name('products.images.destroy');

            Route::put('/inventory/{variant}', [Admin\InventoryController::class, 'update'])->name('inventory.update');

            Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
        });

        // Customers
        Route::middleware('can:customers.view')->group(function () {
            Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show'])->name('customers.show');
        });
        Route::post('/customers/{customer}/anonymize', [Admin\CustomerController::class, 'anonymize'])
            ->middleware('can:customers.anonymize')->name('customers.anonymize');

        // Marketing: discount codes and delivery zones
        Route::middleware('can:marketing.manage')->group(function () {
            Route::get('/coupons', [Admin\CouponController::class, 'index'])->name('coupons.index');
            Route::post('/coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
            Route::put('/coupons/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
            Route::delete('/coupons/{coupon}', [Admin\CouponController::class, 'destroy'])->name('coupons.destroy');

            Route::get('/delivery-zones', [Admin\ShippingZoneController::class, 'index'])->name('zones.index');
            Route::post('/delivery-zones', [Admin\ShippingZoneController::class, 'store'])->name('zones.store');
            Route::put('/delivery-zones/{zone}', [Admin\ShippingZoneController::class, 'update'])->name('zones.update');
            Route::delete('/delivery-zones/{zone}', [Admin\ShippingZoneController::class, 'destroy'])->name('zones.destroy');
        });

        // Content
        Route::middleware('can:content.manage')->group(function () {
            Route::get('/content/home', [Admin\ContentController::class, 'editHome'])->name('content.home');
            Route::put('/content/home', [Admin\ContentController::class, 'updateHome'])->name('content.home.update');
            Route::get('/content/story', [Admin\ContentController::class, 'editStory'])->name('content.story');
            Route::put('/content/story', [Admin\ContentController::class, 'updateStory'])->name('content.story.update');
            Route::post('/content/images/{slot}', [Admin\ContentController::class, 'uploadImage'])
                ->whereIn('slot', ['hero', 'story'])->name('content.images.store');
            Route::delete('/content/images/{slot}', [Admin\ContentController::class, 'removeImage'])
                ->whereIn('slot', ['hero', 'story'])->name('content.images.destroy');

            Route::get('/pages', [Admin\PageController::class, 'index'])->name('pages.index');
            Route::get('/pages/{page}/edit', [Admin\PageController::class, 'edit'])->name('pages.edit');
            Route::put('/pages/{page}', [Admin\PageController::class, 'update'])->name('pages.update');

            Route::get('/faqs', [Admin\FaqController::class, 'index'])->name('faqs.index');
            Route::post('/faqs', [Admin\FaqController::class, 'store'])->name('faqs.store');
            Route::put('/faqs/{faq}', [Admin\FaqController::class, 'update'])->name('faqs.update');
            Route::delete('/faqs/{faq}', [Admin\FaqController::class, 'destroy'])->name('faqs.destroy');

            Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
            Route::post('/messages/{message}/read', [Admin\MessageController::class, 'markRead'])->name('messages.read');
            Route::delete('/messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
            Route::get('/subscribers', [Admin\MessageController::class, 'subscribers'])->name('subscribers.index');
            Route::get('/subscribers/export', [Admin\MessageController::class, 'exportSubscribers'])->name('subscribers.export');
        });

        // Owner only
        Route::middleware('can:settings.manage')->group(function () {
            Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings/{group}', [Admin\SettingsController::class, 'update'])
                ->whereIn('group', ['store', 'checkout', 'contact', 'social', 'seo'])->name('settings.update');
        });
        Route::middleware('can:users.manage')->group(function () {
            Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
            Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        });
        Route::get('/audit-log', Admin\AuditLogController::class)->middleware('can:audit.view')->name('audit.index');
    });

// Unknown URLs run through the web middleware so the 404 page has the
// storefront layout (navigation, cart).
Route::fallback(fn () => abort(404));

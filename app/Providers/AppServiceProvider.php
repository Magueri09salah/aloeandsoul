<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Http\Controllers\Storefront\SeoController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Cart\CartService;
use App\Services\Settings\Settings;
use App\Support\Locales;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Per-request state (resolved settings, current cart).
        $this->app->scoped(Settings::class);
        $this->app->scoped(CartService::class);
    }

    public function boot(): void
    {
        // Catch lazy loading (N+1), silently discarded attributes and typos
        // outside production.
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers()->uncompromised(3));

        $this->registerGates();
        $this->registerRateLimiters();
        $this->registerAuthLogging();
        $this->registerCacheInvalidation();
    }

    /** Navigation and sitemap caches are rebuilt as soon as the catalogue changes. */
    private function registerCacheInvalidation(): void
    {
        $flush = function () {
            foreach (Locales::codes() as $locale) {
                Cache::forget(HandleInertiaRequests::NAV_CACHE_KEY.'.'.$locale);
            }
            Cache::forget(SeoController::SITEMAP_CACHE_KEY);
        };

        foreach ([Category::class, Product::class, Page::class, BlogPost::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }

    private function registerGates(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }
    }

    /**
     * Limits are stored in the configured cache store (database on shared
     * hosting), so they persist across requests and PHP workers.
     */
    private function registerRateLimiters(): void
    {
        $ip = fn (Request $request) => (string) $request->ip();
        // Inertia (XHR) submissions go back to the form with a visible message;
        // anything else (bots, scripts) gets a plain 429 with Retry-After.
        // Messages are translated when the limit is hit (the visitor's language).
        $tooMany = fn (string $message) => function (Request $request, array $headers) use ($message) {
            $message = __($message);

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message)->withHeaders($headers);
            }

            return response($message, 429, $headers);
        };

        // Placing orders: generous for real customers, stops scripted floods.
        RateLimiter::for('checkout', fn (Request $request) => [
            Limit::perMinute(6)->by('checkout-min|'.$ip($request))
                ->response($tooMany('Too many attempts. Please wait a minute and try again.')),
            Limit::perHour(30)->by('checkout-hour|'.$ip($request))
                ->response($tooMany('Too many orders from this connection. Please contact us on WhatsApp.')),
        ]);

        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(60)->by('cart|'.$ip($request))
            ->response($tooMany('You are doing that a bit too fast. Please try again in a moment.')));

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by('search|'.$ip($request)));

        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(3)->by('forms-min|'.$ip($request))
                ->response($tooMany('Please wait a moment before sending again.')),
            Limit::perDay(20)->by('forms-day|'.$ip($request))
                ->response($tooMany('Please wait a moment before sending again.')),
        ]);

        RateLimiter::for('track-order', fn (Request $request) => Limit::perMinutes(10, 10)->by('track|'.$ip($request))
            ->response($tooMany('Too many lookups. Please try again in a few minutes.')));

        // Fortify uses these names for its own routes.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login|'.mb_strtolower((string) $request->input('email')).'|'.$ip($request)),
            Limit::perMinute(20)->by('login-ip|'.$ip($request)),
        ]);
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by('2fa|'.$request->session()->get('login.id').'|'.$ip($request)));
        // Password reset requests: see ThrottlePasswordResetRequests middleware.
    }

    private function registerAuthLogging(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                Log::info('auth.login', ['user_id' => $event->user->id]);
                app(AuditLogger::class)->log('auth.login', $event->user, [], $event->user->id);
            }
        });

        Event::listen(Logout::class, fn (Logout $event) => Log::info('auth.logout', ['user_id' => $event->user?->getAuthIdentifier()]));

        // Never log the password or the full email address.
        Event::listen(Failed::class, function (Failed $event) {
            $email = (string) ($event->credentials['email'] ?? '');
            Log::warning('auth.failed', ['email_hash' => substr(hash('sha256', mb_strtolower($email)), 0, 16), 'user_id' => $event->user?->getAuthIdentifier()]);
        });

        Event::listen(Lockout::class, fn (Lockout $event) => Log::warning('auth.lockout', ['ip' => $event->request->ip()]));
    }
}

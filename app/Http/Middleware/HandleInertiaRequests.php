<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Locales;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public const NAV_CACHE_KEY = 'nav.categories';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $isAdmin = $request->is('admin', 'admin/*');
        $store = settings()->localized('store');
        $contact = settings()->localized('contact');

        $shared = [
            ...parent::share($request),
            'store' => [
                'name' => $store['name'],
                'tagline' => $store['tagline'],
                'currency' => $store['currency'],
                'country' => $store['country'],
                'locale' => app()->getLocale(),
                'timezone' => (string) config('store.display_timezone'),
                'ordering_enabled' => (bool) $store['ordering_enabled'],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];

        if ($isAdmin) {
            /** @var User|null $user */
            $user = $request->user();
            $shared['auth'] = [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'permissions' => $user->permissionNames(),
                    'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                ] : null,
            ];

            return $shared;
        }

        return [
            ...$shared,
            'contact' => [
                'email' => $contact['email'],
                'phone' => $contact['phone'],
                'phone_display' => $contact['phone'] ? Phone::display($contact['phone'], $store['country']) : null,
                'whatsapp' => $contact['whatsapp'] ? Phone::whatsappDigits($contact['whatsapp']) : null,
                'locations' => $contact['locations'],
                'hours' => $contact['hours'],
            ],
            'social' => array_filter(settings()->group('social')),
            'newsletter_enabled' => (bool) settings()->get('home.newsletter_enabled'),
            'announcement' => fn () => $this->announcement(),
            'alternates' => $this->alternates($request),
            'navigation' => fn () => Cache::remember(self::NAV_CACHE_KEY.'.'.app()->getLocale(), now()->addMinutes(10), fn () => Category::query()
                ->active()
                ->get()
                ->map(fn (Category $c) => [
                    'name' => $c->translated('name'),
                    'slug' => $c->slug,
                    'tagline' => $c->translated('tagline'),
                    'tint' => $c->tint,
                    'motif' => $c->motif,
                ])
                ->all()),
            'cart' => fn () => app(CartService::class)->view(),
        ];
    }

    /** @return array{text: string, link_label: string, link_url: string}|null */
    private function announcement(): ?array
    {
        $a = settings()->localized('announcement');
        $url = (string) $a['link_url'];
        // Links to our own pages open in the visitor's language.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            $url = Locales::path($url, app()->getLocale());
        }

        return $a['enabled'] && filled($a['text'])
            ? ['text' => $a['text'], 'link_label' => $a['link_label'], 'link_url' => $url]
            : null;
    }

    /**
     * This page in every language, for the language switcher.
     *
     * @return array<string, string>
     */
    private function alternates(Request $request): array
    {
        $query = $request->getQueryString();
        $path = '/'.$request->path().($query ? "?{$query}" : '');

        $urls = [];
        foreach (Locales::codes() as $locale) {
            $urls[$locale] = url(Locales::path($path, $locale));
        }

        return $urls;
    }
}

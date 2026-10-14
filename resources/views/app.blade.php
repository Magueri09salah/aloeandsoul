@php
    /** @var \App\Services\Seo\Seo|null $seo */
    $seo ??= \App\Services\Seo\Seo::page((string) settings()->localizedGet('seo.title'), null, index: ! request()->is('admin', 'admin/*'));
    $isAdmin = request()->is('admin', 'admin/*');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FBF9F4">

    {{-- Server-rendered so crawlers and link previews never depend on JavaScript.
         data-inertia lets React take these over on client-side navigation. --}}
    <title data-inertia>{{ $seo->fullTitle() }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($seo->description, 160, '…') }}" data-inertia="description">
    <meta name="robots" content="{{ $isAdmin ? 'noindex,nofollow' : $seo->robots() }}" data-inertia="robots">
    @if ($seo->canonical && ! $isAdmin)
        <link rel="canonical" href="{{ $seo->canonical }}" data-inertia="canonical">
        @if ($seo->index)
            {{-- The same page in each language; x-default is the English one. --}}
            @foreach (\App\Support\Locales::codes() as $code)
                <link rel="alternate" hreflang="{{ $code }}" href="{{ \App\Support\Locales::url($seo->canonical, $code) }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ \App\Support\Locales::url($seo->canonical, \App\Support\Locales::DEFAULT) }}">
        @endif
    @endif

    @unless ($isAdmin)
        <meta property="og:site_name" content="{{ settings()->get('store.name') }}">
        <meta property="og:locale" content="{{ \App\Support\Locales::REGIONS[app()->getLocale()] }}">
        @foreach (\App\Support\Locales::codes() as $code)
            @if ($code !== app()->getLocale())
                <meta property="og:locale:alternate" content="{{ \App\Support\Locales::REGIONS[$code] }}">
            @endif
        @endforeach
        <meta property="og:type" content="{{ $seo->type }}">
        <meta property="og:title" content="{{ $seo->fullTitle() }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seo->description, 200, '…') }}">
        <meta property="og:url" content="{{ $seo->canonical ?? url()->current() }}">
        <meta property="og:image" content="{{ $seo->imageUrl() }}">
        <meta name="twitter:card" content="summary_large_image">
        @foreach ($seo->jsonLd as $schema)
            <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endforeach
    @endunless

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('brand/icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="{{ $isAdmin ? 'admin' : 'storefront' }}">
    @inertia
    <noscript>
        <p style="padding:16px;font-family:system-ui,sans-serif">
            {{ __(':store needs JavaScript to show this page. Please enable it, or contact us at :email.', ['store' => settings()->get('store.name'), 'email' => settings()->get('contact.email')]) }}
        </p>
    </noscript>
</body>
</html>

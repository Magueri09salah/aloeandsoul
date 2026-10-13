<?php

namespace App\Http\Controllers;

use App\Services\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

abstract class Controller
{
    /**
     * Render an Inertia page. SEO metadata goes to Blade (initial HTML for
     * crawlers) and to React (title/description on client navigation).
     *
     * @param  array<string, mixed>  $props
     */
    protected function render(string $component, array $props, Seo $seo): Response
    {
        return Inertia::render($component, [...$props, 'seo' => $seo->toProps()])
            ->withViewData(['seo' => $seo]);
    }
}

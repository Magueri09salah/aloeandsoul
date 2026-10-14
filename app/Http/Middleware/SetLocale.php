<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The language comes from the URL alone (/fr/… is French, everything else
 * English), so every page has one stable address per language for search
 * engines and shared links. The admin is always English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(Locales::fromPath($request->path()));

        return $next($request);
    }
}

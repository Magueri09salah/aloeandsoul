<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify registers its password reset routes itself, so the named
 * `password-reset` limiter is applied here for those POST requests.
 */
class ThrottlePasswordResetRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! $request->is('admin/forgot-password', 'admin/reset-password')) {
            return $next($request);
        }

        $key = 'password-reset|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withErrors(['email' => "Too many attempts. Please try again in {$seconds} seconds."])
                ->withHeaders(['Retry-After' => (string) $seconds]);
        }

        RateLimiter::hit($key, 15 * 60);

        return $next($request);
    }
}

<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAdminIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottlePasswordResetRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);

        $middleware->web(append: [
            ThrottlePasswordResetRequests::class,
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);

        // Only trust forwarding headers from proxies listed in TRUSTED_PROXIES
        // (e.g. a CDN in front of Hostinger); otherwise use the socket IP.
        $proxies = env('TRUSTED_PROXIES');
        if ($proxies) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        $middleware->alias([
            'admin.active' => EnsureAdminIsActive::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business-rule violations surface as form errors, never as 500s.
        $exceptions->render(fn (DomainException $e) => throw $e->toValidationException());

        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation']);

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with('error', 'This page expired. Please try again.');
            }

            $friendly = [403, 404, 410, 429];
            if (! config('app.debug')) {
                $friendly = [...$friendly, 500, 503];
            }

            if (in_array($status, $friendly, true) && ($request->header('X-Inertia') || ! $request->expectsJson())) {
                return Inertia::render('error', [
                    'status' => $status,
                    'request_id' => $request->attributes->get('request_id'),
                ])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();

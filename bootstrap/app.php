<?php

use App\Http\Middleware\CaptureOidcAuthorizeParameters;
use App\Http\Middleware\EnsureUserCanAccessClient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Both of these read the session, so they have to run after the web
        // group has started one. They no-op on every route but Passport's
        // authorization endpoints.
        $middleware->appendToGroup('web', [
            CaptureOidcAuthorizeParameters::class,
            EnsureUserCanAccessClient::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            // The userinfo endpoint is machine-to-machine whatever the caller
            // sends in Accept. Redirecting it to the login page would hand a
            // relying party an HTML page where it expects a 401.
            fn (Request $request) => $request->is('api/*')
                || $request->routeIs('oidc.userinfo')
                || $request->expectsJson(),
        );
    })->create();

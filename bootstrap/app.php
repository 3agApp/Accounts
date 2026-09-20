<?php

use App\Http\Middleware\EnsureEmailIsVerifiedForOAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Passport applies config('passport.middleware') as group middleware,
        // which lands it ahead of the `web` group and so ahead of the session
        // the check needs. Priority is what decides the order once the groups
        // are expanded, so name it here and it runs once a user can be read.
        $middleware->appendToPriorityList(
            StartSession::class,
            EnsureEmailIsVerifiedForOAuth::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

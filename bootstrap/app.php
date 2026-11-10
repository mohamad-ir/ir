<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\MarkOfflineCacheable;
use App\Http\Middleware\SecurityHeaders;
use App\Services\Seo\NotFoundHandler;
use App\Support\ColorMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [EnsureInstalled::class], append: [SecurityHeaders::class, MarkOfflineCacheable::class]);
        // Trusted proxies: config/trustedproxy.php (TRUSTED_PROXIES in .env).
        // Written by the Light/Dark/System switcher in the browser, so it cannot be encrypted.
        $middleware->encryptCookies(except: [ColorMode::COOKIE]);
        $middleware->preventRequestForgery(except: ['_a', '_e', '_p', 'newsletter/open/*', 'newsletter/unsubscribe/*', 'stripe/webhook', 'banner-click/*']);
        $middleware->alias([
            'staff' => EnsureStaff::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return app(NotFoundHandler::class)->handle($request);
        });
    })->create();

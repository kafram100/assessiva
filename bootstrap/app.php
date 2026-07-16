<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '*/login',
            '*/Sub-Agent/login',
            '*/logout',
            'login',
            'Sub-Agent/login',
            'logout',
            'admin/toggle-role/*',
            'admin/toggle-status/*',
            'admin/credit-debit*',
            'admin/user-delete*',
            'admin/user-update*',
            'wallet/topup/moolre/callback',
            'store/payment/callback',
        ]);

        $middleware->alias([
            'agent' => \App\Http\Middleware\EnsureAgentRole::class,
            'dealer' => \App\Http\Middleware\EnsureDealerRole::class,
            'sub_agent' => \App\Http\Middleware\EnsureSubAgentRole::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin.role' => \App\Http\Middleware\RedirectNonAdmin::class,
            'api.active' => \App\Http\Middleware\EnsureApiIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

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

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'admin_or_hod' => \App\Http\Middleware\EnsureAdminOrHod::class,
            'approved_staff' => \App\Http\Middleware\EnsureApprovedUser::class,
            'hod' => \App\Http\Middleware\EnsureHod::class,
            'lecturer_area' => \App\Http\Middleware\EnsureLecturerArea::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'student' => \App\Http\Middleware\EnsureStudentSession::class,
            'student_result_verified' => \App\Http\Middleware\EnsureStudentResultVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

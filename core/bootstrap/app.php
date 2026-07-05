<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
        then: fn () => Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group(base_path('routes/api/v1.php'))
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB |
            Request::HEADER_X_FORWARDED_TRAEFIK
        )->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule) {
        // Recurring maintenance so the operator-configured cron has work to do
        // and background tables do not grow without bound.
        $schedule->command('queue:prune-batches')->daily();
        $schedule->command('queue:prune-failed')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

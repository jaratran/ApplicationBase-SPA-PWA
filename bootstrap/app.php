<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // 👈 añadimos ruta api explícita
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // === Middleware para grupo "api" ===
        // === Middleware globales para Sanctum y sesión ===
        $middleware->appendToGroup('api', [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // === Alias opcionales ===
        // Estos se activarán más adelante cuando migremos desde EcoRuta
        /*
        $middleware->alias([
            'signed_custom' => \App\Http\Middleware\CustomValidateSignature::class,
            'check.role'    => \App\Http\Middleware\CheckRoleAccess::class,
        ]);
        */
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
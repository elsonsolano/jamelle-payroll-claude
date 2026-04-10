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
    ->withMiddleware(function (Middleware $middleware): void {
        if (getenv('APP_ENV') === 'production') {
            $middleware->trustProxies(at: '*');
        }
        $middleware->alias([
            'admin'            => \App\Http\Middleware\RequireAdmin::class,
            'super-admin'      => \App\Http\Middleware\RequireSuperAdmin::class,
            'staff'            => \App\Http\Middleware\RequireStaff::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'signature.set'    => \App\Http\Middleware\EnsureSignatureSet::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsurePasswordChanged::class,
            \App\Http\Middleware\EnsureSignatureSet::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'admin/adminer',
            'admin/adminer/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

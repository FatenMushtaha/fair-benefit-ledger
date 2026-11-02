<?php

use App\Http\Middleware\EnsureActiveOrganization;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureUserIsActive::class,
            SetLocale::class,
        ]);

        $middleware->alias([
            'org.context' => EnsureActiveOrganization::class,
        ]);

        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureActiveOrganization::class);
        $middleware->prependToPriorityList(EnsureActiveOrganization::class, EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

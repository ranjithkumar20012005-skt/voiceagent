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
        // Unauthenticated browser requests land on the login page.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Honour X-Forwarded-* from a tunnel / reverse proxy so generated URLs
        // use the public https host instead of http://127.0.0.1.
        $middleware->trustProxies(at: '*');

        // Resolves the workspace every tenant-scoped query filters on. Applied
        // alongside `auth` on the authenticated route group.
        $middleware->alias([
            'workspace' => \App\Http\Middleware\ResolveWorkspace::class,
            'admin'     => \App\Http\Middleware\EnsureInternalAdmin::class,
        ]);

        // ResolveWorkspace MUST run before route-model binding.
        //
        // SubstituteBindings resolves {agent}, {call} and friends by querying the
        // model, and that query is only tenant-filtered if a workspace is already
        // in context. By default bindings are substituted first, so a client could
        // load another client's record simply by putting its id in the URL -- the
        // scope had nothing to filter on yet. Ordering it before bindings is what
        // makes the 404 real rather than incidental.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\ResolveWorkspace::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

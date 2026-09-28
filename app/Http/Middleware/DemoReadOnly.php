<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The "Start Free" demo session can look around but not act: no calls placed,
 * no imports, no settings or agent changes. Signing out is still allowed.
 */
class DemoReadOnly
{
    private const MESSAGE = 'Demo mode is read-only. Sign in to place calls or make changes.';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $request->user()?->isDemo() || $request->routeIs('logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        return back()->with('status', self::MESSAGE);
    }
}

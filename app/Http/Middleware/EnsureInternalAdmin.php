<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate on the internal admin area.
 *
 * A client user hitting an admin URL gets a 404 rather than a 403: a 403 would
 * confirm the area exists, and there is no reason to tell a client that.
 */
class EnsureInternalAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_internal_admin) {
            abort(404);
        }

        return $next($request);
    }
}

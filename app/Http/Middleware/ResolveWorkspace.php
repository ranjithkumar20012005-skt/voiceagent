<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the current workspace into the Tenancy context for the rest of the
 * request, which is what every model scope then filters on.
 *
 * Runs after `auth`, so an authenticated user without a usable workspace is a
 * broken account rather than a visitor: they are signed out instead of being
 * shown an unscoped dashboard, because with no workspace set the model scopes
 * stop filtering and would show everything.
 */
class ResolveWorkspace
{
    public function __construct(private readonly Tenancy $tenancy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $workspace = $user->resolveWorkspace();

        if (! $workspace) {
            return $this->reject($request, 'Your account is not attached to a workspace. Please contact support.');
        }

        if (! $workspace->isActive()) {
            return $this->reject($request, 'This workspace is not active. Please contact support.');
        }

        $this->tenancy->set($workspace);

        // Keep the pointer honest when it was stale or never set.
        if ((int) $user->current_workspace_id !== (int) $workspace->id) {
            $user->forceFill(['current_workspace_id' => $workspace->id])->saveQuietly();
        }

        return $next($request);
    }

    private function reject(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}

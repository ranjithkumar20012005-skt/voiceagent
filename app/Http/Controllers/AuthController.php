<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Show login page
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    /**
     * "Start Free": straight to the dashboard, no signup step.
     *
     * Development only. Signs in a dedicated demo account (never the admin),
     * whose password is random and never shown. In production -- or when the
     * flag is off -- it falls back to the normal sign-in page, so no data is
     * ever reachable without credentials there.
     */
    public function startFree(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (app()->environment('production') || ! config('app.start_free_demo')) {
            return redirect()->route('login');
        }

        $demo = User::firstOrCreate(
            ['email' => 'demo@voiceagent.local'],
            ['name' => 'Demo User', 'password' => Str::random(40)],
        );

        // Every signed-in account needs a workspace: it is what each query is
        // scoped by, and an account without one is signed straight back out.
        // The demo shares the default workspace rather than getting its own.
        $workspace = Workspace::where('slug', 'default')->first()
            ?? Workspace::create([
                'name'   => 'Default Workspace',
                'slug'   => 'default',
                'status' => 'active',
            ]);

        if (! $demo->belongsToWorkspace($workspace)) {
            Membership::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $demo->id,
                'role'         => Membership::MEMBER,
            ]);
        }

        $demo->forceFill(['current_workspace_id' => $workspace->id])->save();

        Auth::login($demo);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

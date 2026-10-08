<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WorkspaceRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Customer self-signup. Creates the user, their workspace and their ownership
 * of it in one transaction, then signs them straight in.
 */
class RegisterController extends Controller
{
    public function __construct(private readonly WorkspaceRegistrar $registrar)
    {
    }

    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'email'         => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $this->registrar->register($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}

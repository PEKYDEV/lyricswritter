<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'name.required'     => 'A felhasználónév kötelező.',
            'password.required' => 'A jelszó kötelező.',
        ]);

        if (Auth::attempt(
            ['name' => $validated['name'], 'password' => $validated['password']],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()
            ->withErrors(['name' => 'Hibás felhasználónév vagy jelszó.'])
            ->onlyInput('name');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

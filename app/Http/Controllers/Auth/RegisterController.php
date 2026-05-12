<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'unique:users',
                // Csak betű, szám, alulvonás, kötőjel
                'regex:/^[a-zA-Z0-9_\-]+$/',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'  => 'A felhasználónév kötelező.',
            'name.min'       => 'A felhasználónév minimum 3 karakter.',
            'name.max'       => 'A felhasználónév maximum 30 karakter.',
            'name.unique'    => 'Ez a felhasználónév már foglalt.',
            'name.regex'     => 'Csak betű, szám, _ és - karakterek engedélyezettek.',
            'password.min'   => 'A jelszó minimum 8 karakter.',
            'password.confirmed' => 'A két jelszó nem egyezik.',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'password' => $validated['password'], // a 'hashed' cast automatikusan bcrypt-el
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect('/');
    }
}

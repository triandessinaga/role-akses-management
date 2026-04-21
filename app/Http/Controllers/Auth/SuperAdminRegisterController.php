<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class SuperAdminRegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register-superadmin');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'secret'   => ['required', 'string'],
        ]);

        // Kunci akses dengan secret key — ganti nilai ini dengan string acak yang kuat
        if ($request->secret !== config('auth.superadmin_secret', 'RAHASIA-SUPERADMIN-2024')) {
            return back()->withErrors(['secret' => 'Kode akses tidak valid.'])->onlyInput('name', 'email');
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'superadmin', // dikunci ke superadmin
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('superadmin.dashboard');
    }
}

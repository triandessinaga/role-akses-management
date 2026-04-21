<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    // READ — Daftar semua user & admin
    public function index()
    {
        $users = User::whereIn('role', ['user', 'admin'])
                     ->latest()
                     ->paginate(10);

        return view('superadmin.users.index', compact('users'));
    }

    // READ — Detail satu user
    public function show(User $user)
    {
        abort_if($user->role === 'superadmin', 403);
        return view('superadmin.users.show', compact('user'));
    }

    // CREATE — Form buat akun baru
    public function create()
    {
        return view('superadmin.users.create');
    }

    // CREATE — Simpan akun baru
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', 'in:user,admin'],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return redirect()->route('superadmin.users.index')
                         ->with('success', 'Akun berhasil dibuat.');
    }

    // UPDATE — Form edit user
    public function edit(User $user)
    {
        abort_if($user->role === 'superadmin', 403, 'Super Admin tidak bisa diedit dari sini.');
        return view('superadmin.users.edit', compact('user'));
    }

    // UPDATE — Simpan perubahan
    public function update(Request $request, User $user)
    {
        abort_if($user->role === 'superadmin', 403);

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $user->id],
            'role'     => ['required', 'in:user,admin'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('superadmin.users.index')
                         ->with('success', 'Data user berhasil diperbarui.');
    }

    // DELETE — Hapus user
    public function destroy(User $user)
    {
        abort_if($user->role === 'superadmin', 403, 'Super Admin tidak bisa dihapus.');
        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Edit User</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f3f4f6; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 440px; }
        h2 { margin: 0 0 0.25rem; }
        .subtitle { font-size: 0.875rem; color: #6b7280; margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #374151; }
        input, select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .hint { font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.75rem; }
        .error-msg { color: #dc2626; font-size: 0.8rem; margin-bottom: 0.75rem; }
        .alert-success { background: #dcfce7; color: #15803d; padding: 0.6rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
        button.submit { width: 100%; padding: 0.6rem; background: #f59e0b; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; }
        button.submit:hover { background: #d97706; }
        a.back { display: block; text-align: center; margin-top: 1rem; color: #6b7280; font-size: 0.875rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Edit User</h2>
        <p class="subtitle">ID: {{ $user->id }} — {{ $user->email }}</p>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="error-msg">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('superadmin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <label>Nama</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus>
            <div class="hint"></div>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            <div class="hint"></div>

            <label>Role</label>
            <select name="role">
                <option value="user"  {{ old('role', $user->role) == 'user'  ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            <div class="hint"></div>

            <label>Password Baru</label>
            <input type="password" name="password" autocomplete="new-password">
            <div class="hint">Kosongkan jika tidak ingin mengubah password.</div>

            <label>Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation">
            <div class="hint"></div>

            <button type="submit" class="submit">Simpan Perubahan</button>
        </form>

        <a class="back" href="{{ route('superadmin.users.index') }}">← Kembali ke daftar</a>
    </div>
</body>
</html>

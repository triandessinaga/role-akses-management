<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buat Akun Baru</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f3f4f6; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 440px; }
        h2 { margin: 0 0 1.5rem; }
        label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #374151; }
        input, select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .hint { font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.75rem; }
        .error-msg { color: #dc2626; font-size: 0.8rem; margin-bottom: 0.5rem; }
        button.submit { width: 100%; padding: 0.6rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; }
        button.submit:hover { background: #2563eb; }
        a.back { display: block; text-align: center; margin-top: 1rem; color: #6b7280; font-size: 0.875rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Buat Akun Baru</h2>

        @if($errors->any())
            <div class="error-msg">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('superadmin.users.store') }}">
            @csrf

            <label>Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            <div class="hint"></div>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <div class="hint"></div>

            <label>Password</label>
            <input type="password" name="password" required>
            <div class="hint"></div>

            <label>Konfirmasi Password</label>
            <input type="password" name="password_confirmation" required>
            <div class="hint"></div>

            <label>Role</label>
            <select name="role">
                <option value="user"  {{ old('role') == 'user'  ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            <div class="hint">Super Admin tidak bisa dibuat dari form ini.</div>

            <button type="submit" class="submit">Buat Akun</button>
        </form>

        <a class="back" href="{{ route('superadmin.users.index') }}">← Kembali ke daftar</a>
    </div>
</body>
</html>

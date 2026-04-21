<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Super Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; margin: auto; }
        .badge { background: #fef9c3; color: #854d0e; padding: 2px 12px; border-radius: 999px; font-size: 0.875rem; }
        a.btn { display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Dashboard Super Admin</h1>
        <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong></p>
        <p>Role: <span class="badge">{{ auth()->user()->role }}</span></p>
        <p>Email: {{ auth()->user()->email }}</p>

        <a class="btn" href="{{ route('superadmin.users.index') }}">Kelola User & Admin</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>

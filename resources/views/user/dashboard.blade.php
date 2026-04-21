<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>User Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        h1 { color: #111; }
        .badge { display: inline-block; background: #dbeafe; color: #1d4ed8; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.875rem; }
        form { margin-top: 1.5rem; }
        button { padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Dashboard User</h1>
        <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong></p>
        <p>Role: <span class="badge">{{ auth()->user()->role }}</span></p>
        <p>Email: {{ auth()->user()->email }}</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail User</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; background: #f3f4f6; padding: 2rem; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; margin: auto; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        h2 { margin: 0 0 1.5rem; }
        .row { display: flex; padding: 0.6rem 0; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; }
        .label { width: 130px; color: #6b7280; flex-shrink: 0; }
        .value { font-weight: 500; }
        .badge { padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-user  { background: #dbeafe; color: #1d4ed8; }
        .badge-admin { background: #dcfce7; color: #15803d; }
        .actions { margin-top: 1.5rem; display: flex; gap: 0.75rem; }
        a.btn-yellow { background: #f59e0b; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; }
        a.btn-gray   { background: #6b7280; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Detail User</h2>

        <div class="row">
            <span class="label">Nama</span>
            <span class="value">{{ $user->name }}</span>
        </div>
        <div class="row">
            <span class="label">Email</span>
            <span class="value">{{ $user->email }}</span>
        </div>
        <div class="row">
            <span class="label">Role</span>
            <span class="value">
                <span class="badge badge-{{ $user->role }}">{{ $user->role }}</span>
            </span>
        </div>
        <div class="row">
            <span class="label">Dibuat</span>
            <span class="value">{{ $user->created_at->format('d M Y, H:i') }}</span>
        </div>
        <div class="row">
            <span class="label">Diperbarui</span>
            <span class="value">{{ $user->updated_at->format('d M Y, H:i') }}</span>
        </div>

        <div class="actions">
            <a class="btn-yellow" href="{{ route('superadmin.users.edit', $user) }}">Edit</a>
            <a class="btn-gray" href="{{ route('superadmin.users.index') }}">← Kembali</a>
        </div>
    </div>
</body>
</html>

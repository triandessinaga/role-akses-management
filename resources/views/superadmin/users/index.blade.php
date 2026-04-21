<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kelola User</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; background: #f3f4f6; padding: 2rem; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 900px; margin: auto; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        h2 { margin: 0 0 1.5rem; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        a.btn-blue { background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; }
        a.btn-yellow { background: #f59e0b; color: white; padding: 0.3rem 0.75rem; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        a.btn-gray { background: #6b7280; color: white; padding: 0.3rem 0.75rem; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 0.875rem; }
        th { background: #f9fafb; font-weight: 600; }
        .badge { padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-user  { background: #dbeafe; color: #1d4ed8; }
        .badge-admin { background: #dcfce7; color: #15803d; }
        .actions { display: flex; gap: 0.5rem; align-items: center; }
        button.btn-red { background: #ef4444; color: white; padding: 0.3rem 0.75rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.8rem; }
        .alert-success { background: #dcfce7; color: #15803d; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .footer { margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        button.logout { background: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <div class="top-bar">
            <h2>Kelola User & Admin</h2>
            <a class="btn-blue" href="{{ route('superadmin.users.create') }}">+ Buat Akun Baru</a>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $user)
                <tr>
                    <td>{{ $users->firstItem() + $i }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge badge-{{ $user->role }}">{{ $user->role }}</span></td>
                    <td>
                        <div class="actions">
                            <a class="btn-gray" href="{{ route('superadmin.users.show', $user) }}">Detail</a>
                            <a class="btn-yellow" href="{{ route('superadmin.users.edit', $user) }}">Edit</a>
                            <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-red">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af;">Belum ada data user.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:1rem;">{{ $users->links() }}</div>

        <div class="footer">
            <a href="{{ route('superadmin.dashboard') }}">← Kembali ke Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>

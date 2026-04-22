# Panduan Laravel: Middleware & Role Akses Multi User

> Materi Pembelajaran Pemrograman Web  
> Topik: Middleware dan Role Akses (User, Admin, Super Admin)

---

## Prasyarat

- PHP >= 8.1
- Composer
- Node.js & NPM
- Laravel 10
- Database MySQL
- Text Editor (VS Code)

---

## Urutan Pengerjaan (Ringkasan)

```
STEP 1  → Buat Project Laravel
STEP 2  → Konfigurasi Database
STEP 3  → Install Laravel Breeze (auth dulu sebelum apapun)
STEP 4  → Tambah Kolom Role ke Tabel Users
STEP 5  → Update Model User
STEP 6  → Buat Seeder Data Awal
STEP 7  → Buat 1 Middleware CheckRole
STEP 8  → Daftarkan Middleware
STEP 9  → Buat Controller per Role
STEP 10 → Memahami routes/web.php vs routes/auth.php
STEP 10b→ Update routes/web.php (tambahkan route role)
STEP 11 → Redirect Login Berdasarkan Role
STEP 12 → Modifikasi Register Bawaan Breeze (khusus User)
STEP 13 → Register Terpisah untuk Admin & Super Admin
STEP 14 → CRUD Kelola User oleh Super Admin
STEP 15 → Buat View Dashboard per Role
STEP 16 → Uji Coba
```

> **Kenapa Breeze di-install di awal?**  
> Karena `php artisan breeze:install blade` akan **menimpa `routes/web.php`** dengan versi bawaannya.  
> Jika route role akses sudah dibuat sebelum Breeze, semuanya akan terhapus.  
> Install Breeze dulu → baru tambahkan route role akses.

---

## STEP 1 — Buat Project Laravel

```bash
composer create-project laravel/laravel laravel-role-akses
cd laravel-role-akses
```

---

## STEP 2 — Konfigurasi Database

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_role
DB_USERNAME=root
DB_PASSWORD=
```

---

## STEP 3 — Install Laravel Breeze

### Apa itu Blade?

Blade adalah **template engine bawaan Laravel**. File view menggunakan ekstensi `.blade.php` dan mendukung sintaks khusus:

| Sintaks Blade | Fungsi |
|---|---|
| `{{ $variable }}` | Tampilkan variabel (auto-escape HTML) |
| `@if / @else / @endif` | Kondisi |
| `@foreach / @endforeach` | Perulangan |
| `@csrf` | Token keamanan form |
| `@method('PUT')` | Spoofing HTTP method |
| `@extends('layout')` | Gunakan layout induk |
| `@section / @yield` | Isi bagian layout |
| `@include('partial')` | Sisipkan file view lain |

### Apa yang dihasilkan Breeze?

```
routes/
  └── auth.php                           ← route login, register, logout

app/Http/Controllers/Auth/
  ├── AuthenticatedSessionController.php  ← handle login & logout
  ├── RegisteredUserController.php        ← handle register user
  └── PasswordResetLinkController.php     ← reset password

resources/views/
  ├── auth/
  │   ├── login.blade.php                ← halaman login
  │   └── register.blade.php             ← halaman register
  ├── layouts/
  │   ├── app.blade.php                  ← layout utama
  │   └── guest.blade.php                ← layout halaman auth
  └── components/                        ← komponen form (input, button, dll)
```

### Langkah Install

**1. Install package:**
```bash
composer require laravel/breeze --dev
```

**2. Generate semua file auth:**
```bash
php artisan breeze:install blade
```

**3. Install dependency frontend:**
```bash
npm install
```

**4. Compile CSS/JS:**
```bash
npm run build
```

> Gunakan `npm run build` bukan `npm run dev`.  
> `npm run build` → compile sekali selesai.  
> `npm run dev` → watcher yang berjalan terus, tidak cocok dijalankan bersamaan `php artisan serve`.

**5. Jalankan migration awal:**
```bash
php artisan migrate
```

---

## STEP 4 — Tambah Kolom Role ke Tabel Users

```bash
php artisan make:migration add_role_to_users_table --table=users
```

Edit file migration yang baru dibuat di `database/migrations/`:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['user', 'admin', 'superadmin'])->default('user');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}
```

Jalankan migration:

```bash
php artisan migrate
```

---

## STEP 5 — Update Model User

Edit `app/Models/User.php`, tambahkan `role` ke `$fillable`:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
];
```

---

## STEP 6 — Buat Seeder Data Awal

```bash
php artisan make:seeder UserSeeder
```

Edit `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'),
                'role'     => 'superadmin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('password'),
                'role'     => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'User Biasa',
                'password' => bcrypt('password'),
                'role'     => 'user',
            ]
        );
    }
}
```

> Menggunakan `updateOrCreate` agar seeder bisa dijalankan berulang kali tanpa error duplikat email.

Daftarkan di `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call(UserSeeder::class);
}
```

Jalankan seeder:

```bash
php artisan db:seed
```

---

## STEP 7 — Buat 1 Middleware untuk Semua Role

> Pendekatan standar industri: cukup **1 file middleware** yang fleksibel.

```bash
php artisan make:middleware CheckRole
```

Edit `app/Http/Middleware/CheckRole.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Penggunaan di route:
     *   middleware('role:admin')
     *   middleware('role:admin,superadmin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
```

> Dengan 1 file ini kamu bisa proteksi semua role:
> - `middleware('role:user')` → hanya user
> - `middleware('role:admin')` → hanya admin
> - `middleware('role:superadmin')` → hanya superadmin
> - `middleware('role:admin,superadmin')` → admin atau superadmin

---

## STEP 8 — Daftarkan Middleware

### Laravel 10 — Edit `app/Http/Kernel.php`

Tambahkan di bagian `$middlewareAliases`:

```php
protected $middlewareAliases = [
    // ... middleware lainnya (jangan hapus yang sudah ada)
    'role' => \App\Http\Middleware\CheckRole::class,
];
```

### Laravel 11 — Edit `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

---

## STEP 9 — Buat Controller per Role

```bash
php artisan make:controller UserController
php artisan make:controller AdminController
php artisan make:controller SuperAdminController
```

### `app/Http/Controllers/UserController.php`

```php
<?php

namespace App\Http\Controllers;

class UserController extends Controller
{
    public function dashboard()
    {
        return view('user.dashboard');
    }
}
```

### `app/Http/Controllers/AdminController.php`

```php
<?php

namespace App\Http\Controllers;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }
}
```

### `app/Http/Controllers/SuperAdminController.php`

```php
<?php

namespace App\Http\Controllers;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        return view('superadmin.dashboard');
    }
}
```

---

## STEP 10 — Memahami `routes/web.php` vs `routes/auth.php`

Sebelum menambahkan route, penting untuk memahami perbedaan kedua file ini.

### `routes/web.php`

File utama untuk semua route aplikasi web. Semua route di sini otomatis mendapat middleware group `web` yang menyediakan:
- Session
- Cookie
- CSRF protection
- Flash message

Di sinilah kita mendefinisikan route untuk dashboard, halaman utama, dan semua fitur aplikasi.

```
routes/web.php
  └── Semua route aplikasi
        ├── Route publik (/, /about, dll)
        ├── Route terproteksi (dashboard, profil, dll)
        └── require auth.php  ← menyertakan route auth
```

### `routes/auth.php`

File khusus yang dibuat oleh **Laravel Breeze** untuk mengelompokkan semua route autentikasi. File ini tidak berdiri sendiri — ia di-include ke dalam `web.php` via:

```php
require __DIR__.'/auth.php';
```

Berisi route-route berikut:

| Route | Fungsi |
|---|---|
| `GET /login` | Tampilkan form login |
| `POST /login` | Proses login |
| `GET /register` | Tampilkan form register |
| `POST /register` | Proses register |
| `POST /logout` | Proses logout |
| `GET /forgot-password` | Form lupa password |
| `POST /forgot-password` | Kirim email reset |
| `GET /reset-password/{token}` | Form reset password |
| `POST /reset-password` | Proses reset password |

### Kenapa dipisah?

| Alasan | Penjelasan |
|---|---|
| Kerapian kode | Route auth tidak bercampur dengan route fitur aplikasi |
| Mudah dikelola | Jika ingin ubah sistem auth, cukup buka `auth.php` |
| Standar Breeze | Breeze generate file ini secara otomatis, kita tidak perlu tulis ulang |

### Alur saat request masuk

```
Browser request ke /login
  └── Laravel baca routes/web.php
        └── Menemukan: require __DIR__.'/auth.php'
              └── Laravel baca routes/auth.php
                    └── Menemukan route GET /login
                          └── Jalankan AuthenticatedSessionController@create
```

> **Aturan penting:** Jangan pernah hapus baris `require __DIR__.'/auth.php'` dari `web.php`.  
> Jika dihapus, semua route login/register/logout akan hilang dan aplikasi tidak bisa diakses.

---

## STEP 10b — Update `routes/web.php`

Breeze sudah mengisi `web.php` dengan route dasarnya. Kita **tambahkan** route role akses di bawahnya — jangan hapus `require __DIR__.'/auth.php'`.

Edit `routes/web.php` menjadi:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdmin\UserManagementController;

Route::get('/', function () {
    return view('welcome');
});

// Auth routes dari Breeze (login, register, logout) — JANGAN DIHAPUS
require __DIR__.'/auth.php';

// Route untuk User biasa
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
});

// Route untuk Admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

// Route untuk Super Admin + CRUD user management
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UserManagementController::class); // CRUD lengkap
});

// Route yang bisa diakses Admin DAN Super Admin
Route::middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/laporan', function () {
        return view('laporan');
    })->name('laporan');
});
```

---

## STEP 11 — Redirect Login Berdasarkan Role

Edit `app/Http/Controllers/Auth/AuthenticatedSessionController.php`.

Cari method `store` dan ubah bagian redirect-nya:

```php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    // Redirect berdasarkan role
    $role = auth()->user()->role;

    return match($role) {
        'superadmin' => redirect()->route('superadmin.dashboard'),
        'admin'      => redirect()->route('admin.dashboard'),
        default      => redirect()->route('user.dashboard'),
    };
}
```

> Tanpa perubahan ini, semua user diarahkan ke `/dashboard` bawaan Breeze setelah login.

---

## STEP 12 — Modifikasi Register Bawaan Breeze (Khusus User)

Register bawaan Breeze tidak menyertakan kolom `role`. Kita perlu memastikan setiap user yang daftar via `/register` otomatis mendapat role `user`.

Edit `app/Http/Controllers/Auth/RegisteredUserController.php`, pada method `store`:

```php
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'role'     => 'user', // ← tambahkan ini, role dikunci ke 'user'
    ]);

    event(new Registered($user));

    Auth::login($user);

    return redirect()->route('user.dashboard'); // ← arahkan ke user dashboard
}
```

> Dengan ini, siapapun yang daftar via `/register` publik hanya bisa menjadi `user` biasa.  
> Admin dan Super Admin tidak bisa dibuat dari form ini.

---

## STEP 13 — Register Terpisah untuk Admin & Super Admin

Standar industri membedakan cara pembuatan akun berdasarkan role:

| Role | URL Register | Siapa yang bisa akses |
|---|---|---|
| User | `/register` | Semua orang (publik) |
| Admin | `/daftar-admin-xyz123` | Hanya yang tahu URL-nya |
| Super Admin | `/daftar-superadmin-abc999` | Hanya yang tahu URL + punya secret key |

> URL rahasia tidak muncul di mana pun di halaman publik. Hanya pemilik sistem yang tahu.  
> Untuk Super Admin ada lapisan keamanan tambahan: **secret key** yang harus diisi saat daftar.

---

### Buat Controller Register Admin

```bash
php artisan make:controller Auth/AdminRegisterController
```

Edit `app/Http/Controllers/Auth/AdminRegisterController.php`:

```php
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

class AdminRegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register-admin');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'admin', // dikunci ke admin
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }
}
```

---

### Buat Controller Register Super Admin

```bash
php artisan make:controller Auth/SuperAdminRegisterController
```

Edit `app/Http/Controllers/Auth/SuperAdminRegisterController.php`:

```php
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

        // Validasi secret key — ganti dengan string acak yang kuat
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
```

> Secret key bisa dikonfigurasi di `config/auth.php` dengan menambahkan:
> ```php
> 'superadmin_secret' => env('SUPERADMIN_SECRET', 'RAHASIA-SUPERADMIN-2024'),
> ```
> Lalu simpan nilai aslinya di `.env`:
> ```env
> SUPERADMIN_SECRET=isi-dengan-string-acak-yang-kuat
> ```

### Konfigurasi Secret Key

**1. Tambahkan di `config/auth.php`** (di bagian paling bawah sebelum `];`):

```php
/*
|--------------------------------------------------------------------------
| Super Admin Secret Key
|--------------------------------------------------------------------------
| Kode rahasia yang wajib diisi saat mendaftar sebagai Super Admin.
| Nilai diambil dari .env — jangan hardcode langsung di sini.
*/
'superadmin_secret' => env('SUPERADMIN_SECRET', ''),
```

**2. Tambahkan di `.env`:**

```env
# Secret key untuk register Super Admin — ganti dengan string acak yang kuat
SUPERADMIN_SECRET=RAHASIA-SUPERADMIN-2024
```

**3. Generate string acak yang kuat:**

```bash
php artisan key:generate --show
```

Salin hasilnya dan gunakan sebagai nilai `SUPERADMIN_SECRET` di `.env`.

**4. Clear cache config setelah ubah `.env`:**

```bash
php artisan config:clear
```

---

### Tambahkan Route di `routes/auth.php`

Tambahkan import dan route baru di dalam blok `middleware('guest')`:

```php
use App\Http\Controllers\Auth\AdminRegisterController;
use App\Http\Controllers\Auth\SuperAdminRegisterController;

Route::middleware('guest')->group(function () {

    // Register User Biasa (publik — semua orang bisa lihat)
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Register Admin (URL rahasia — hanya yang tahu bisa akses)
    // Ganti 'daftar-admin-xyz123' dengan string acak milikmu
    Route::get('daftar-admin-xyz123', [AdminRegisterController::class, 'create'])->name('register.admin');
    Route::post('daftar-admin-xyz123', [AdminRegisterController::class, 'store']);

    // Register Super Admin (URL rahasia + wajib isi secret key)
    // Ganti 'daftar-superadmin-abc999' dengan string acak milikmu
    Route::get('daftar-superadmin-abc999', [SuperAdminRegisterController::class, 'create'])->name('register.superadmin');
    Route::post('daftar-superadmin-abc999', [SuperAdminRegisterController::class, 'store']);

    // ... route login dan lainnya tetap di bawah
});
```

> Ganti `daftar-admin-xyz123` dan `daftar-superadmin-abc999` dengan string acak yang susah ditebak.  
> Contoh generate string acak di terminal:
> ```bash
> php artisan tinker
> >>> Str::random(32)
> ```

---

### Buat View Register Admin (`resources/views/auth/register-admin.blade.php`)

```html
<x-guest-layout>
    <form method="POST" action="{{ route('register.admin') }}">
        @csrf

        <div class="mb-4 text-sm text-gray-600 font-semibold">
            Pendaftaran Akun Admin
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password"
                name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            <x-primary-button class="ms-4">{{ __('Register') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
```

---

### Buat View Register Super Admin (`resources/views/auth/register-superadmin.blade.php`)

```html
<x-guest-layout>
    <form method="POST" action="{{ route('register.superadmin') }}">
        @csrf

        <div class="mb-4 text-sm text-gray-600 font-semibold">
            Pendaftaran Akun Super Admin
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password"
                name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Field tambahan: Secret Key -->
        <div class="mt-4">
            <x-input-label for="secret" value="Kode Akses Rahasia" />
            <x-text-input id="secret" class="block mt-1 w-full" type="password"
                name="secret" required />
            <x-input-error :messages="$errors->get('secret')" class="mt-2" />
            <p class="text-xs text-gray-400 mt-1">Kode ini hanya diketahui oleh pemilik sistem.</p>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            <x-primary-button class="ms-4">{{ __('Register') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
```

---

### Ringkasan Alur Register per Role

```
/register                    → User biasa (publik, semua orang bisa akses)
/daftar-admin-xyz123         → Admin (URL rahasia, hanya yang tahu bisa akses)
/daftar-superadmin-abc999    → Super Admin (URL rahasia + wajib isi secret key)
```

Tidak ada link ke URL rahasia di halaman publik manapun — hanya pemilik sistem yang tahu.

### Isi Lengkap `routes/auth.php` Setelah Modifikasi

```php
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AdminRegisterController;
use App\Http\Controllers\Auth\SuperAdminRegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

    // Register User Biasa (publik)
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Register Admin (URL rahasia)
    Route::get('daftar-admin-xyz123', [AdminRegisterController::class, 'create'])->name('register.admin');
    Route::post('daftar-admin-xyz123', [AdminRegisterController::class, 'store']);

    // Register Super Admin (URL rahasia + secret key)
    Route::get('daftar-superadmin-abc999', [SuperAdminRegisterController::class, 'create'])->name('register.superadmin');
    Route::post('daftar-superadmin-abc999', [SuperAdminRegisterController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')->name('verification.send');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
```

### Buat Controller Manajemen User oleh Super Admin

```bash
php artisan make:controller SuperAdmin/UserManagementController
```

Edit `app/Http/Controllers/SuperAdmin/UserManagementController.php`:

```php
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    // Daftar semua user & admin
    public function index()
    {
        $users = User::whereIn('role', ['user', 'admin'])->latest()->paginate(10);
        return view('superadmin.users.index', compact('users'));
    }

    // Form buat akun baru
    public function create()
    {
        return view('superadmin.users.create');
    }

    // Simpan akun baru
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', 'in:user,admin'], // superadmin tidak bisa dibuat via form
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

    // Hapus akun
    public function destroy(User $user)
    {
        abort_if($user->role === 'superadmin', 403, 'Super Admin tidak bisa dihapus.');
        $user->delete();
        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
```

### Tambahkan Route di `routes/web.php`

Tambahkan di bawah route superadmin yang sudah ada:

```php
use App\Http\Controllers\SuperAdmin\UserManagementController;

// Super Admin — kelola akun user & admin
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UserManagementController::class)->only(['index', 'create', 'store', 'destroy']);
});
```

### View: Daftar User (`resources/views/superadmin/users/index.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Manajemen User</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; background: #f3f4f6; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 800px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; border: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; }
        .badge-user { background: #dbeafe; color: #1d4ed8; padding: 2px 10px; border-radius: 999px; font-size: 0.8rem; }
        .badge-admin { background: #dcfce7; color: #15803d; padding: 2px 10px; border-radius: 999px; font-size: 0.8rem; }
        a.btn { background: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; }
        .success { color: green; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Manajemen User & Admin</h2>

        @if(session('success'))
            <p class="success">{{ session('success') }}</p>
        @endif

        <a class="btn" href="{{ route('superadmin.users.create') }}">+ Buat Akun Baru</a>

        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge-{{ $user->role }}">{{ $user->role }}</span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}"
                              onsubmit="return confirm('Hapus akun ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="color:red; border:none; background:none; cursor:pointer;">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $users->links() }}

        <br>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>
```

### View: Form Buat Akun (`resources/views/superadmin/users/create.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buat Akun Baru</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f3f4f6; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 420px; }
        h2 { margin: 0 0 1.5rem; }
        label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #374151; }
        input, select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-sizing: border-box; margin-bottom: 1rem; }
        button { width: 100%; padding: 0.6rem; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
        .error { color: #dc2626; font-size: 0.875rem; margin-bottom: 1rem; }
        a { color: #6b7280; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Buat Akun User / Admin</h2>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('superadmin.users.store') }}">
            @csrf

            <label>Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" required>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Konfirmasi Password</label>
            <input type="password" name="password_confirmation" required>

            <label>Role</label>
            <select name="role">
                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>

            <button type="submit">Buat Akun</button>
        </form>

        <br>
        <a href="{{ route('superadmin.users.index') }}">← Kembali ke daftar user</a>
    </div>
</body>
</html>
```

---

## STEP 14 — CRUD Kelola User oleh Super Admin

Super Admin dapat melakukan operasi lengkap: **Create, Read, Update, Delete** terhadap akun User dan Admin.

### Struktur File

```
app/Http/Controllers/SuperAdmin/
  └── UserManagementController.php   ← controller CRUD

resources/views/superadmin/users/
  ├── index.blade.php   ← daftar semua user & admin
  ├── show.blade.php    ← detail satu user
  ├── create.blade.php  ← form buat akun baru
  └── edit.blade.php    ← form edit user
```

### Route Resource di `routes/web.php`

```php
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UserManagementController::class); // CRUD lengkap
});
```

`Route::resource` otomatis membuat 7 route sekaligus:

| Method | URL | Action | Route Name |
|---|---|---|---|
| GET | `/superadmin/users` | index | superadmin.users.index |
| GET | `/superadmin/users/create` | create | superadmin.users.create |
| POST | `/superadmin/users` | store | superadmin.users.store |
| GET | `/superadmin/users/{user}` | show | superadmin.users.show |
| GET | `/superadmin/users/{user}/edit` | edit | superadmin.users.edit |
| PUT | `/superadmin/users/{user}` | update | superadmin.users.update |
| DELETE | `/superadmin/users/{user}` | destroy | superadmin.users.destroy |

---

### Controller: `app/Http/Controllers/SuperAdmin/UserManagementController.php`

```php
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
```

> `abort_if($user->role === 'superadmin', 403)` memastikan Super Admin tidak bisa mengedit atau menghapus sesama Super Admin.

---

### View: Index (`resources/views/superadmin/users/index.blade.php`)

Menampilkan tabel daftar user dengan tombol Detail, Edit, dan Hapus.

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kelola User</title>
</head>
<body>
    <h2>Kelola User & Admin</h2>

    @if(session('success'))
        <p style="color:green">{{ session('success') }}</p>
    @endif

    <a href="{{ route('superadmin.users.create') }}">+ Buat Akun Baru</a>

    <table border="1" cellpadding="8">
        <thead>
            <tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse($users as $i => $user)
            <tr>
                <td>{{ $users->firstItem() + $i }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role }}</td>
                <td>
                    <a href="{{ route('superadmin.users.show', $user) }}">Detail</a> |
                    <a href="{{ route('superadmin.users.edit', $user) }}">Edit</a> |
                    <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}"
                          style="display:inline"
                          onsubmit="return confirm('Hapus akun ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="color:red;border:none;background:none;cursor:pointer;">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $users->links() }}

    <a href="{{ route('superadmin.dashboard') }}">← Dashboard</a>
</body>
</html>
```

---

### View: Show (`resources/views/superadmin/users/show.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Detail User</title></head>
<body>
    <h2>Detail User</h2>
    <p><strong>Nama:</strong> {{ $user->name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Role:</strong> {{ $user->role }}</p>
    <p><strong>Dibuat:</strong> {{ $user->created_at->format('d M Y, H:i') }}</p>
    <p><strong>Diperbarui:</strong> {{ $user->updated_at->format('d M Y, H:i') }}</p>

    <a href="{{ route('superadmin.users.edit', $user) }}">Edit</a> |
    <a href="{{ route('superadmin.users.index') }}">← Kembali</a>
</body>
</html>
```

---

### View: Create (`resources/views/superadmin/users/create.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Buat Akun Baru</title></head>
<body>
    <h2>Buat Akun Baru</h2>

    @if($errors->any())
        <ul style="color:red">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('superadmin.users.store') }}">
        @csrf
        <label>Nama</label><br>
        <input type="text" name="name" value="{{ old('name') }}" required><br><br>

        <label>Email</label><br>
        <input type="email" name="email" value="{{ old('email') }}" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <label>Konfirmasi Password</label><br>
        <input type="password" name="password_confirmation" required><br><br>

        <label>Role</label><br>
        <select name="role">
            <option value="user"  {{ old('role') == 'user'  ? 'selected' : '' }}>User</option>
            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
        </select><br><br>

        <button type="submit">Buat Akun</button>
    </form>

    <a href="{{ route('superadmin.users.index') }}">← Kembali</a>
</body>
</html>
```

---

### View: Edit (`resources/views/superadmin/users/edit.blade.php`)

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Edit User</title></head>
<body>
    <h2>Edit User — {{ $user->name }}</h2>

    @if($errors->any())
        <ul style="color:red">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('superadmin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <label>Nama</label><br>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required><br><br>

        <label>Email</label><br>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required><br><br>

        <label>Role</label><br>
        <select name="role">
            <option value="user"  {{ old('role', $user->role) == 'user'  ? 'selected' : '' }}>User</option>
            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
        </select><br><br>

        <label>Password Baru (kosongkan jika tidak diubah)</label><br>
        <input type="password" name="password"><br><br>

        <label>Konfirmasi Password Baru</label><br>
        <input type="password" name="password_confirmation"><br><br>

        <button type="submit">Simpan Perubahan</button>
    </form>

    <a href="{{ route('superadmin.users.index') }}">← Kembali</a>
</body>
</html>
```

### `resources/views/user/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>User Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; margin: auto; }
        .badge { background: #dbeafe; color: #1d4ed8; padding: 2px 12px; border-radius: 999px; font-size: 0.875rem; }
        button { margin-top: 1.5rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; }
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
```

### `resources/views/admin/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; margin: auto; }
        .badge { background: #dcfce7; color: #15803d; padding: 2px 12px; border-radius: 999px; font-size: 0.875rem; }
        button { margin-top: 1.5rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Dashboard Admin</h1>
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
```

### `resources/views/superadmin/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Super Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; margin: auto; }
        .badge { background: #fef9c3; color: #854d0e; padding: 2px 12px; border-radius: 999px; font-size: 0.875rem; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; }
        a.btn { display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; }
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
```

---

## STEP 15 — Buat View Dashboard per Role

Buat folder dan file view berikut:

```
resources/views/
├── user/
│   └── dashboard.blade.php
├── admin/
│   └── dashboard.blade.php
└── superadmin/
    └── dashboard.blade.php
```

### `resources/views/user/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>User Dashboard</title></head>
<body>
    <h1>Dashboard User</h1>
    <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong></p>
    <p>Role: {{ auth()->user()->role }}</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
```

### `resources/views/admin/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Admin Dashboard</title></head>
<body>
    <h1>Dashboard Admin</h1>
    <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong></p>
    <p>Role: {{ auth()->user()->role }}</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
```

### `resources/views/superadmin/dashboard.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Super Admin Dashboard</title></head>
<body>
    <h1>Dashboard Super Admin</h1>
    <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong></p>
    <p>Role: {{ auth()->user()->role }}</p>
    <a href="{{ route('superadmin.users.index') }}">Kelola User & Admin</a><br><br>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
```

---

## STEP 16 — Uji Coba

Jalankan server:

```bash
php artisan serve
```

Buka browser ke `http://127.0.0.1:8000` lalu login dengan:

| Email | Password | Role |
|---|---|---|
| superadmin@example.com | password | Super Admin |
| admin@example.com | password | Admin |
| user@example.com | password | User |

### Yang perlu diuji:

1. Login sebagai `user` → harus masuk ke `/user/dashboard`
2. Login sebagai `admin` → harus masuk ke `/admin/dashboard`
3. Login sebagai `superadmin` → harus masuk ke `/superadmin/dashboard`
4. Daftar via `/register` → role otomatis `user`, diarahkan ke `/user/dashboard`
5. Login sebagai `superadmin`, buka `/superadmin/users` → CRUD user & admin
6. Saat login sebagai `user`, akses `/admin/dashboard` → muncul **403 Akses Ditolak**
7. Saat login sebagai `admin`, akses `/superadmin/dashboard` → muncul **403 Akses Ditolak**
8. Tanpa login, akses `/user/dashboard` → diarahkan ke halaman login

---

## Ringkasan Alur Sistem

```
Register:
  ├── /register                 → User biasa (publik)
  ├── /daftar-admin-xyz123      → Admin (URL rahasia)
  └── /daftar-superadmin-abc999 → Super Admin (URL rahasia + secret key)

Login → Redirect otomatis berdasarkan role:
  ├── superadmin → /superadmin/dashboard
  ├── admin      → /admin/dashboard
  └── user       → /user/dashboard

Middleware CheckRole (1 file):
  └── middleware('role:user')
      middleware('role:admin')
      middleware('role:superadmin')
      middleware('role:admin,superadmin')

Akses Route Terproteksi:
  └── CheckRole dijalankan
        ├── Role cocok      → lanjut ke Controller
        └── Role tidak cocok → abort(403)
```

---


<img width="2562" height="1526" alt="Screenshot 2026-04-22 064327" src="https://github.com/user-attachments/assets/717b86b3-82d5-418e-a050-f54c65da8b5a" />
<img width="1211" height="572" alt="Screenshot 2026-04-22 064844" src="https://github.com/user-attachments/assets/89981386-716e-4752-9739-933cf3480f99" />

<img width="2273" height="1547" alt="Screenshot 2026-04-22 064522" src="https://github.com/user-attachments/assets/e33effe6-506e-420d-863b-fe53d15d795a" /> 
<img width="2242" height="960" alt="Screenshot 2026-04-22 065110" src="https://github.com/user-attachments/assets/a579b8ac-586e-4987-8c51-055d67da7d05" />

<img width="2036" height="834" alt="Screenshot 2026-04-22 065001" src="https://github.com/user-attachments/assets/cd4dfeae-c04b-4b7e-bff5-afbb7a1fb4a7" />

<img width="1690" height="994" alt="Screenshot 2026-04-22 065143" src="https://github.com/user-attachments/assets/e3258785-ae0a-4a1c-b7de-e14d37023dcd" />





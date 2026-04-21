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

// Route untuk Super Admin
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UserManagementController::class);
});

// Route yang bisa diakses Admin DAN Super Admin
Route::middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/laporan', function () {
        return view('laporan');
    })->name('laporan');
});

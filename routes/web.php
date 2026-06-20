<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\UserDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login',    Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', AdminDashboard::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/dashboard', UserDashboard::class)
        ->middleware('role:user')
        ->name('user.dashboard');

    // Explicit logout route
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

    // Cloud services pages (user)
    Route::get('/cloud', function () {
        return view('cloud.index');
    })->middleware('role:user')->name('cloud.index');

    Route::get('/cloud/computing', function () {
        return view('cloud.computing');
    })->middleware('role:user')->name('cloud.computing');

    // API endpoints for compute (web routes with auth & csrf)
    Route::get('/cloud/api/instances', [\App\Http\Controllers\Cloud\ComputeController::class, 'index'])->name('cloud.api.instances');
    Route::post('/cloud/api/instances', [\App\Http\Controllers\Cloud\ComputeController::class, 'store'])->name('cloud.api.instances.store');
    Route::post('/cloud/api/instances/{id}/action', [\App\Http\Controllers\Cloud\ComputeController::class, 'action'])->name('cloud.api.instances.action');
});
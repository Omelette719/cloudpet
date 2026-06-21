<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\UserDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cloud\ComputeController;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/admin/dashboard', AdminDashboard::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/dashboard', UserDashboard::class)
        ->name('user.dashboard');

    Route::get('/app/dashboard', UserDashboard::class)
        ->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');

    // Cloud pages (user only)
    Route::middleware('role:user')->group(function () {

        Route::get('/cloud', function () {
            return view('cloud.index');
        })->name('cloud.index');

        Route::get('/cloud/computing', function () {
            return view('cloud.computing');
        })->name('cloud.computing');
    });

    // Cloud API
    Route::prefix('cloud/api')->group(function () {

        // Catalog / plans
        Route::get('/plans', [ComputeController::class, 'plans'])
            ->name('cloud.api.plans');

        // Instances
        Route::get('/instances', [ComputeController::class, 'index'])
            ->name('cloud.api.instances');

        Route::post('/instances', [ComputeController::class, 'store'])
            ->name('cloud.api.instances.store');

        Route::post('/instances/{id}/action', [ComputeController::class, 'action'])
            ->name('cloud.api.instances.action');

        Route::get('/instances/{id}/stats', [ComputeController::class, 'stats'])
            ->name('cloud.api.instances.stats');
        
        Route::get('/instances/{id}/log', [ComputeController::class, 'log'])
            ->name('cloud.api.instances.log');

        // Admin only
        Route::get('/usage/export', [ComputeController::class, 'exportUsage'])
            ->middleware('role:admin')
            ->name('cloud.api.usage.export');
    });
});

// Public home route
Route::get('/home', function () {
    return view('welcome');
})->name('home');

// Additional route files
if (file_exists(__DIR__ . '/settings.php')) {
    require __DIR__ . '/settings.php';
}
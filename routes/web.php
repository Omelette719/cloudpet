<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\UserDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Cloud\ComputeController;
use App\Http\Controllers\Cloud\DatabaseController;
use App\Livewire\Bucket\BucketManager;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/admin/dashboard', AdminDashboard::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // Admin pages
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/plans', fn() => view('admin.plans'))->name('admin.plans');
        Route::get('/admin/users', fn() => view('admin.users'))->name('admin.users');
        Route::get('/admin/logs',  fn() => view('admin.logs'))->name('admin.logs');
    });

    // Admin API
    Route::prefix('admin/api')->middleware('role:admin')->group(function () {
        Route::get('/stats',                    [AdminController::class, 'stats']);
        Route::get('/server',                   [AdminController::class, 'serverStats']);
        Route::get('/plans',                    [AdminController::class, 'plansList']);
        Route::post('/plans',                   [AdminController::class, 'planStore']);
        Route::put('/plans/{id}',               [AdminController::class, 'planUpdate']);
        Route::delete('/plans/{id}',            [AdminController::class, 'planDestroy']);
        Route::get('/users',                    [AdminController::class, 'usersList']);
        Route::get('/users/{id}',               [AdminController::class, 'userShow']);
        Route::put('/users/{id}',               [AdminController::class, 'userUpdate']);
        Route::get('/logs/activity',            [AdminController::class, 'activityLogs']);
        Route::get('/logs/resource-state',      [AdminController::class, 'resourceStateLogs']);
        Route::get('/logs/errors',              [AdminController::class, 'errorLogs']);
    });

    Route::get('/dashboard', UserDashboard::class)
        ->name('user.dashboard');

    Route::get('/app/dashboard', UserDashboard::class)
        ->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // Cloud pages (user only)
    Route::middleware('role:user')->group(function () {

        Route::get('/cloud', function () {
            return view('cloud.index');
        })->name('cloud.index');

        Route::get('/cloud/computing', function () {
            return view('cloud.computing');
        })->name('cloud.computing');

        Route::get('/cloud/database', function () {
            return view('cloud.database');
        })->name('cloud.database');
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

        // Databases
        Route::get('/databases',                  [DatabaseController::class, 'index'])->name('cloud.api.databases');
        Route::post('/databases',                 [DatabaseController::class, 'store'])->name('cloud.api.databases.store');
        Route::post('/databases/{id}/action',     [DatabaseController::class, 'action'])->name('cloud.api.databases.action');
        Route::get('/databases/{id}/log',         [DatabaseController::class, 'log'])->name('cloud.api.databases.log');
        Route::delete('/databases/{id}',          [DatabaseController::class, 'destroy'])->name('cloud.api.databases.destroy');
        Route::get('/databases/{id}/connection',  [DatabaseController::class, 'connectionInfo'])->name('cloud.api.databases.connection');
        Route::get('/databases/{id}/tables',             [DatabaseController::class, 'tables'])->name('cloud.api.databases.tables');
        Route::post('/databases/{id}/tables/create',    [DatabaseController::class, 'createTable'])->name('cloud.api.databases.tables.create');
        Route::delete('/databases/{id}/tables/{table}', [DatabaseController::class, 'dropTable'])->name('cloud.api.databases.tables.drop');
        Route::get('/databases/{id}/tables/{table}',    [DatabaseController::class, 'tableRows'])->name('cloud.api.databases.table.rows');
        Route::post('/databases/{id}/tables/{table}/rows',   [DatabaseController::class, 'insertRow'])->name('cloud.api.databases.table.insert');
        Route::put('/databases/{id}/tables/{table}/rows',    [DatabaseController::class, 'updateRow'])->name('cloud.api.databases.table.update');
        Route::delete('/databases/{id}/tables/{table}/rows', [DatabaseController::class, 'deleteRow'])->name('cloud.api.databases.table.delete');
        Route::post('/databases/{id}/query',             [DatabaseController::class, 'executeQuery'])->name('cloud.api.databases.query');
        Route::get('/plans/database',             [DatabaseController::class, 'plans'])->name('cloud.api.plans.database');
    });

    Route::get('/cloud/volumes', function () {
        return view('cloud.volumes'); // Kita akan membuat file ini di langkah 3
    })->name('cloud.volumes');
    // Tambahkan ke dalam Route::middleware('auth')->group() di routes/web.php

    // ── Billing API ────────────────────────────────────────────────────────────────
    Route::prefix('/cloud/api/billing')->middleware('auth')->group(function () {
        Route::get('/summary',             [\App\Http\Controllers\Cloud\BillingController::class, 'summary'])->name('cloud.api.billing.summary');
        Route::post('/topup',              [\App\Http\Controllers\Cloud\BillingController::class, 'topUp'])->name('cloud.api.billing.topup');
        Route::get('/history',             [\App\Http\Controllers\Cloud\BillingController::class, 'history'])->name('cloud.api.billing.history');
        Route::post('/storage-subscribe',  [\App\Http\Controllers\Cloud\BillingController::class, 'storageSubscribe'])->name('cloud.api.billing.storage.subscribe');
    });

    // ── Halaman Storage ────────────────────────────────────────────────────────────
    Route::get('/cloud/storage', function () {
        return view('cloud.storage');
    })->middleware(['auth', 'role:user'])->name('cloud.storage');

    Route::get('/cloud/bucket', function () {
        $buckets = \App\Models\StorageBucket::where(
            'user_id',
            Auth::id()
        )->latest()->get();

        return view('cloud.bucket', compact('buckets'));
    })->middleware('role:user')
        ->name('cloud.bucket');

    Route::get('/bucket/{id}', BucketManager::class)
        ->middleware('role:user')
        ->name('bucket.manager');
});

// Public home route
Route::get('/home', function () {
    return view('welcome');
})->name('home');

// Additional route files
if (file_exists(__DIR__ . '/settings.php')) {
    require __DIR__ . '/settings.php';
}

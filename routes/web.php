<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Root / IS the Login page
Route::get('/', fn () => view('livewire.auth.login'))->middleware('guest')->name('login');
Route::get('home', fn () => redirect()->route('login'))->name('home');

// Redirect any attempts to reach the (now disabled) registration page
Route::get('register', function () {
    return redirect()->route('login');
});

// Google OAuth Sign-In (Whitelist model)
Route::get('auth/google', [App\Http\Controllers\Auth\GoogleController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [App\Http\Controllers\Auth\GoogleController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    // Main dashboard entry point — redirects based on role
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Role-specific dashboard routes
    Route::get('dashboard/logistik', [DashboardController::class, 'index'])
        ->middleware('role:logistik')
        ->name('dashboard.logistik');



    // ─── Logistik module (logistik + admin) ───────────────────────
    Route::middleware('role:logistik,admin')->prefix('logistik')->group(function () {

        Route::get('suppliers', App\Livewire\Logistik\Suppliers::class)->name('logistik.suppliers');
        Route::get('categories', App\Livewire\Logistik\Categories::class)->name('logistik.categories');
        Route::get('materials', App\Livewire\Logistik\Materials::class)->name('logistik.materials');
        Route::get('tools', App\Livewire\Logistik\Tools::class)->name('logistik.tools');
        Route::get('houses', App\Livewire\Logistik\Houses::class)->name('logistik.houses');
        // Proyek
        Route::get('houses/{house}', App\Livewire\Logistik\HouseDetail::class)->name('logistik.house-detail');
        Route::get('houses/{house}/finish', App\Livewire\Logistik\HouseFinish::class)->name('logistik.house-finish');
        // Transaksi Direct
        Route::get('transaksi', App\Livewire\Logistik\TransaksiLogistik::class)->name('logistik.transaksi');
        // Log
        Route::get('material-log', App\Livewire\Logistik\MaterialLog::class)->name('logistik.material-log');
        Route::get('tool-log', App\Livewire\Logistik\ToolLog::class)->name('logistik.tool-log');
    });

    // ─── Admin module (admin only) ───────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('playground', App\Livewire\Playground::class)->name('playground');
        Route::get('users', App\Livewire\Admin\UserManagement::class)->name('admin.users');
        Route::get('house-costs', App\Livewire\Admin\HouseCosts::class)->name('admin.house-costs');
        Route::get('house-costs/{house}', App\Livewire\Admin\HouseCostDetail::class)->name('admin.house-costs.detail');
    });
});

require __DIR__.'/settings.php';

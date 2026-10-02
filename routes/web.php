<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Admin\HouseCostDetail;
use App\Livewire\Admin\HouseCosts;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Logistik\Categories;
use App\Livewire\Logistik\ClusterExpenses;
use App\Livewire\Logistik\Clusters;
use App\Livewire\Logistik\HouseDetail;
use App\Livewire\Logistik\HouseFinish;
use App\Livewire\Logistik\Houses;
use App\Livewire\Logistik\InventoryTransfers;
use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\Suppliers;
use App\Livewire\Logistik\ToolLog;
use App\Livewire\Logistik\Tools;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Livewire\Logistik\WarehouseDetail;
use App\Livewire\Logistik\Warehouses;
use App\Livewire\Playground;
use App\Exports\MaterialLogExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;

// Root / and home redirect to Login page
Route::get('/', fn () => redirect()->route('login'));
Route::get('home', fn () => redirect()->route('login'))->name('home');

// Redirect any attempts to reach the (now disabled) registration page
Route::get('register', fn () => redirect()->route('login'));

// Google OAuth Sign-In (Whitelist model)
Route::middleware('guest')->group(function () {
    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'verified', 'role:admin|logistik|keuangan'])->group(function () {
    // Main dashboard entry point — redirects based on role
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Role-specific dashboard routes
    Route::get('dashboard/logistik', [DashboardController::class, 'index'])
        ->middleware('role:logistik')
        ->name('dashboard.logistik');

    // ─── Shared inventory, history, and finance pages ─────────────
    Route::prefix('logistik')->group(function () {

        Route::get('suppliers', Suppliers::class)->name('logistik.suppliers');
        Route::get('categories', Categories::class)->name('logistik.categories');
        Route::get('materials', Materials::class)->name('logistik.materials');
        Route::get('tools', Tools::class)->name('logistik.tools');
        Route::get('warehouses', Warehouses::class)->name('logistik.warehouses');
        Route::get('warehouses/{warehouse}', WarehouseDetail::class)->name('logistik.warehouse-detail');
        Route::get('biaya-rumah', HouseCosts::class)->name('logistik.house-costs');
        Route::get('biaya-rumah/{house}', HouseCostDetail::class)->name('logistik.house-costs.detail');
        Route::get('clusters/{cluster}/expenses', ClusterExpenses::class)->name('logistik.cluster-expenses');
        Route::get('clusters', Clusters::class)->middleware('role:admin|keuangan')->name('logistik.clusters');
        Route::get('biaya-cluster', Clusters::class)->middleware('role:admin|keuangan')->name('logistik.cluster-costs');

        // Lapangan stays available to admins and logistics staff only.
        Route::middleware('role:admin|logistik')->group(function () {
            Route::get('transfers', InventoryTransfers::class)->name('logistik.transfers');
            Route::get('houses', Houses::class)->name('logistik.houses');
            Route::get('houses/{house}', HouseDetail::class)->name('logistik.house-detail');
            Route::get('houses/{house}/finish', HouseFinish::class)->name('logistik.house-finish');
            Route::get('alokasi', TransaksiLogistik::class)->name('logistik.alokasi');
            Route::get('transaksi', TransaksiLogistik::class)->name('logistik.transaksi');
        });
        // Log
        Route::get('material-log/export', function (Request $request) {
            $filters = $request->validate([
                'search' => ['nullable', 'string'],
                'type' => ['nullable', 'in:masuk,keluar'],
                'house' => ['nullable', 'integer'],
                'supplier' => ['nullable', 'integer'],
                'sort' => ['nullable', 'string', 'max:30'],
            ]);
            $user = $request->user();
            $export = new MaterialLogExport(
                $filters['search'] ?? '',
                $filters['type'] ?? '',
                (string) ($filters['house'] ?? ''),
                (string) ($filters['supplier'] ?? ''),
                $filters['sort'] ?? 'date_desc',
                $user->role === 'logistik' ? (int) ($user->cluster_id ?? 0) : null
            );

            return Excel::download($export, 'catatan-material-'.now()->format('Ymd-His').'.xlsx');
        })->name('logistik.material-log.export');
        Route::get('material-log', MaterialLog::class)->name('logistik.material-log');
        Route::get('tool-log', ToolLog::class)->name('logistik.tool-log');
    });

    // ─── Admin-only pages ────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('playground', Playground::class)->name('playground');
        Route::get('users', UserManagement::class)->name('admin.users');
    });

    // Finance can view and manage costs, but not users or admin tools.
    Route::middleware('role:admin|keuangan')->prefix('admin')->group(function () {
        Route::get('house-costs', HouseCosts::class)->name('admin.house-costs');
        Route::get('house-costs/{house}', HouseCostDetail::class)->name('admin.house-costs.detail');
    });
});

require __DIR__.'/settings.php';

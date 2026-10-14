<?php

namespace App\Http\Controllers;

use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Redirect to the correct dashboard based on the user's role.
     */
    public function index(Request $request)
    {
        $role = auth()->user()->role;

        return match ($role) {
            'admin'     => $this->adminDashboard(),
            'keuangan'  => $this->adminDashboard(),
            'pengawas'  => $this->logistikDashboard($request),
            'logistik'  => $this->logistikDashboard($request),
            default     => abort(403, 'Unauthorized.'),
        };
    }

    private function adminDashboard()
    {
        $stats = [
            'total_houses' => House::count(),
            'total_users' => User::count(),
            'total_suppliers' => Supplier::count(),
            'total_cost' => cache()->remember('dashboard_total_cost', 60, function () {
                return (float) MaterialUsage::whereNull('voided_at')->sum('total_cost');
            }),
            'houses_construction' => House::where('status', 'pembangunan')->count(),
            'houses_warranty' => House::where('status', 'selesai')->where('warranty_expires_at', '>', now())->count(),
            'houses_finished' => House::where('status', 'selesai')->count(),

            // Low stock materials (stock <= 10)
            'low_stock_materials' => Material::with('warehouse:id,name')
                ->where('stock', '<=', 10)
                ->orderBy('stock')
                ->take(5)
                ->get(),
            'low_stock_count' => Material::where('stock', '<=', 10)->count(),

            // Active tool loans
            'tools_on_loan_count' => ToolUsage::whereNull('return_date')->whereNull('voided_at')->count(),
            'recent_tool_loans' => ToolUsage::with(['tool:id,name,code', 'house:id,name,house_code', 'warehouse:id,name'])
                ->whereNull('return_date')
                ->whereNull('voided_at')
                ->latest('id')
                ->take(5)
                ->get(),

            // Recent field material usage stream
            'recent_activities' => MaterialUsage::with(['material:id,name,unit', 'house:id,name,house_code', 'user:id,name'])
                ->whereNull('voided_at')
                ->latest('id')
                ->take(6)
                ->get(),

            // Cluster cost overview
            'clusters_summary' => Cluster::withCount('houses')
                ->get()
                ->map(function ($cluster) {
                    $materialCost = (float) DB::table('material_usages as mu')
                        ->join('houses as h', 'h.id', '=', 'mu.house_id')
                        ->where('h.cluster_id', $cluster->id)
                        ->whereNull('mu.voided_at')
                        ->sum('mu.total_cost');

                    $expenseCost = Schema::hasTable('cluster_expenses')
                        ? (float) DB::table('cluster_expenses')
                            ->where('cluster_id', $cluster->id)
                            ->whereIn('type', ['rental', 'rental_extension', 'vendor_service'])
                            ->sum('amount')
                        : 0.0;

                    $cluster->setAttribute('material_cost', $materialCost);
                    $cluster->setAttribute('expense_cost', $expenseCost);
                    $cluster->setAttribute('total_cost', $materialCost + $expenseCost);

                    return $cluster;
                }),
        ];

        return view('dashboard', $stats);
    }

    private function logistikDashboard(Request $request)
    {
        $user = $request->user();
        $activitySort = (string) $request->query('activity_sort', 'date_desc');
        $activitySorters = [
            'date' => 'material_usages.created_at',
            'house' => House::select('name')->whereColumn('houses.id', 'material_usages.house_id'),
            'material' => Material::select('name')->whereColumn('materials.id', 'material_usages.material_id'),
            'quantity' => 'material_usages.quantity',
        ];

        if (! preg_match('/^(.+)_(asc|desc)$/', $activitySort, $matches)
            || ! array_key_exists($matches[1], $activitySorters)) {
            $activitySort = 'date_desc';
            $matches = [null, 'date', 'desc'];
        }

        $recentActivities = MaterialUsage::with(['material', 'house'])
            ->whereNull('voided_at')
            ->whereHas('house', fn ($house) => $house->forUser($user))
            ->orderBy($activitySorters[$matches[1]], $matches[2])
            ->orderByDesc('material_usages.id')
            ->take(5)
            ->get();

        $stats = [
            'total_materials' => Material::count(),
            'low_stock_count' => cache()->remember('dashboard_low_stock_count', 60, function () {
                return Material::where('stock', '<=', 10)->count();
            }),
            'tools_on_loan' => cache()->remember('dashboard_tools_on_loan_cluster_'.$user->cluster_id, 60, function () use ($user) {
                return ToolUsage::whereNull('return_date')->whereNull('voided_at')
                    ->whereHas('house', fn ($house) => $house->forUser($user))
                    ->count();
            }),
            'recent_activities' => $recentActivities,
            'activity_sort' => $activitySort,
        ];

        return view('logistik.dashboard', $stats);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolUsage;
use Illuminate\Http\Request;

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
                return MaterialUsage::whereNull('voided_at')->sum('total_cost');
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

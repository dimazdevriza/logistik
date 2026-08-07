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
    public function index()
    {
        $role = auth()->user()->role;

        return match ($role) {
            'admin'     => $this->adminDashboard(),
            'logistik'  => $this->logistikDashboard(),
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

    private function logistikDashboard()
    {
        $stats = [
            'total_materials' => Material::count(),
            'low_stock_count' => cache()->remember('dashboard_low_stock_count', 60, function () {
                return Material::where('stock', '<=', 10)->count();
            }),
            'tools_on_loan' => cache()->remember('dashboard_tools_on_loan', 60, function () {
                return ToolUsage::whereNull('return_date')->whereNull('voided_at')->count();
            }),
            'recent_activities' => MaterialUsage::with(['material', 'house'])
                ->whereNull('voided_at')
                ->latest()
                ->take(5)
                ->get(),
        ];

        return view('logistik.dashboard', $stats);
    }


}

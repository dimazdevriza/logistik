<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class ImportReconciliation
{
    public static function snapshot(): array
    {
        $warehouses = DB::table('warehouses')->orderBy('id')->get(['id', 'name']);
        $materials = DB::table('materials')
            ->selectRaw('warehouse_id, COUNT(*) AS row_count, COALESCE(SUM(stock), 0) AS stock, COALESCE(SUM(ROUND(stock * unit_price, 2) * 100), 0) AS value_minor')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');
        $tools = DB::table('tool_warehouse_balances')
            ->selectRaw('warehouse_id, COUNT(DISTINCT tool_id) AS row_count, COALESCE(SUM(available_qty + qty_broken), 0) AS total_qty, COALESCE(SUM(available_qty), 0) AS available_qty, COALESCE(SUM(qty_broken), 0) AS broken_qty')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');
        $toolLoans = DB::table('tool_usages')
            ->whereNull('return_date')
            ->whereNull('voided_at')
            ->where('warehouse_source_recorded', true)
            ->whereNotNull('warehouse_id')
            ->selectRaw('warehouse_id, COALESCE(SUM(quantity), 0) AS quantity')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');
        $houseCosts = DB::table('material_usages')
            ->join('houses', 'houses.id', '=', 'material_usages.house_id')
            ->whereNull('material_usages.voided_at')
            ->selectRaw('houses.id, houses.name, COUNT(*) AS row_count, COALESCE(SUM(material_usages.total_cost * 100), 0) AS cost_minor')
            ->groupBy('houses.id', 'houses.name')
            ->get()
            ->keyBy('id');
        $clusterExpenses = DB::table('cluster_expenses')
            ->join('clusters', 'clusters.id', '=', 'cluster_expenses.cluster_id')
            ->selectRaw('clusters.id, clusters.name, COUNT(*) AS row_count, COALESCE(SUM(cluster_expenses.amount * 100), 0) AS amount_minor')
            ->groupBy('clusters.id', 'clusters.name')
            ->get()
            ->keyBy('id');
        $activeToolLoans = fn () => DB::table('tool_usages')->whereNull('return_date')->whereNull('voided_at');
        $toolsTotals = DB::table('tools')->selectRaw('COALESCE(SUM(total_qty), 0) AS owned_qty, COALESCE(SUM(available_qty), 0) AS available_qty, COALESCE(SUM(qty_broken), 0) AS broken_qty')->first();

        return [
            'warehouses' => $warehouses->mapWithKeys(function ($warehouse) use ($materials, $tools, $toolLoans) {
                $material = $materials->get($warehouse->id);
                $tool = $tools->get($warehouse->id);

                return [$warehouse->id => [
                    'name' => $warehouse->name,
                    'material_rows' => (int) ($material->row_count ?? 0),
                    'material_stock' => (float) ($material->stock ?? 0),
                    'material_value_minor' => (int) ($material->value_minor ?? 0),
                    'tool_rows' => (int) ($tool->row_count ?? 0),
                    'tool_total_qty' => (int) ($tool->total_qty ?? 0),
                    'tool_available_qty' => (int) ($tool->available_qty ?? 0),
                    'tool_broken_qty' => (int) ($tool->broken_qty ?? 0),
                    'tool_active_loan_qty' => (int) ($toolLoans->get($warehouse->id)->quantity ?? 0),
                ]];
            })->all(),
            'house_costs' => $houseCosts->mapWithKeys(fn ($house) => [$house->id => [
                'name' => $house->name,
                'usage_rows' => (int) $house->row_count,
                'material_cost_minor' => (int) $house->cost_minor,
            ]])->all(),
            'cluster_expenses' => $clusterExpenses->mapWithKeys(fn ($cluster) => [$cluster->id => [
                'name' => $cluster->name,
                'expense_rows' => (int) $cluster->row_count,
                'amount_minor' => (int) $cluster->amount_minor,
            ]])->all(),
            'totals' => [
                'material_usages' => DB::table('material_usages')->count(),
                'tool_usages' => DB::table('tool_usages')->count(),
                'stock_ins' => DB::table('stock_ins')->count(),
                'houses' => DB::table('houses')->count(),
                'material_cost_minor' => (int) DB::table('material_usages')->whereNull('voided_at')->sum(DB::raw('total_cost * 100')),
                'cluster_expense_minor' => (int) DB::table('cluster_expenses')->sum(DB::raw('amount * 100')),
                'tool_owned_qty' => (int) $toolsTotals->owned_qty,
                'tool_available_qty' => (int) $toolsTotals->available_qty,
                'tool_broken_qty' => (int) $toolsTotals->broken_qty,
                'active_tool_loan_rows' => (int) $activeToolLoans()->count(),
                'active_tool_loan_qty' => (int) $activeToolLoans()->sum('quantity'),
                'unattributed_active_tool_loan_qty' => (int) $activeToolLoans()
                    ->where(function ($query) {
                        $query->where('warehouse_source_recorded', false)->orWhereNull('warehouse_id');
                    })
                    ->sum('quantity'),
            ],
        ];
    }

    public static function delta(array $before, array $after): array
    {
        $warehouses = self::groupDelta($before, $after, 'warehouses', [
            'material_rows', 'material_stock', 'material_value_minor', 'tool_rows', 'tool_total_qty',
            'tool_available_qty', 'tool_broken_qty', 'tool_active_loan_qty',
        ]);
        $houseCosts = self::groupDelta($before, $after, 'house_costs', ['usage_rows', 'material_cost_minor']);
        $clusterExpenses = self::groupDelta($before, $after, 'cluster_expenses', ['expense_rows', 'amount_minor']);

        $totals = [];
        foreach (array_unique(array_merge(array_keys($before['totals'] ?? []), array_keys($after['totals'] ?? []))) as $key) {
            $totals[$key] = ($after['totals'][$key] ?? 0) - ($before['totals'][$key] ?? 0);
        }

        return [
            'warehouses' => $warehouses,
            'house_costs' => $houseCosts,
            'cluster_expenses' => $clusterExpenses,
            'totals' => $totals,
        ];
    }

    private static function groupDelta(array $before, array $after, string $key, array $fields): array
    {
        $ids = array_unique(array_merge(array_keys($before[$key] ?? []), array_keys($after[$key] ?? [])));
        $delta = [];

        foreach ($ids as $id) {
            $old = $before[$key][$id] ?? [];
            $new = $after[$key][$id] ?? [];
            $delta[$id] = ['name' => $new['name'] ?? $old['name'] ?? 'Unknown'];

            foreach ($fields as $field) {
                $delta[$id][$field] = ($new[$field] ?? 0) - ($old[$field] ?? 0);
            }
        }

        return $delta;
    }
}

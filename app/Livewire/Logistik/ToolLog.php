<?php

namespace App\Livewire\Logistik;

use App\Exports\ToolLogExport;
use App\Models\House;
use App\Models\InventoryTransfer;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Support\ToolInventory;
use App\Traits\WithFilterModal;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ToolLog extends Component
{
    use WithFilterModal, WithPagination, WithTableSorting;

    public $search = '';

    public $filterStatus = ''; // '' = Semua, 'dipinjam', 'dikembalikan'

    public $filterHouse = '';

    public $sort = 'date_desc';

    public array $resolutionNotesById = [];

    public array $repairCostsById = [];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterHouse()
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return [
            'date' => 'date',
            'type' => 'type',
            'admin' => 'admin_name',
            'house' => 'house_name',
            'notes' => 'job_notes',
            'code' => 'item_code',
            'name' => 'item_name',
            'volume' => 'volume',
            'unit_price' => 'unit_price',
        ];
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterHouse']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    /**
     * B5 — void an active tool checkout: restore available_qty, flag the row.
     * Voided rows STAY visible in the log (VOIDED badge) but excluded from
     * active-loan aggregates via whereNull('voided_at').
     */
    public function voidTool(int $usageId)
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan', 'pengawas'], true)) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($usageId) {
                $usage = ToolUsage::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
                    ->lockForUpdate()->findOrFail($usageId);
                if (! is_null($usage->voided_at)) {
                    throw new \Exception('Peminjaman ini sudah dibatalkan sebelumnya.');
                }
                if (! is_null($usage->return_date)) {
                    throw new \Exception('Hanya peminjaman aktif yang dapat dibatalkan.');
                }

                $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
                $warehouseId = (int) $usage->warehouse_id;
                if (! $usage->warehouse_source_recorded || ! $warehouseId) {
                    throw new \RuntimeException('Gudang asal peminjaman lama tidak tercatat; jangan kembalikan stok tanpa pemeriksaan.');
                }
                ToolInventory::receive($tool, $warehouseId, (int) $usage->quantity, 0);

                $usage->update([
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                ]);
            });
            session()->flash('success', 'Peminjaman alat dibatalkan; qty tersedia dikembalikan.');
        } catch (\Exception $e) {
            $this->addError('void', $e->getMessage());
        }
    }

    public function resolveBrokenReturn(int $returnLogId, string $resolution): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'pengawas'], true)) {
            abort(403);
        }
        if (! in_array($resolution, ['fixed', 'discarded'], true)) {
            throw ValidationException::withMessages(['resolution' => 'Pilih perbaikan atau afkir.']);
        }

        $rules = ["resolutionNotesById.$returnLogId" => ['required', 'string', 'max:500']];
        if ($resolution === 'fixed') {
            $rules["repairCostsById.$returnLogId"] = ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'];
        }
        $this->validate($rules);

        DB::transaction(function () use ($returnLogId, $resolution): void {
            $return = ToolReturnLog::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
                ->lockForUpdate()->findOrFail($returnLogId);
            if ($return->report_type !== 'broken' || $return->status !== 'pending') {
                throw ValidationException::withMessages(['resolution' => 'Catatan rusak ini sudah diproses atau tidak dapat diselesaikan.']);
            }
            if (! $return->receiving_warehouse_id) {
                throw ValidationException::withMessages(['resolution' => 'Gudang penerima tidak tercatat; stok perlu diperiksa manual.']);
            }

            $tool = Tool::lockForUpdate()->findOrFail($return->tool_id);
            $balance = ToolInventory::lockBalance($tool, (int) $return->receiving_warehouse_id);
            if ($balance->qty_broken < $return->quantity) {
                throw ValidationException::withMessages(['resolution' => 'Saldo alat rusak di gudang tidak mencukupi.']);
            }

            $balance->decrement('qty_broken', $return->quantity);
            if ($resolution === 'fixed') {
                $balance->increment('available_qty', $return->quantity);
            } else {
                if ($tool->total_qty < $return->quantity) {
                    throw ValidationException::withMessages(['resolution' => 'Jumlah alat afkir melebihi total alat tercatat.']);
                }
                $tool->decrement('total_qty', $return->quantity);
            }

            ToolInventory::refreshAggregates($tool);
            $tool->refresh();
            $tool->condition = $tool->qty_broken > 0 ? 'rusak' : 'baik';
            $tool->save();

            $return->update([
                'status' => $resolution,
                'resolved_by_id' => auth()->id(),
                'resolved_at' => now(),
                'resolution_notes' => $this->resolutionNotesById[$returnLogId],
                'repair_cost' => $resolution === 'fixed' ? ($this->repairCostsById[$returnLogId] ?? null) : null,
            ]);
        });

        $this->resetValidation(["resolutionNotesById.$returnLogId", "repairCostsById.$returnLogId"]);
        session()->flash('success', 'Catatan alat rusak berhasil diselesaikan.');
    }

    public function exportExcel()
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan', 'pengawas'], true)) {
            return;
        }

        $export = new ToolLogExport($this->buildRecordsQuery());
        $filename = 'catatan-alat-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    protected function buildRecordsQuery(): \Illuminate\Database\Query\Builder
    {
        $usageQuery = ToolUsage::query()
            ->select(
                DB::raw("'keluar' as type"),
                'tool_usages.id',
                'tool_usages.voided_at',
                'tool_usages.checkout_date as date',
                DB::raw('NULL as received_at'),
                'tool_usages.transaction_code',
                'tool_usages.dispatch_code',
                'tool_usages.created_at',
                'users.name as admin_name',
                'users.role as admin_role',
                'houses.name as house_name',
                'tool_usages.notes as job_notes',
                'tools.code as item_code',
                'tools.name as item_name',
                'tool_usages.quantity as volume',
                DB::raw("'unit' as unit"),
                'tools.purchase_price as unit_price',
                DB::raw('(tools.purchase_price * tool_usages.quantity) as total_cost'),
                'tool_usages.return_date',
                DB::raw('COALESCE(source_stock.entry_code, source_tool.entry_code) as source_entry_code'),
                'source_warehouse.name as source_warehouse_name',
                'dispatch_lines.id as source_line_id',
                DB::raw('NULL as vendor_name'), DB::raw('NULL as rental_due_date'),
                DB::raw('NULL as rental_status'), DB::raw('NULL as rental_evidence_path'),
                DB::raw('NULL as parent_transaction_code')
            )
            ->join('tools', 'tool_usages.tool_id', '=', 'tools.id')
            ->join('houses', 'tool_usages.house_id', '=', 'houses.id')
            ->leftJoin('users', 'tool_usages.user_id', '=', 'users.id')
            ->leftJoin('dispatch_lines', 'tool_usages.dispatch_line_id', '=', 'dispatch_lines.id')
            ->leftJoin('stock_ins as source_stock', 'dispatch_lines.stock_in_id', '=', 'source_stock.id')
            ->leftJoin('tools as source_tool', 'dispatch_lines.tool_id', '=', 'source_tool.id')
            ->leftJoin('warehouses as source_warehouse', 'dispatch_lines.warehouse_id', '=', 'source_warehouse.id')
            ->when(in_array(auth()->user()->role, ['logistik', 'pengawas'], true), fn ($query) => $query->where('houses.cluster_id', auth()->user()->cluster_id ?? 0))
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                ->where('tool_usages.transaction_code', 'like', "%{$this->search}%")
                ->orWhere('tool_usages.dispatch_code', 'like', "%{$this->search}%")
                ->orWhere('tools.entry_code', 'like', "%{$this->search}%")
                ->orWhere('tools.code', 'like', "%{$this->search}%")
                ->orWhere('tools.name', 'like', "%{$this->search}%")
                ->orWhere('source_stock.entry_code', 'like', "%{$this->search}%")
                ->orWhere('source_tool.entry_code', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($query) => $query->where('tool_usages.house_id', $this->filterHouse))
            ->when($this->filterStatus === 'dipinjam', fn ($query) => $query->whereNull('tool_usages.return_date')->whereNull('tool_usages.voided_at'))
            ->when($this->filterStatus === 'dikembalikan', fn ($query) => $query->whereNotNull('tool_usages.return_date'));

        $entryQuery = Tool::query()
            ->select(
                DB::raw("CASE WHEN tools.entry_type = 'opening_balance' THEN 'saldo_awal' ELSE 'masuk' END as type"),
                'tools.id',
                DB::raw('NULL as voided_at'),
                DB::raw('COALESCE(tools.received_date, DATE(tools.received_at)) as date'),
                'tools.received_at',
                'tools.entry_code as transaction_code',
                DB::raw('NULL as dispatch_code'),
                'tools.created_at',
                DB::raw("COALESCE(users.name, 'Tidak tercatat') as admin_name"),
                'users.role as admin_role',
                DB::raw("COALESCE(warehouses.name, '-') as house_name"),
                DB::raw("CASE WHEN tools.entry_type = 'opening_balance' THEN 'Saldo awal alat' ELSE 'Penerimaan alat' END as job_notes"),
                'tools.code as item_code',
                'tools.name as item_name',
                'tools.total_qty as volume',
                DB::raw("'unit' as unit"),
                'tools.purchase_price as unit_price',
                DB::raw('(tools.purchase_price * tools.total_qty) as total_cost'),
                DB::raw('NULL as return_date'),
                DB::raw('NULL as source_entry_code'),
                DB::raw('NULL as source_warehouse_name'),
                DB::raw('NULL as source_line_id'),
                DB::raw('NULL as vendor_name'), DB::raw('NULL as rental_due_date'),
                DB::raw('NULL as rental_status'), DB::raw('NULL as rental_evidence_path'),
                DB::raw('NULL as parent_transaction_code')
            )
            ->leftJoin('users', 'tools.recorded_by_id', '=', 'users.id')
            ->leftJoin('warehouses', 'tools.warehouse_id', '=', 'warehouses.id')
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                ->where('tools.entry_code', 'like', "%{$this->search}%")
                ->orWhere('tools.code', 'like', "%{$this->search}%")
                ->orWhere('tools.name', 'like', "%{$this->search}%")
                ->orWhere('warehouses.name', 'like', "%{$this->search}%")));

        $returnQuery = ToolReturnLog::query()
            ->select(
                DB::raw("'kembali' as type"),
                'tool_return_logs.id',
                DB::raw('NULL as voided_at'),
                DB::raw('DATE(COALESCE(tool_return_logs.received_at, tool_return_logs.created_at)) as date'),
                DB::raw('COALESCE(tool_return_logs.received_at, tool_return_logs.created_at) as received_at'),
                DB::raw("COALESCE(tool_return_logs.transaction_code, CONCAT('KMB-', tool_return_logs.id)) as transaction_code"),
                'tool_usages.dispatch_code',
                'tool_return_logs.created_at',
                DB::raw("COALESCE(receivers.name, reporters.name, 'Tidak tercatat') as admin_name"),
                DB::raw('COALESCE(receivers.role, reporters.role) as admin_role'),
                DB::raw("CONCAT(houses.name, ' / ', COALESCE(receiving_warehouse.name, 'Tidak masuk gudang')) as house_name"),
                DB::raw("CONCAT('Kondisi: ', CASE tool_return_logs.report_type WHEN 'normal' THEN 'baik' WHEN 'broken' THEN 'rusak' WHEN 'lost' THEN 'hilang' ELSE tool_return_logs.report_type END, IF(tool_return_logs.notes IS NULL OR tool_return_logs.notes = '', '', CONCAT(' · ', tool_return_logs.notes))) as job_notes"),
                'tools.code as item_code',
                'tools.name as item_name',
                'tool_return_logs.quantity as volume',
                DB::raw("'unit' as unit"),
                DB::raw('0 as unit_price'),
                DB::raw('0 as total_cost'),
                DB::raw('DATE(COALESCE(tool_return_logs.received_at, tool_return_logs.created_at)) as return_date'),
                DB::raw('NULL as source_entry_code'),
                DB::raw('NULL as source_warehouse_name'),
                DB::raw('NULL as source_line_id'),
                DB::raw('NULL as vendor_name'), DB::raw('NULL as rental_due_date'),
                DB::raw('NULL as rental_status'), DB::raw('NULL as rental_evidence_path'),
                'tool_usages.transaction_code as parent_transaction_code'
            )
            ->join('tools', 'tool_return_logs.tool_id', '=', 'tools.id')
            ->join('houses', 'tool_return_logs.house_id', '=', 'houses.id')
            ->leftJoin('tool_usages', 'tool_return_logs.tool_usage_id', '=', 'tool_usages.id')
            ->leftJoin('warehouses as receiving_warehouse', 'tool_return_logs.receiving_warehouse_id', '=', 'receiving_warehouse.id')
            ->leftJoin('users as receivers', 'tool_return_logs.received_by_id', '=', 'receivers.id')
            ->leftJoin('users as reporters', 'tool_return_logs.reported_by', '=', 'reporters.id')
            ->when(in_array(auth()->user()->role, ['logistik', 'pengawas'], true), fn ($query) => $query->where('houses.cluster_id', auth()->user()->cluster_id ?? 0))
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                ->where('tool_return_logs.transaction_code', 'like', "%{$this->search}%")
                ->orWhereRaw("CONCAT('KMB-', tool_return_logs.id) like ?", ["%{$this->search}%"])
                ->orWhere('tool_usages.transaction_code', 'like', "%{$this->search}%")
                ->orWhere('tools.code', 'like', "%{$this->search}%")
                ->orWhere('tools.name', 'like', "%{$this->search}%")
                ->orWhere('houses.name', 'like', "%{$this->search}%")
                ->orWhere('receiving_warehouse.name', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($query) => $query->where('tool_return_logs.house_id', $this->filterHouse))
            ->when($this->filterStatus === 'dipinjam', fn ($query) => $query->whereRaw('1 = 0'));

        $transferQuery = InventoryTransfer::query()
            ->select(
                DB::raw("'transfer' as type"),
                'inventory_transfers.id',
                DB::raw('NULL as voided_at'),
                'inventory_transfers.transferred_at as date',
                DB::raw('NULL as received_at'),
                'inventory_transfers.transfer_code as transaction_code',
                DB::raw('NULL as dispatch_code'),
                'inventory_transfers.created_at',
                DB::raw("COALESCE(users.name, 'Tidak tercatat') as admin_name"),
                'users.role as admin_role',
                DB::raw("CONCAT('Asal: ', source_warehouse.name, '; tujuan: ', destination_warehouse.name) as house_name"),
                DB::raw("COALESCE(NULLIF(inventory_transfers.notes, ''), 'Transfer antargudang') as job_notes"),
                'tools.code as item_code',
                'tools.name as item_name',
                'inventory_transfers.quantity as volume',
                DB::raw("'unit' as unit"),
                'tools.purchase_price as unit_price',
                DB::raw('0 as total_cost'),
                DB::raw('NULL as return_date'),
                DB::raw('NULL as source_entry_code'),
                DB::raw('NULL as source_warehouse_name'),
                DB::raw('NULL as source_line_id'),
                DB::raw('NULL as vendor_name'), DB::raw('NULL as rental_due_date'),
                DB::raw('NULL as rental_status'), DB::raw('NULL as rental_evidence_path'),
                DB::raw('NULL as parent_transaction_code')
            )
            ->join('tools', 'inventory_transfers.tool_id', '=', 'tools.id')
            ->join('warehouses as source_warehouse', 'inventory_transfers.source_warehouse_id', '=', 'source_warehouse.id')
            ->join('warehouses as destination_warehouse', 'inventory_transfers.destination_warehouse_id', '=', 'destination_warehouse.id')
            ->leftJoin('users', 'inventory_transfers.created_by', '=', 'users.id')
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                ->where('inventory_transfers.transfer_code', 'like', "%{$this->search}%")
                ->orWhere('tools.code', 'like', "%{$this->search}%")
                ->orWhere('tools.name', 'like', "%{$this->search}%")
                ->orWhere('source_warehouse.name', 'like', "%{$this->search}%")
                ->orWhere('destination_warehouse.name', 'like', "%{$this->search}%")));

        $rentalQuery = DB::table('cluster_expenses')
            ->leftJoin('cluster_expenses as rental_parent', 'cluster_expenses.parent_expense_id', '=', 'rental_parent.id')
            ->leftJoin('users', 'cluster_expenses.created_by', '=', 'users.id')
            ->leftJoin(DB::raw('(SELECT cluster_expense_id, GROUP_CONCAT(houses.name ORDER BY houses.name SEPARATOR ", ") as names FROM cluster_expense_house JOIN houses ON houses.id = cluster_expense_house.house_id GROUP BY cluster_expense_id) as rental_houses'), function ($join) {
                $join->on('rental_houses.cluster_expense_id', '=', DB::raw('COALESCE(cluster_expenses.parent_expense_id, cluster_expenses.id)'));
            })
            ->selectRaw("cluster_expenses.type as type, cluster_expenses.id, NULL as voided_at, COALESCE(cluster_expenses.start_date, cluster_expenses.off_hire_date, DATE(cluster_expenses.created_at)) as date, NULL as received_at, CONCAT('SWA-', LPAD(cluster_expenses.id, 6, '0')) as transaction_code, NULL as dispatch_code, cluster_expenses.created_at, COALESCE(users.name, 'Tidak tercatat') as admin_name, users.role as admin_role, COALESCE(rental_houses.names, '-') as house_name, COALESCE(cluster_expenses.notes, cluster_expenses.description) as job_notes, CONCAT('SWA-', LPAD(COALESCE(cluster_expenses.parent_expense_id, cluster_expenses.id), 6, '0')) as item_code, cluster_expenses.description as item_name, cluster_expenses.quantity as volume, 'unit' as unit, IF(cluster_expenses.quantity > 0, cluster_expenses.amount / cluster_expenses.quantity, 0) as unit_price, cluster_expenses.amount as total_cost, cluster_expenses.off_hire_date as return_date, NULL as source_entry_code, NULL as source_warehouse_name, NULL as source_line_id, cluster_expenses.vendor as vendor_name, COALESCE((SELECT MAX(extension.due_date) FROM cluster_expenses extension WHERE extension.parent_expense_id = COALESCE(cluster_expenses.parent_expense_id, cluster_expenses.id) AND extension.type = 'rental_extension'), COALESCE(rental_parent.due_date, cluster_expenses.due_date)) as rental_due_date, CASE WHEN cluster_expenses.type = 'rental_return' OR cluster_expenses.status = 'returned' THEN 'Dikembalikan' WHEN EXISTS (SELECT 1 FROM cluster_expenses rental_return WHERE rental_return.parent_expense_id = cluster_expenses.id AND rental_return.type = 'rental_return') THEN 'Dikembalikan sebagian' WHEN COALESCE((SELECT MAX(extension.due_date) FROM cluster_expenses extension WHERE extension.parent_expense_id = COALESCE(cluster_expenses.parent_expense_id, cluster_expenses.id) AND extension.type = 'rental_extension'), COALESCE(rental_parent.due_date, cluster_expenses.due_date)) < CURDATE() THEN 'Terlambat' ELSE 'Aktif' END as rental_status, cluster_expenses.bill_image as rental_evidence_path, CASE WHEN cluster_expenses.parent_expense_id IS NULL THEN NULL ELSE CONCAT('SWA-', LPAD(cluster_expenses.parent_expense_id, 6, '0')) END as parent_transaction_code")
            ->whereIn('cluster_expenses.type', ['rental', 'rental_extension', 'rental_return'])
            ->when(in_array(auth()->user()->role, ['logistik', 'pengawas'], true), fn ($query) => $query->where('cluster_expenses.cluster_id', auth()->user()->cluster_id ?? 0))
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search->where('cluster_expenses.description', 'like', "%{$this->search}%")
                ->orWhere('cluster_expenses.vendor', 'like', "%{$this->search}%")
                ->orWhereRaw("CONCAT('SWA-', LPAD(cluster_expenses.id, 6, '0')) like ?", ["%{$this->search}%"])
                ->orWhere('rental_houses.names', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($query) => $query->whereExists(fn ($houses) => $houses->selectRaw('1')->from('cluster_expense_house as rental_house_filter')->whereColumn('rental_house_filter.cluster_expense_id', DB::raw('COALESCE(cluster_expenses.parent_expense_id, cluster_expenses.id)'))->where('rental_house_filter.house_id', $this->filterHouse)))
            ->when($this->filterStatus === 'dipinjam', fn ($query) => $query->whereIn('cluster_expenses.type', ['rental', 'rental_extension'])->where('cluster_expenses.status', 'active'))
            ->when($this->filterStatus === 'dikembalikan', fn ($query) => $query->where(fn ($status) => $status->where('cluster_expenses.type', 'rental_return')->orWhere(fn ($parent) => $parent->where('cluster_expenses.type', 'rental')->where('cluster_expenses.status', 'returned'))));

        $combined = $usageQuery->unionAll($entryQuery)->unionAll($returnQuery)->unionAll($transferQuery)->unionAll($rentalQuery);
        $query = DB::table(DB::raw("({$combined->toSql()}) as combined"))
            ->mergeBindings($combined->getQuery());

        [$field, $direction] = $this->tableSortParts();
        $this->applyTableSort($query);
        if ($field === 'date') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderByDesc('date')->orderByDesc('created_at');
        }
        return $query;
    }

    public function render()
    {
        $records = $this->buildRecordsQuery()->paginate(10);

        $houses = House::forUser(auth()->user())->orderBy('name')->get();
        $pendingBrokenReturns = ToolReturnLog::with(['tool:id,name,code', 'house:id,name', 'receivingWarehouse:id,name'])
            ->where('report_type', 'broken')
            ->where('status', 'pending')
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->oldest('received_at')
            ->get();

        return view('livewire.logistik.tool-log', compact('records', 'houses', 'pendingBrokenReturns'))
            ->layout('layouts.app', ['title' => 'Catatan Alat']);
    }
}

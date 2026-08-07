<?php

namespace App\Livewire\Logistik;

use App\Models\ToolUsage;
use App\Models\Tool;
use App\Models\House;
use App\Traits\WithFilterModal;
use App\Exports\ToolLogExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class ToolLog extends Component
{
    use WithPagination, WithFilterModal;

    public $search = '';
    public $filterStatus = ''; // '' = Semua, 'dipinjam', 'dikembalikan'
    public $filterHouse = '';
    public $sortDirection = 'desc';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterHouse() { $this->resetPage(); }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
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
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($usageId) {
                $usage = ToolUsage::lockForUpdate()->findOrFail($usageId);
                if (!is_null($usage->voided_at)) {
                    throw new \Exception('Peminjaman ini sudah dibatalkan sebelumnya.');
                }
                if (!is_null($usage->return_date)) {
                    throw new \Exception('Hanya peminjaman aktif yang dapat dibatalkan.');
                }

                $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
                $tool->increment('available_qty', $usage->quantity);

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

    public function exportExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;

        $export = new ToolLogExport(
            $this->search,
            $this->filterStatus,
            $this->filterHouse,
            $this->sortDirection
        );
        $filename = 'catatan-alat-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $usages = ToolUsage::with(['house', 'tool', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('tool', fn ($tq) => $tq->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($q) => $q->where('house_id', $this->filterHouse))
            ->when($this->filterStatus === 'dipinjam', fn ($q) => $q->whereNull('return_date'))
            ->when($this->filterStatus === 'dikembalikan', fn ($q) => $q->whereNotNull('return_date'))
            ->orderBy('checkout_date', $this->sortDirection)
            ->paginate(10);

        $houses = House::orderBy('name')->get();

        return view('livewire.logistik.tool-log', compact('usages', 'houses'))
            ->layout('layouts.app', ['title' => 'Catatan Alat']);
    }
}

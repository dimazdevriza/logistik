<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Exports\HouseExport;
use App\Exports\HouseListExport;
use App\Imports\HouseImport;
use App\Traits\WithFilterModal;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Houses extends Component
{
    use WithPagination, WithFilterModal, WithFileUploads;

    public $search = '';
    public $filterStatus = '';

    // Import Modal State
    public $showImportModal = false;
    public $importFile = null;
    public $importResultSummary = null;

    public function updatingSearch() { $this->resetPage(); }

    // Confirmation Modal State
    public $showConfirmation = false;
    public $confirmingAction = '';
    public $confirmingId = null;
    public $confirmTitle = '';
    public $confirmMessage = '';

    public function confirm($action, $id = null, $title = '', $message = '')
    {
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->showConfirmation = true;
    }

    public function executeConfirmedAction()
    {
        match ($this->confirmingAction) {
            'delete' => $this->delete($this->confirmingId),
            default => null,
        };

        $this->showConfirmation = false;
        $this->confirmingAction = '';
        $this->confirmingId = null;
    }

    public function updatingFilterStatus() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus']);
        $this->showFilterModal = false;
        $this->resetPage();
    }
    public $showModal = false;
    public $editMode = false;
    public $houseId;

    public $name = '';
    public $type = '';
    public $status = 'perencanaan';

    public $start_date = '';
    public $target_end_date = '';

    public function resetForm()
    {
        $this->houseId = null;
        $this->name = '';
        $this->type = '';
        $this->status = 'perencanaan';

        $this->start_date = '';
        $this->target_end_date = '';
        $this->resetValidation();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'status' => 'required|in:perencanaan,pembangunan,selesai',

            'start_date' => 'nullable|date',
            'target_end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $house = House::findOrFail($id);
        $this->houseId = $house->id;
        $this->name = $house->name;
        $this->type = $house->type;
        $this->status = $house->status;

        $this->start_date = $house->start_date?->format('Y-m-d') ?? '';
        $this->target_end_date = $house->target_end_date?->format('Y-m-d') ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'start_date' => $this->start_date ?: null,
            'target_end_date' => $this->target_end_date ?: null,
        ];

        if (!$this->editMode) {
            $generatedCode = House::generateCode($this->name);

            $exists = House::where('house_code', $generatedCode)->exists();

            if ($exists) {
                $this->addError('name', 'Kode rumah ' . $generatedCode . ' sudah digunakan. Gunakan nama blok / deskripsi yang berbeda.');
                return;
            }

            $data['house_code'] = $generatedCode;
            House::create($data);
            session()->flash('success', 'Rumah berhasil ditambahkan.');
        } else {
            House::findOrFail($this->houseId)->update($data);
            session()->flash('success', 'Rumah berhasil diperbarui.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        House::findOrFail($id)->delete();
        session()->flash('success', 'Rumah berhasil dihapus.');
    }

    public function openImportModal()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;
        $this->importFile = null;
        $this->importResultSummary = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function importExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'importFile.required' => 'Pilih berkas Excel (.xlsx / .xls) terlebih dahulu.',
            'importFile.mimes' => 'Berkas harus berupa format Excel (.xlsx, .xls) atau CSV.',
            'importFile.max' => 'Ukuran berkas maksimal 10MB.',
        ]);

        try {
            $import = new HouseImport();
            Excel::import($import, $this->importFile->getRealPath());

            $this->importResultSummary = [
                'totalRows' => $import->totalRows,
                'successfulRows' => $import->successfulRows,
                'skippedRows' => $import->skippedRows,
                'housesImported' => $import->housesImported,
                'materialsImported' => $import->materialsImported,
                'toolsImported' => $import->toolsImported,
                'logs' => $import->rowLogs,
            ];

            session()->flash('success', "Proses validasi & impor selesai: {$import->successfulRows} dari {$import->totalRows} baris data unit rumah berhasil diproses.");
            $this->resetPage();
        } catch (\Exception $e) {
            $this->addError('importFile', 'Gagal memproses berkas Excel: ' . $e->getMessage());
        }
    }

    public function exportExcel()
    {
        // Only admin or logistik can export
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) {
            return;
        }

        $export = new HouseListExport($this->search, $this->filterStatus);
        $filename = 'daftar-rumah-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $houses = House::query()
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.logistik.houses', compact('houses'))
            ->layout('layouts.app', ['title' => 'Rumah']);
    }
}

<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\Cluster;
use App\Models\MaterialUsage;
use App\Models\ImportBatch;
use App\Exports\HouseExport;
use App\Exports\HouseListExport;
use App\Imports\HouseImport;
use App\Traits\WithFilterModal;
use App\Traits\WithTableSorting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Houses extends Component
{
    use WithPagination, WithFilterModal, WithFileUploads, WithTableSorting;

    public $search = '';
    public $filterStatus = '';
    public $filterCluster = '';
    public $sort = 'name_asc';

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
        $this->resetValidation('delete');
        if ($action === 'delete') {
            $house = House::forUser(auth()->user())->findOrFail($id);
            if ($house->hasTransactionHistory()) {
                $this->addError('delete', 'Rumah tidak dapat dihapus karena memiliki riwayat penggunaan material, peminjaman alat, atau permintaan. Riwayat transaksi harus tetap tersimpan.');
                return;
            }
        }
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
    public function updatingFilterCluster() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterCluster']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'houses.name',
            'code' => 'houses.house_code',
            'cluster' => fn ($query, $direction) => $query->orderBy(
                Cluster::select('name')->whereColumn('clusters.id', 'houses.cluster_id'),
                $direction
            ),
            'type' => 'houses.type',
            'status' => 'houses.status',
            'cost' => fn ($query, $direction) => $query->orderBy(
                MaterialUsage::selectRaw('COALESCE(SUM(total_cost), 0)')
                    ->whereColumn('material_usages.house_id', 'houses.id')
                    ->whereNull('material_usages.voided_at'),
                $direction
            ),
            'start_date' => 'houses.start_date',
            'target_end_date' => 'houses.target_end_date',
        ];
    }
    public $showModal = false;
    public $editMode = false;
    public $houseId;

    public $name = '';
    public $type = '';
    public $status = 'perencanaan';
    public $cluster_id = '';

    public $start_date = '';
    public $target_end_date = '';
    public $houseCount = 1;
    public $bulkBlock = '';
    public $startingNumber = 1;
    public $bulkRows = [];
    public $bulkConflicts = [];
    public $bulkPreviewReady = false;

    public function resetForm()
    {
        $this->houseId = null;
        $this->name = '';
        $this->type = '';
        $this->status = 'perencanaan';
        $this->cluster_id = '';

        $this->start_date = '';
        $this->target_end_date = '';
        $this->houseCount = 1;
        $this->bulkBlock = '';
        $this->startingNumber = 1;
        $this->bulkRows = [];
        $this->bulkConflicts = [];
        $this->bulkPreviewReady = false;
        $this->resetValidation();
    }

    public function updatedHouseCount(): void
    {
        $this->clearBulkPreview();
    }

    public function updatedBulkBlock(): void
    {
        $this->clearBulkPreview();
    }

    public function updatedStartingNumber(): void
    {
        $this->clearBulkPreview();
    }

    public function updatedBulkRows(): void
    {
        $this->resetValidation('bulkRows');
        $this->refreshBulkConflicts();
    }

    private function clearBulkPreview(): void
    {
        $this->bulkRows = [];
        $this->bulkConflicts = [];
        $this->bulkPreviewReady = false;
        $this->resetValidation(['houseCount', 'bulkBlock', 'startingNumber', 'bulkRows']);
    }

    public function previewBulkHouses(): void
    {
        $validated = $this->validate([
            'houseCount' => 'required|integer|min:2|max:100',
            'bulkBlock' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9]+$/'],
            'startingNumber' => 'required|integer|min:1|max:999999',
        ], [
            'houseCount.min' => 'Jumlah rumah minimal 2 untuk membuat beberapa unit.',
            'houseCount.max' => 'Maksimal 100 rumah sekali simpan.',
            'bulkBlock.regex' => 'Blok hanya boleh berisi huruf dan angka tanpa spasi.',
        ], [
            'houseCount' => 'jumlah rumah',
            'bulkBlock' => 'blok',
            'startingNumber' => 'nomor awal',
        ]);

        $firstNumber = (int) $validated['startingNumber'];
        if ($firstNumber + (int) $validated['houseCount'] - 1 > 999999) {
            $this->addError('startingNumber', 'Rentang nomor rumah tidak boleh melebihi 999999.');
            return;
        }

        $width = max(2, strlen((string) $firstNumber));
        $block = strtoupper($validated['bulkBlock']);
        $this->bulkRows = [];

        for ($offset = 0; $offset < (int) $validated['houseCount']; $offset++) {
            $number = str_pad((string) ($firstNumber + $offset), $width, '0', STR_PAD_LEFT);
            $this->bulkRows[] = [
                'name' => "Blok {$block}-{$number}",
                'type' => $this->type,
            ];
        }

        $this->bulkPreviewReady = true;
        $this->refreshBulkConflicts();
    }

    public function removeBulkHouse(int $index): void
    {
        if (!array_key_exists($index, $this->bulkRows)) {
            return;
        }

        unset($this->bulkRows[$index]);
        $this->bulkRows = array_values($this->bulkRows);
        $this->resetValidation('bulkRows');
        $this->refreshBulkConflicts();
    }

    private function refreshBulkConflicts(): void
    {
        $codes = [];
        foreach ($this->bulkRows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $codes[] = House::generateCode($name);
            }
        }

        $existingCodes = array_fill_keys(House::whereIn('house_code', array_unique($codes))->pluck('house_code')->all(), true);
        $firstRowByCode = [];
        $conflicts = [];

        foreach ($this->bulkRows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $code = House::generateCode($name);
            if (isset($existingCodes[$code])) {
                $conflicts[] = $index;
            }
            if (isset($firstRowByCode[$code])) {
                $conflicts[] = $firstRowByCode[$code];
                $conflicts[] = $index;
            } else {
                $firstRowByCode[$code] = $index;
            }
        }

        $this->bulkConflicts = array_values(array_unique($conflicts));
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'status' => 'required|in:perencanaan,pembangunan,selesai',
            'cluster_id' => 'nullable|exists:clusters,id',

            'start_date' => 'nullable|date',
            'target_end_date' => 'nullable|date|after_or_equal:start_date',
            'houseCount' => 'required|integer|min:1|max:100',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->cluster_id = auth()->user()->role === 'admin' ? '' : (auth()->user()->cluster_id ?? '');
        $this->showModal = true;
    }

    public function edit($id)
    {
        $house = House::forUser(auth()->user())->findOrFail($id);
        $this->houseId = $house->id;
        $this->name = $house->name;
        $this->type = $house->type;
        $this->status = $house->status;
        $this->cluster_id = $house->cluster_id ?? '';

        $this->start_date = $house->start_date?->format('Y-m-d') ?? '';
        $this->target_end_date = $house->target_end_date?->format('Y-m-d') ?? '';
        $this->houseCount = 1;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if (!$this->editMode && (int) $this->houseCount > 1) {
            $this->saveBulk();
            return;
        }

        $this->validate();

        $user = auth()->user();
        if ($user->role === 'logistik' && ! $user->cluster_id) {
            $this->addError('cluster_id', 'Akun Logistik belum memiliki cluster tugas.');

            return;
        }

        if ($this->editMode) {
            $existingHouse = House::forUser($user)->findOrFail($this->houseId);
            if ($existingHouse->status === 'selesai') {
                $this->addError('status', 'Rumah selesai dikunci. Gunakan halaman penyelesaian untuk mencatat tanggal dan penanggung jawab; data selesai tidak dapat diedit kembali.');
                return;
            }
        }
        if ($this->status === 'selesai') {
            $this->addError('status', 'Rumah hanya dapat ditandai selesai melalui alur Pertanggungjawaban Alat.');
            return;
        }

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'cluster_id' => $user->role === 'admin' ? ($this->cluster_id ?: null) : $user->cluster_id,
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
            House::forUser($user)->findOrFail($this->houseId)->update($data);
            session()->flash('success', 'Rumah berhasil diperbarui.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    private function saveBulk(): void
    {
        $validated = $this->validate([
            'houseCount' => 'required|integer|min:2|max:100',
            'bulkBlock' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9]+$/'],
            'startingNumber' => 'required|integer|min:1|max:999999',
            'bulkRows' => 'required|array|min:1|max:100',
            'bulkRows.*.name' => 'required|string|max:255',
            'bulkRows.*.type' => 'required|string|max:100',
            'status' => 'required|in:perencanaan,pembangunan,selesai',
            'cluster_id' => 'nullable|exists:clusters,id',
            'start_date' => 'nullable|date',
            'target_end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'houseCount.min' => 'Jumlah rumah minimal 2 untuk membuat beberapa unit.',
            'houseCount.max' => 'Maksimal 100 rumah sekali simpan.',
            'bulkBlock.regex' => 'Blok hanya boleh berisi huruf dan angka tanpa spasi.',
            'bulkRows.required' => 'Tampilkan pratinjau rumah terlebih dahulu.',
            'bulkRows.min' => 'Pilih sedikitnya satu rumah untuk disimpan.',
            'bulkRows.*.name.required' => 'Nama rumah wajib diisi.',
            'bulkRows.*.type.required' => 'Tipe rumah wajib diisi.',
        ], [
            'houseCount' => 'jumlah rumah',
            'bulkBlock' => 'blok',
            'startingNumber' => 'nomor awal',
            'bulkRows.*.name' => 'nama rumah',
            'bulkRows.*.type' => 'tipe rumah',
            'cluster_id' => 'cluster',
            'start_date' => 'tanggal mulai',
            'target_end_date' => 'target selesai',
        ]);

        $user = auth()->user();
        if ($user->role === 'logistik' && ! $user->cluster_id) {
            $this->addError('cluster_id', 'Akun Logistik belum memiliki cluster tugas.');

            return;
        }

        if ($this->status === 'selesai') {
            $this->addError('status', 'Rumah hanya dapat ditandai selesai melalui alur Pertanggungjawaban Alat.');
            return;
        }

        if ((int) $this->startingNumber + (int) $this->houseCount - 1 > 999999) {
            $this->addError('startingNumber', 'Rentang nomor rumah tidak boleh melebihi 999999.');
            return;
        }

        if (!$this->bulkPreviewReady) {
            $this->addError('bulkRows', 'Tampilkan pratinjau rumah terlebih dahulu.');
            return;
        }

        $this->refreshBulkConflicts();
        if ($this->bulkConflicts !== []) {
            $this->addError('bulkRows', 'Perbaiki atau hapus rumah dengan kode yang sudah digunakan sebelum menyimpan.');
            return;
        }

        $timestamp = now();
        $houses = array_map(fn ($row) => [
            'house_code' => House::generateCode(trim($row['name'])),
            'name' => trim($row['name']),
            'type' => trim($row['type']),
            'status' => $validated['status'],
            'cluster_id' => auth()->user()->role === 'admin' ? ($validated['cluster_id'] ?: null) : auth()->user()->cluster_id,
            'start_date' => $validated['start_date'] ?: null,
            'target_end_date' => $validated['target_end_date'] ?: null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $validated['bulkRows']);

        try {
            DB::transaction(fn () => House::insert($houses));
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $this->refreshBulkConflicts();
            $this->addError('bulkRows', 'Kode rumah berubah saat penyimpanan. Periksa kembali pratinjau lalu coba lagi.');
            return;
        }

        $savedCount = count($houses);
        session()->flash('success', "{$savedCount} rumah berhasil ditambahkan.");
        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $house = House::forUser(auth()->user())->lockForUpdate()->findOrFail($id);
            if ($house->hasTransactionHistory()) {
                $this->addError('delete', 'Rumah tidak dapat dihapus karena memiliki riwayat penggunaan material, peminjaman alat, atau permintaan. Riwayat transaksi harus tetap tersimpan.');
                return;
            }
            $house->delete();
            session()->flash('success', 'Rumah berhasil dihapus.');
        });
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
            $import = ImportBatch::run('house', $this->importFile, function (string $path) {
                $user = auth()->user();
                if ($user->role === 'logistik' && ! $user->cluster_id) {
                    throw new \RuntimeException('Akun Logistik belum memiliki cluster tugas.');
                }
                $import = new HouseImport($user->role === 'admin' ? null : (int) $user->cluster_id);
                Excel::import($import, $path);

                return $import;
            });

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
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Gagal memproses berkas Excel: ' . $e->getMessage());
        }
    }

    public function exportExcel()
    {
        // Only admin or logistik can export
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) {
            return;
        }

        $user = auth()->user();
        $export = new HouseListExport($this->search, $this->filterStatus, null, $this->filterCluster, $user->role === 'admin' ? null : (int) ($user->cluster_id ?? 0));
        $filename = 'daftar-rumah-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $houses = House::with('cluster')
            ->forUser(auth()->user())
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%")
                    ->orWhereHas('cluster', fn ($cluster) => $cluster->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCluster, fn ($q) => $q->where('cluster_id', $this->filterCluster))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('houses.id')
            ->paginate(10);

        $clusters = Cluster::when(auth()->user()->role !== 'admin', fn ($query) => $query->whereKey(auth()->user()->cluster_id ?? 0))
            ->orderBy('name')->get();

        $clusterAssignmentMissing = auth()->user()->role === 'logistik' && ! auth()->user()->cluster_id;

        return view('livewire.logistik.houses', compact('houses', 'clusters', 'clusterAssignmentMissing'))
            ->layout('layouts.app', ['title' => 'Rumah']);
    }
}

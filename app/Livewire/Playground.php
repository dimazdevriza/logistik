<?php

namespace App\Livewire;

use Livewire\Component;

class Playground extends Component
{
    public $activeTab = 'components';

    // Table playground state
    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $filterHouse = '';
    public $showFilterModal = false;

    public function resetFilters()
    {
        $this->reset(['search', 'filterType', 'filterStatus', 'filterHouse']);
    }

    public function getActiveFiltersCountProperty()
    {
        $count = 0;
        if (!empty($this->filterType)) $count++;
        if (!empty($this->filterStatus)) $count++;
        if (!empty($this->filterHouse)) $count++;
        return $count;
    }

    public function getTableDataProperty()
    {
        $data = collect([
            [
                'id' => 1,
                'code' => 'TRX-2026-0801',
                'date' => '10 Aug 2026 • 14:30',
                'item_type' => 'material',
                'item_name' => 'Semen Portland 50kg',
                'category' => 'Semen & Mortar',
                'house' => 'Blok A-01',
                'qty' => 50,
                'unit' => 'sak',
                'unit_price' => 75000,
                'total_cost' => 3750000,
                'status' => 'approved',
                'status_label' => '✓ Approved',
                'status_badge' => 'bg-success-subtle text-success border-success-subtle',
            ],
            [
                'id' => 2,
                'code' => 'TRX-2026-0802',
                'date' => '10 Aug 2026 • 15:15',
                'item_type' => 'tool',
                'item_name' => 'Mesin Molen Beton',
                'category' => 'Alat Berat',
                'house' => 'Blok B-04',
                'qty' => 2,
                'unit' => 'unit',
                'unit_price' => 0,
                'total_cost' => 0,
                'status' => 'borrowed',
                'status_label' => 'Dipinjam',
                'status_badge' => 'bg-warning-subtle text-warning border-warning-subtle',
            ],
            [
                'id' => 3,
                'code' => 'TRX-2026-0803',
                'date' => '10 Aug 2026 • 16:00',
                'item_type' => 'material',
                'item_name' => 'Baja Ringan C75',
                'category' => 'Struktur Atap',
                'house' => 'Blok C-02',
                'qty' => 120,
                'unit' => 'batang',
                'unit_price' => 85000,
                'total_cost' => 10200000,
                'status' => 'pending',
                'status_label' => 'Menunggu Kirim',
                'status_badge' => 'bg-info-subtle text-info border-info-subtle',
            ],
            [
                'id' => 4,
                'code' => 'TRX-2026-0804',
                'date' => '10 Aug 2026 • 16:45',
                'item_type' => 'material',
                'item_name' => 'Batu Bata Merah',
                'category' => 'Dinding & Pasangan',
                'house' => 'Blok A-01',
                'qty' => 5000,
                'unit' => 'buah',
                'unit_price' => 900,
                'total_cost' => 4500000,
                'status' => 'rejected',
                'status_label' => 'Ditolak',
                'status_badge' => 'bg-danger-subtle text-danger border-danger-subtle',
            ],
        ]);

        return $data->filter(function ($row) {
            if ($this->search) {
                $term = strtolower($this->search);
                $match = str_contains(strtolower($row['code']), $term)
                    || str_contains(strtolower($row['item_name']), $term)
                    || str_contains(strtolower($row['house']), $term);
                if (!$match) return false;
            }
            if ($this->filterType && $row['item_type'] !== $this->filterType) {
                return false;
            }
            if ($this->filterStatus && $row['status'] !== $this->filterStatus) {
                return false;
            }
            if ($this->filterHouse && $row['house'] !== $this->filterHouse) {
                return false;
            }
            return true;
        });
    }

    public function render()
    {
        return view('livewire.playground', [
            'rows' => $this->tableData,
            'activeFiltersCount' => $this->activeFiltersCount,
        ])->layout('layouts.app', ['title' => 'Design Playground']);
    }
}

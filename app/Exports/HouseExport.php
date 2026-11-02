<?php

namespace App\Exports;

use App\Models\MaterialUsage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HouseExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private int $houseId
    ) {}

    public function sheets(): array
    {
        $year = (int) (MaterialUsage::where('house_id', $this->houseId)
            ->whereNull('voided_at')
            ->max(DB::raw('YEAR(usage_date)')) ?: now()->year);

        return [
            new MaterialUsageExport($this->houseId, 'Material'),
            new ToolUsageExport($this->houseId, 'Alat'),
            new HouseVendorServiceExport($this->houseId),
            new HouseVendorRentalExport($this->houseId),
            new HouseListExport(
                year: $year,
                sheetTitle: 'Laporan Perbulan',
                category: null,
                houseId: $this->houseId,
            ),
        ];
    }
}

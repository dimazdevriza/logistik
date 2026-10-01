<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class HouseExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private int $houseId
    ) {}

    public function sheets(): array
    {
        return [
            new MaterialUsageExport($this->houseId, 'Material'),
            new ToolUsageExport($this->houseId, 'Alat'),
            new HouseVendorServiceExport($this->houseId),
            new HouseVendorRentalExport($this->houseId),
        ];
    }
}

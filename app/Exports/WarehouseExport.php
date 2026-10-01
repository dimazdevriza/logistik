<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WarehouseExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private int $warehouseId) {}

    public function sheets(): array
    {
        return [
            new MaterialInventoryExport('', '', (string) $this->warehouseId),
            new ToolInventoryExport('', '', '', (string) $this->warehouseId),
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\StockIn;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as ColumnDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaterialInventoryExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize, WithTitle, WithDrawings, WithStrictNullComparison
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterCategory = '',
        private string $filterWarehouse = '',
        private string $filterSupplier = '',
        private string $filterStock = ''
    ) {}

    public function query()
    {
        return StockIn::query()
            ->join('materials', 'materials.id', '=', 'stock_ins.material_id')
            ->select('stock_ins.*')
            ->with(['material.supplier', 'material.category', 'material.warehouse', 'supplier', 'warehouse'])
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->whereHas('material', fn ($material) => $material
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%"))
                ->orWhere('entry_code', 'like', "%{$this->search}%")))
            ->when($this->filterCategory, fn ($q) => $q->whereHas('material', fn ($material) => $material->where('category_id', $this->filterCategory)))
            ->when($this->filterSupplier, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('stock_ins.supplier_id', $this->filterSupplier)
                ->orWhere(fn ($fallback) => $fallback->whereNull('stock_ins.supplier_id')
                    ->whereHas('material', fn ($material) => $material->where('supplier_id', $this->filterSupplier)))))
            ->when($this->filterWarehouse, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('stock_ins.warehouse_id', $this->filterWarehouse)
                ->orWhere(fn ($fallback) => $fallback->whereNull('stock_ins.warehouse_id')
                    ->whereHas('material', fn ($material) => $material->where('warehouse_id', $this->filterWarehouse)))))
            ->when($this->filterStock, function ($q) {
                if ($this->filterStock === 'low') {
                    $q->where('stock_ins.remaining_quantity', '<=', 10)->where('stock_ins.remaining_quantity', '>', 0);
                } elseif ($this->filterStock === 'safe') {
                    $q->where('stock_ins.remaining_quantity', '>', 10);
                } elseif ($this->filterStock === 'empty') {
                    $q->where(fn ($sub) => $sub->whereNull('stock_ins.remaining_quantity')->orWhere('stock_ins.remaining_quantity', '<=', 0));
                }
            })
            ->orderBy('materials.id')
            ->orderBy('stock_ins.id');
    }

    public function headings(): array
    {
        $categoryName = $this->filterCategory ? (Category::find($this->filterCategory)?->name ?? 'Semua') : 'Semua';
        
        return [
            [' '],
            ['Laporan Inventaris Material - D\'Royal Village'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            ['Filter aktif: Kategori = ' . $categoryName . ($this->search ? ' | Cari = ' . $this->search : '')],
            [],
            [
                'No',
                'Kode Material',
                'Kode Masuk',
                'Nama Material',
                'Kategori',
                'Supplier',
                'Gudang',
                'Sisa Stok',
                'Satuan',
                'Harga Masuk',
                'Estimasi Nilai',
                'Tanggal Masuk',
            ]
        ];
    }

    public function title(): string
    {
        return 'Material';
    }

    public function drawings(): array
    {
        $drawing = new Drawing();
        $drawing->setName('D\'Royal Village');
        $drawing->setDescription('D\'Royal Village logo');
        $drawing->setPath(public_path('images/logo-light.png'));
        $drawing->setHeight(48);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetY(4);

        return [$drawing];
    }

    public function map($batch): array
    {
        $this->rowNumber++;
        $material = $batch->material;
        $remaining = (float) ($batch->remaining_quantity ?? 0);
        $unitPrice = (float) ($batch->unit_price ?? 0);
        $totalValue = $remaining * $unitPrice;
        $supplier = $batch->supplier?->name ?? $material?->supplier?->name ?? '-';
        $warehouse = $batch->warehouse?->name ?? $material?->warehouse?->name ?? 'Belum ditetapkan';
        $receivedAt = $batch->received_at?->format('d/m/Y H:i')
            ?? $batch->date?->format('d/m/Y')
            ?? $batch->created_at?->format('d/m/Y H:i')
            ?? '-';

        return [
            $this->rowNumber,
            $material?->code ?? '-',
            $batch->entry_code ?? '-',
            $material?->name ?? '-',
            $material?->category?->name ?? 'Tanpa Kategori',
            $supplier,
            $warehouse,
            $remaining,
            $material?->unit ?? '-',
            $unitPrice,
            $totalValue,
            $receivedAt,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0.00',
            'J' => '[$Rp-421] #,##0.00',
            'K' => '[$Rp-421] #,##0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');
        $sheet->mergeCells('A4:L4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Styling (Rule 2: Dark Slate Grey #334155)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '334155'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        $sheet->getStyle('A6:L6')->applyFromArray($headerStyle);

        $sheet->calculateColumnWidths();
        $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
        $columnWidths = [];
        $totalWidth = 0;
        for ($column = 1; $column <= 12; $column++) {
            $width = ColumnDrawing::cellDimensionToPixels($sheet->getColumnDimensionByColumn($column)->getWidth(), $font);
            $columnWidths[] = $width;
            $totalWidth += $width;
        }

        $offset = max(0, (int) (($totalWidth - $sheet->getDrawingCollection()[0]->getWidth()) / 2));
        foreach ($columnWidths as $index => $width) {
            if ($offset < $width) {
                $sheet->getDrawingCollection()[0]
                    ->setCoordinates(Coordinate::stringFromColumnIndex($index + 1) . '1')
                    ->setOffsetX($offset);
                break;
            }
            $offset -= $width;
        }
        
        // Alignment for data
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J:K')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

<?php

namespace App\Exports;

use App\Models\Tool;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as ColumnDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ToolInventoryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithDrawings
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterCategory = '',
        private string $filterCondition = '',
        private string $filterWarehouse = ''
    ) {}

    public function query()
    {
        return Tool::with(['category', 'warehouse', 'warehouseBalances.warehouse'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
            ->when($this->filterWarehouse, fn ($q) => $q->whereHas('warehouseBalances', fn ($balance) => $balance->where('warehouse_id', $this->filterWarehouse)))
            ->orderBy('name');
    }

    public function headings(): array
    {
        $categoryName = $this->filterCategory ? (Category::find($this->filterCategory)?->name ?? 'Semua') : 'Semua';

        return [
            [' '],
            ['Laporan Inventaris Alat - D\'Royal Village'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            ['Filter aktif: Kategori = ' . $categoryName . ' | Kondisi = ' . ($this->filterCondition ?: 'Semua') . ($this->search ? ' | Cari = ' . $this->search : '')],
            [],
            [
                'No',
                'Kode',
                'Nama Alat',
                'Kategori',
                'Gudang',
                'Kondisi',
                'Total Qty',
                'Tersedia',
                'Sedang Dipinjam',
            ]
        ];
    }

    public function title(): string
    {
        return 'Alat';
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

    public function map($tool): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $tool->code,
            $tool->name,
            $tool->category?->name ?? 'Tanpa Kategori',
            $tool->warehouseBalances
                ->filter(fn ($balance) => ($balance->available_qty + $balance->qty_broken) > 0)
                ->map(fn ($balance) => ($balance->warehouse?->name ?? 'Belum ditetapkan').' ('.$balance->available_qty.' tersedia)')
                ->join('; ') ?: 'Belum ditetapkan',
            ucfirst($tool->condition),
            (int) ($tool->total_qty ?? 0),
            (int) ($tool->available_qty ?? 0),
            (int) ($tool->checked_out_qty ?? 0),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

        $sheet->getStyle('A6:I6')->applyFromArray($headerStyle);

        $sheet->calculateColumnWidths();
        $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
        $columnWidths = [];
        $totalWidth = 0;
        for ($column = 1; $column <= 9; $column++) {
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
        
        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

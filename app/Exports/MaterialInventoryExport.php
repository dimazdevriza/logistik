<?php

namespace App\Exports;

use App\Models\Material;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaterialInventoryExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterCategory = ''
    ) {}

    public function query()
    {
        return Material::with(['category', 'supplier'])
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->orderBy('name');
    }

    public function headings(): array
    {
        $categoryName = $this->filterCategory ? (Category::find($this->filterCategory)?->name ?? 'Semua') : 'Semua';
        
        return [
            ['Laporan Inventaris Material - D\'Royal Village'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            ['Filter aktif: Kategori = ' . $categoryName . ($this->search ? ' | Cari = ' . $this->search : '')],
            [], // Empty row
            [
                'No',
                'Kode',
                'Nama Material',
                'Kategori',
                'Supplier',
                'Sisa Stok',
                'Satuan',
                'Harga Satuan',
                'Total Nilai Sisa Stok',
            ]
        ];
    }

    public function map($material): array
    {
        $this->rowNumber++;
        $totalValue = (float) ($material->stock ?? 0) * (float) ($material->unit_price ?? 0);
        
        return [
            $this->rowNumber,
            $material->code ?? '-',
            $material->name,
            $material->category?->name ?? 'Tanpa Kategori',
            $material->supplier?->name ?? '-',
            // Rule 4: Zero substitution
            (float) ($material->stock ?? 0),
            $material->unit,
            (float) ($material->unit_price ?? 0),
            $totalValue,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0.00',
            'H' => '"Rp "#,##0',
            'I' => '"Rp "#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling metadata rows
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10);

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

        $sheet->getStyle('A5:I5')->applyFromArray($headerStyle);
        
        // Alignment for data
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}

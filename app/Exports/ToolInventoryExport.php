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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ToolInventoryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterCategory = '',
        private string $filterCondition = ''
    ) {}

    public function query()
    {
        return Tool::with(['category'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
            ->orderBy('name');
    }

    public function headings(): array
    {
        $categoryName = $this->filterCategory ? (Category::find($this->filterCategory)?->name ?? 'Semua') : 'Semua';

        return [
            ['Laporan Inventaris Alat - D\'Royal Village'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            ['Filter aktif: Kategori = ' . $categoryName . ' | Kondisi = ' . ($this->filterCondition ?: 'Semua') . ($this->search ? ' | Cari = ' . $this->search : '')],
            [], // Empty row
            [
                'No',
                'Kode',
                'Nama Alat',
                'Kategori',
                'Kondisi',
                'Total Qty',
                'Tersedia',
                'Sedang Dipinjam',
            ]
        ];
    }

    public function map($tool): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $tool->code,
            $tool->name,
            $tool->category?->name ?? 'Tanpa Kategori',
            ucfirst($tool->condition),
            (int) ($tool->total_qty ?? 0),
            (int) ($tool->available_qty ?? 0),
            (int) ($tool->checked_out_qty ?? 0),
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
        
        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

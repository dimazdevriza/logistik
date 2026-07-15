<?php

namespace App\Exports;

use App\Models\House;
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

class HouseExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterStatus = ''
    ) {}

    public function query()
    {
        return House::query()
            ->withSum('materialUsages', 'total_cost')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            ['Laporan Proyek Rumah - D\'Royal Village'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            ['Filter aktif: Status = ' . ($this->filterStatus ?: 'Semua') . ($this->search ? ' | Cari = ' . $this->search : '')],
            [], // Empty row
            [
                'No',
                'Kode Rumah',
                'Nama Rumah / Blok',
                'Tipe',
                'Status',
                'Total Biaya Material',
            ]
        ];
    }

    public function map($house): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $house->house_code ?? '-',
            $house->name,
            $house->type,
            ucfirst($house->status),
            (float) ($house->material_usages_sum_total_cost ?? 0),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"Rp "#,##0',
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

        $sheet->getStyle('A5:F5')->applyFromArray($headerStyle);
        
        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}

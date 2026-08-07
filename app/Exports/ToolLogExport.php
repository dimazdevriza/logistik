<?php

namespace App\Exports;

use App\Models\ToolUsage;
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

class ToolLogExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterStatus = '',
        private string $filterHouse = '',
        private string $sortDirection = 'desc'
    ) {}

    public function query()
    {
        return ToolUsage::with(['tool', 'house', 'user'])
            ->whereNull('tool_usages.voided_at')
            ->when($this->search, fn ($q) => $q->whereHas('tool', fn ($tq) => $tq->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($q) => $q->where('house_id', $this->filterHouse))
            ->when($this->filterStatus === 'dipinjam', fn ($q) => $q->whereNull('return_date'))
            ->when($this->filterStatus === 'dikembalikan', fn ($q) => $q->whereNotNull('return_date'))
            ->orderBy('checkout_date', $this->sortDirection);
    }

    public function headings(): array
    {
        return [
            ['Catatan Alat - Sistem Logistik'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            [], // Empty row
            [
                'No',
                'Tanggal Pinjam',
                'Rumah',
                'Nama Alat',
                'Kode Alat',
                'Jumlah',
                'Status',
                'Tanggal Kembali',
                'Pencatat',
                'Catatan',
            ]
        ];
    }

    public function map($usage): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $usage->checkout_date->format('d/m/Y'),
            $usage->house->name,
            $usage->tool->name,
            $usage->tool->code,
            (int) ($usage->quantity ?? 0),
            $usage->return_date ? 'Dikembalikan' : 'Dipinjam',
            $usage->return_date ? $usage->return_date->format('d/m/Y') : '-',
            $usage->user->name,
            $usage->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling metadata rows
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        // Header Styling (Dark Slate Grey #334155)
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

        $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);

        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

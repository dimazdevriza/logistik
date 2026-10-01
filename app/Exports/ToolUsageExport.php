<?php

namespace App\Exports;

use App\Models\House;
use App\Models\ToolUsage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ToolUsageExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private int $houseId,
        private string $sheetTitle = 'Peminjaman Alat',
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function query()
    {
        return ToolUsage::with(['tool', 'user'])
            ->where('house_id', $this->houseId)
            ->whereNull('voided_at')
            ->orderBy('checkout_date', 'desc');
    }

    public function headings(): array
    {
        $house = House::find($this->houseId);

        return [
            ['Laporan Peminjaman Alat - '.($house->name ?? 'D\'Royal Village')],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Proyek: '.($house->house_code ?? '-').' ('.($house->type ?? '-').')'],
            [], // Empty row
            [
                'No',
                'Tanggal Pinjam',
                'Nama Alat',
                'Kode Alat',
                'Jumlah',
                'Status',
                'Tanggal Kembali',
                'Pencatat',
                'Catatan',
                'Dicatat pada',
            ],
        ];
    }

    public function map($usage): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $usage->checkout_date->format('d/m/Y'),
            $usage->tool->name,
            $usage->tool->code,
            // Rule 4: Zero substitution
            (int) ($usage->quantity ?? 0),
            $usage->return_date ? 'Dikembalikan' : 'Dipinjam',
            $usage->return_date ? $usage->return_date->format('d/m/Y') : '-',
            $usage->user->name,
            $usage->notes ?? '-',
            $usage->created_at?->format('d/m/Y H:i') ?? '-',
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

        $sheet->getStyle('A5:J5')->applyFromArray($headerStyle);

        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

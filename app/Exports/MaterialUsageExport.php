<?php

namespace App\Exports;

use App\Models\MaterialUsage;
use App\Models\House;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaterialUsageExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, WithEvents, ShouldAutoSize, WithTitle
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private int $houseId
    ) {}

    public function title(): string
    {
        return 'Penggunaan Material';
    }

    public function query()
    {
        return MaterialUsage::with(['material', 'user'])
            ->where('house_id', $this->houseId)
            ->whereNull('voided_at')
            ->orderBy('usage_date', 'desc')
            ->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        $house = House::find($this->houseId);
        
        return [
            ['Laporan Penggunaan Material - ' . ($house->name ?? 'D\'Royal Village')],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            ['Proyek: ' . ($house->house_code ?? '-') . ' (' . ($house->type ?? '-') . ')'],
            [], // Empty row
            [
                'No',
                'Tanggal',
                'Nama Material',
                'Jumlah',
                'Satuan',
                'Harga Satuan (Snapshot)',
                'Total Biaya',
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
            $usage->usage_date->format('d/m/Y H:i'),
            $usage->material->name,
            // Rule 4: Zero substitution
            (float) ($usage->quantity ?? 0),
            $usage->material->unit,
            (float) ($usage->unit_price_at_usage ?? 0),
            (float) ($usage->total_cost ?? 0),
            $usage->user->name,
            $usage->notes ?? '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0.00',
            'F' => '"Rp "#,##0',
            'G' => '"Rp "#,##0',
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
        $sheet->getStyle('D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;
                
                // Calculate Total
                $totalCost = MaterialUsage::where('house_id', $this->houseId)->whereNull('voided_at')->sum('total_cost');

                // Add Footer Row
                $event->sheet->append([
                    [], // Blank Row
                    ['', '', '', '', '', 'Total Biaya Proyek', $totalCost]
                ]);

                $finalRow = $event->sheet->getHighestRow();
                
                // Styling the total row
                $event->sheet->getStyle('F' . $finalRow . ':G' . $finalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'], // Emerald-500
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Format the total cell
                $event->sheet->getStyle('G' . $finalRow)->getNumberFormat()->setFormatCode('"Rp "#,##0');
            },
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\House;
use App\Models\MaterialUsage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaterialUsageExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private int $houseId,
        private string $sheetTitle = 'Penggunaan Material',
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
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
            ['Laporan Penggunaan Material - '.($house->name ?? 'D\'Royal Village')],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Proyek: '.($house->house_code ?? '-').' ('.($house->type ?? '-').')'],
            [], // Empty row
            [
                'No',
                'Tanggal',
                'Keterangan Pekerjaan',
                'Kode Material',
                'Nama Material',
                'Volume',
                'Satuan',
                'Harga Satuan',
                'Total Biaya',
                'Pencatat',
                'Dicatat pada',
            ],
        ];
    }

    public function map($usage): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $usage->usage_date->format('d/m/Y'),
            $usage->notes ?? '-',
            $usage->material->code ?? '-',
            $usage->material->name,
            // Rule 4: Zero substitution
            (float) ($usage->quantity ?? 0),
            $usage->material->unit,
            (float) ($usage->unit_price_at_usage ?? 0),
            (float) ($usage->total_cost ?? 0),
            $usage->user->name,
            $usage->created_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0.00',
            'H' => '[$Rp-421] #,##0.00',
            'I' => '[$Rp-421] #,##0.00',
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

        $sheet->getStyle('A5:K5')->applyFromArray($headerStyle);

        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                // Calculate Total
                $totalCost = MaterialUsage::where('house_id', $this->houseId)->whereNull('voided_at')->sum('total_cost');

                // Add Footer Row
                $event->sheet->append([
                    [], // Blank Row
                    ['', '', '', '', '', '', '', 'Total Biaya Proyek', $totalCost],
                ]);

                $finalRow = $event->sheet->getHighestRow();

                // Styling the total row
                $event->sheet->getStyle('H'.$finalRow.':I'.$finalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Format the total cell
                $event->sheet->getStyle('I'.$finalRow)->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');
            },
        ];
    }
}

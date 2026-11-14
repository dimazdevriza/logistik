<?php

namespace App\Exports;

use App\Models\House;
use App\Models\MaterialUsage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as ColumnDrawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaterialUsageExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
            [' '],
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
                'Pengambil',
                'Pencatat',
                'Dicatat pada',
            ],
        ];
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
            $usage->taken_by ?: '-',
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
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');
        $sheet->mergeCells('A4:L4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Styling (D'Royal Village Green #1FA33A)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1FA33A'],
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
                    ->setCoordinates(Coordinate::stringFromColumnIndex($index + 1).'1')
                    ->setOffsetX($offset);
                break;
            }
            $offset -= $width;
        }

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

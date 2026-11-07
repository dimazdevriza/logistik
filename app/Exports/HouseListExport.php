<?php

namespace App\Exports;

use App\Models\House;
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

class HouseListExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private $rowNumber = 0;

    private int $year;

    public function __construct(
        private ?string $search = null,
        private ?string $filterStatus = null,
        ?int $year = null,
        private ?string $filterCluster = null,
        private int|array|null $clusterScope = null,
        private ?string $sheetTitle = null,
        private ?string $reportTitle = null,
        private ?string $category = House::CATEGORY_HOUSE,
        private ?int $houseId = null,
    ) {
        $this->year = $year ?: (int) now()->year;
    }

    public function title(): string
    {
        return $this->sheetTitle ?? 'Biaya Rumah '.$this->year;
    }

    public function query()
    {
        $query = House::query()
            ->with('cluster')
            ->withSum(['materialUsages' => fn ($q) => $q->whereNull('voided_at')], 'total_cost')
            ->withCount(['materialUsages' => fn ($q) => $q->whereNull('voided_at')]);

        for ($m = 1; $m <= 12; $m++) {
            $query->withSum([
                'materialUsages as month_'.$m.'_cost' => function ($q) use ($m) {
                    $q->whereNull('voided_at')
                        ->whereYear('usage_date', $this->year)
                        ->whereMonth('usage_date', $m);
                },
            ], 'total_cost');
        }

        return $query
            ->when($this->houseId !== null, fn ($q) => $q->whereKey($this->houseId))
            ->when($this->category !== null, fn ($q) => $q->where('category', $this->category))
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%")
                    ->orWhereHas('cluster', fn ($cluster) => $cluster->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCluster, fn ($q) => $q->where('cluster_id', $this->filterCluster))
            ->when($this->clusterScope !== null, fn ($q) => is_array($this->clusterScope) ? $q->whereIn('cluster_id', $this->clusterScope) : $q->where('cluster_id', $this->clusterScope))
            ->orderBy('name');
    }

    public function headings(): array
    {
        $house = $this->houseId ? House::find($this->houseId) : null;
        $title = $this->reportTitle ?? ($house
            ? 'Laporan Biaya Perbulan - '.$house->name
            : 'D\'ROYAL VILLAGE - LAPORAN MONITORING BIAYA RUMAH PER BULAN (TAHUN '.$this->year.')');
        $subtitle = $house
            ? 'Proyek: '.($house->house_code ?? '-').' ('.($house->type ?? '-').') | Tahun '.$this->year
            : 'Tahun Monitoring: '.$this->year;

        return [
            [' '],
            [$title],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            [$subtitle],
            [],
            [
                'No.',
                'Kode Rumah',
                'Nama / Blok',
                'Cluster',
                'Tipe',
                'Mandor',
                'Status',
                'Bobot Kerja',
                'Bobot Bahan',
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'Mei',
                'Jun',
                'Jul',
                'Agu',
                'Sep',
                'Okt',
                'Nov',
                'Des',
                'Total Tahun '.$this->year,
                'Total Keseluruhan',
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

    public function map($house): array
    {
        $this->rowNumber++;

        $yearTotal = 0;
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $val = (float) ($house->{'month_'.$m.'_cost'} ?? 0);
            $months[] = $val;
            $yearTotal += $val;
        }

        $isPublicWork = method_exists($house, 'isPublicWork') ? $house->isPublicWork() : false;
        $bobotKerja = $isPublicWork ? null : ((float) $house->bobot_rumah) / 100;
        $bobotBahan = $isPublicWork || $house->bobot_bahan === null ? null : ((float) $house->bobot_bahan) / 100;

        return array_merge(
            [
                $this->rowNumber,
                $house->house_code ?? '-',
                $house->name,
                $house->cluster?->name ?? 'Tanpa Cluster',
                $house->type,
                $house->mandor_name ?: '-',
                ucfirst($house->status),
                $bobotKerja,
                $bobotBahan,
            ],
            $months,
            [
                $yearTotal,
                (float) ($house->material_usages_sum_total_cost ?? 0),
            ]
        );
    }

    public function columnFormats(): array
    {
        return [
            'H' => '0%',
            'I' => '0%',
            'J' => '[$Rp-421] #,##0.00',
            'K' => '[$Rp-421] #,##0.00',
            'L' => '[$Rp-421] #,##0.00',
            'M' => '[$Rp-421] #,##0.00',
            'N' => '[$Rp-421] #,##0.00',
            'O' => '[$Rp-421] #,##0.00',
            'P' => '[$Rp-421] #,##0.00',
            'Q' => '[$Rp-421] #,##0.00',
            'R' => '[$Rp-421] #,##0.00',
            'S' => '[$Rp-421] #,##0.00',
            'T' => '[$Rp-421] #,##0.00',
            'U' => '[$Rp-421] #,##0.00',
            'V' => '[$Rp-421] #,##0.00',
            'W' => '[$Rp-421] #,##0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:W1');
        $sheet->mergeCells('A2:W2');
        $sheet->mergeCells('A3:W3');
        $sheet->mergeCells('A4:W4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:W4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A6:W6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '334155'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(6, $sheet->getHighestRow());

                $sheet->getStyle("A6:W{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
                $sheet->getStyle('A6:W6')->getBorders()->getAllBorders()->getColor()->setRGB('000000');

                if ($highestRow >= 7) {
                    $sheet->getStyle("A7:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B7:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G7:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H7:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J7:W{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Add summary footer row
                $totalRow = $highestRow + 1;
                $sheet->setCellValue("I{$totalRow}", 'TOTAL:');
                foreach (range('J', 'W') as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", $highestRow >= 7 ? "=SUM({$col}7:{$col}{$highestRow})" : 0);
                }

                $sheet->getStyle("I{$totalRow}:W{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle("I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("J{$totalRow}:W{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("J{$totalRow}:W{$totalRow}")->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');

                // Center header logo across final calculated column widths
                $sheet->calculateColumnWidths();
                $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
                $columnWidths = [];
                $totalWidth = 0;
                for ($column = 1; $column <= 23; $column++) {
                    $width = ColumnDrawing::cellDimensionToPixels($sheet->getColumnDimensionByColumn($column)->getWidth(), $font);
                    $columnWidths[] = $width;
                    $totalWidth += $width;
                }

                if (! empty($sheet->getDrawingCollection()) && isset($sheet->getDrawingCollection()[0])) {
                    $drawing = $sheet->getDrawingCollection()[0];
                    $offset = max(0, (int) (($totalWidth - $drawing->getWidth()) / 2));
                    foreach ($columnWidths as $index => $width) {
                        if ($offset < $width) {
                            $drawing
                                ->setCoordinates(Coordinate::stringFromColumnIndex($index + 1).'1')
                                ->setOffsetX($offset);
                            break;
                        }
                        $offset -= $width;
                    }
                }
            },
        ];
    }
}

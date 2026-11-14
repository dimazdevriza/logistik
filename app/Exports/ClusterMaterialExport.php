<?php

namespace App\Exports;

use App\Models\Cluster;
use App\Models\MaterialUsage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
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

class ClusterMaterialExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private int $rowNumber = 0;

    public function __construct(private Cluster $cluster) {}

    public function title(): string
    {
        return 'Material';
    }

    public function headings(): array
    {
        return [
            [' '],
            ['Laporan Penggunaan Material - Cluster '.$this->cluster->name],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Cluster: '.$this->cluster->name],
            [],
            [
                'No',
                'Tanggal',
                'Unit / Rumah',
                'Kode Material',
                'Nama Material',
                'Volume',
                'Satuan',
                'Harga Satuan',
                'Total Biaya',
                'Pengambil',
                'Catatan',
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

    public function collection(): Collection
    {
        return MaterialUsage::with(['house:id,name,category', 'material:id,code,name,unit'])
            ->whereNull('voided_at')
            ->whereHas('house', fn ($q) => $q->where('cluster_id', $this->cluster->id))
            ->orderBy('usage_date')
            ->orderBy('id')
            ->get();
    }

    public function map($usage): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $usage->usage_date?->format('d/m/Y') ?? '-',
            $usage->house?->name ?? '-',
            $usage->material?->code ?? '-',
            $usage->material?->name ?? 'Material tidak tersedia',
            (float) $usage->quantity,
            $usage->material?->unit ?? '-',
            (float) $usage->unit_price_at_usage,
            (float) $usage->total_cost,
            $usage->taken_by ?: '-',
            $usage->notes ?? '',
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

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');
        $sheet->mergeCells('A4:K4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:K4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A6:K6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1FA33A'],
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

                $sheet->getStyle("A6:K{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
                $sheet->getStyle('A6:K6')->getBorders()->getAllBorders()->getColor()->setRGB('000000');

                if ($highestRow >= 7) {
                    $sheet->getStyle("A7:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D7:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F7:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("G7:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H7:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $totalRow = $highestRow + 1;
                    $sheet->setCellValue("H{$totalRow}", 'TOTAL:');
                    $sheet->setCellValue("I{$totalRow}", "=SUM(I7:I{$highestRow})");

                    $sheet->getStyle("H{$totalRow}:I{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
                    $sheet->getStyle("H{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("I{$totalRow}")->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');
                }

                $sheet->calculateColumnWidths();
                $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
                $columnWidths = [];
                $totalWidth = 0;
                for ($column = 1; $column <= 11; $column++) {
                    $width = ColumnDrawing::cellDimensionToPixels($sheet->getColumnDimensionByColumn($column)->getWidth(), $font);
                    $columnWidths[] = $width;
                    $totalWidth += $width;
                }

                if (!empty($sheet->getDrawingCollection()) && isset($sheet->getDrawingCollection()[0])) {
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
                }
            },
        ];
    }
}

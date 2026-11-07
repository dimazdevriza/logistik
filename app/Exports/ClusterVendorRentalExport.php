<?php

namespace App\Exports;

use App\Models\Cluster;
use App\Models\ClusterExpense;
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

class ClusterVendorRentalExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private int $rowNumber = 0;

    public function __construct(private Cluster $cluster) {}

    public function title(): string
    {
        return 'Sewa Alat Vendor';
    }

    public function headings(): array
    {
        return [
            [' '],
            ['Laporan Sewa Alat Vendor - Cluster '.$this->cluster->name],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Cluster: '.$this->cluster->name],
            [],
            [
                'No',
                'Jenis',
                'Unit / Rumah',
                'Alat',
                'Vendor',
                'Mulai',
                'Jatuh Tempo',
                'Off-hire',
                'Jumlah Unit',
                'Biaya Sewa',
                'Status',
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
        return ClusterExpense::with(['house:id,name', 'houses:id,name'])
            ->where('cluster_id', $this->cluster->id)
            ->whereIn('type', ['rental', 'rental_extension'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    public function map($expense): array
    {
        $this->rowNumber++;

        $houses = collect([$expense->house?->name])
            ->merge($expense->houses->pluck('name'))
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            $this->rowNumber,
            $expense->type === 'rental_extension' ? 'Perpanjangan sewa' : 'Sewa alat',
            $houses ?: 'Cluster (Umum)',
            $expense->description,
            $expense->vendor ?? '-',
            $expense->start_date?->format('d/m/Y') ?? '-',
            $expense->due_date?->format('d/m/Y') ?? '-',
            $expense->off_hire_date?->format('d/m/Y') ?? '-',
            $expense->quantity === null ? '-' : (float) $expense->quantity,
            (float) $expense->amount,
            $expense->off_hire_date ? 'Selesai' : ($expense->status === 'active' ? 'Aktif' : ucfirst($expense->status)),
            $expense->notes ?? '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => '#,##0.00',
            'J' => '[$Rp-421] #,##0.00',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');
        $sheet->mergeCells('A4:L4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A6:L6')->applyFromArray([
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

                $sheet->getStyle("A6:L{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
                $sheet->getStyle('A6:L6')->getBorders()->getAllBorders()->getColor()->setRGB('000000');

                if ($highestRow >= 7) {
                    $sheet->getStyle("A7:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F7:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("I7:J{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("K7:K{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $totalRow = $highestRow + 1;
                    $sheet->setCellValue("I{$totalRow}", 'TOTAL:');
                    $sheet->setCellValue("J{$totalRow}", "=SUM(J7:J{$highestRow})");

                    $sheet->getStyle("I{$totalRow}:J{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
                    $sheet->getStyle("I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("J{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("J{$totalRow}")->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');
                }

                $sheet->calculateColumnWidths();
                $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
                $columnWidths = [];
                $totalWidth = 0;
                for ($column = 1; $column <= 12; $column++) {
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

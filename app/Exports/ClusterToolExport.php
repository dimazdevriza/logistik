<?php

namespace App\Exports;

use App\Models\Cluster;
use App\Models\ToolUsage;
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

class ClusterToolExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private int $rowNumber = 0;

    public function __construct(private Cluster $cluster) {}

    public function title(): string
    {
        return 'Peminjaman Alat';
    }

    public function headings(): array
    {
        return [
            [' '],
            ['Laporan Peminjaman Alat - Cluster '.$this->cluster->name],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Cluster: '.$this->cluster->name],
            [],
            [
                'No',
                'Tanggal Pinjam',
                'Unit / Rumah',
                'Nama Alat',
                'Kode Alat',
                'Jumlah',
                'Status',
                'Tanggal Kembali',
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
        return ToolUsage::with(['house:id,name', 'tool:id,name,code'])
            ->whereNull('voided_at')
            ->whereHas('house', fn ($q) => $q->where('cluster_id', $this->cluster->id))
            ->orderBy('checkout_date')
            ->orderBy('id')
            ->get();
    }

    public function map($usage): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $usage->checkout_date?->format('d/m/Y') ?? '-',
            $usage->house?->name ?? '-',
            $usage->tool?->name ?? 'Alat tidak tersedia',
            $usage->tool?->code ?? '-',
            (int) ($usage->quantity ?? 0),
            $usage->return_date ? 'Dikembalikan' : 'Dipinjam',
            $usage->return_date ? $usage->return_date->format('d/m/Y') : '-',
            $usage->notes ?? '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A6:I6')->applyFromArray([
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

                $sheet->getStyle("A6:I{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
                $sheet->getStyle('A6:I6')->getBorders()->getAllBorders()->getColor()->setRGB('000000');

                if ($highestRow >= 7) {
                    $sheet->getStyle("A7:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E7:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $totalRow = $highestRow + 1;
                    $sheet->setCellValue("E{$totalRow}", 'TOTAL UNIT:');
                    $sheet->setCellValue("F{$totalRow}", "=SUM(F7:F{$highestRow})");

                    $sheet->getStyle("E{$totalRow}:F{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
                    $sheet->getStyle("E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                }

                $sheet->calculateColumnWidths();
                $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
                $columnWidths = [];
                $totalWidth = 0;
                for ($column = 1; $column <= 9; $column++) {
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

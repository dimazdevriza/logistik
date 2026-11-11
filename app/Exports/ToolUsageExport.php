<?php

namespace App\Exports;

use App\Models\House;
use App\Models\ToolUsage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as ColumnDrawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ToolUsageExport implements FromQuery, ShouldAutoSize, WithDrawings, WithHeadings, WithMapping, WithStyles, WithTitle
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
            [' '],
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
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');
        $sheet->mergeCells('A4:J4');
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:J4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

        $sheet->getStyle('A6:J6')->applyFromArray($headerStyle);

        $sheet->calculateColumnWidths();
        $font = $sheet->getParentOrThrow()->getDefaultStyle()->getFont();
        $columnWidths = [];
        $totalWidth = 0;
        for ($column = 1; $column <= 10; $column++) {
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
        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

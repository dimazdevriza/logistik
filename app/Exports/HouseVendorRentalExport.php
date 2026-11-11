<?php

namespace App\Exports;

use App\Models\ClusterExpense;
use App\Models\House;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
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

class HouseVendorRentalExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithDrawings, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private int $houseId) {}

    public function title(): string
    {
        return 'Sewa Alat';
    }

    public function headings(): array
    {
        $house = House::find($this->houseId);

        return [
            [' '],
            ['Laporan Sewa Alat Vendor - '.($house->name ?? 'D\'Royal Village')],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Proyek: '.($house->house_code ?? '-').' ('.($house->type ?? '-').')'],
            [],
            ['Jenis', 'Alat', 'Vendor', 'Mulai', 'Jatuh tempo', 'Off-hire', 'Jumlah unit', 'Biaya sewa', 'Status', 'Rumah terkait', 'Catatan', 'Pencatat'],
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
        return ClusterExpense::query()
            ->with(['house:id,name', 'houses:id,name', 'createdBy:id,name'])
            ->whereIn('type', ['rental', 'rental_extension'])
            ->where(fn ($query) => $query->where('house_id', $this->houseId)
                ->orWhereHas('houses', fn ($houses) => $houses->whereKey($this->houseId)))
            ->orderBy('start_date')->orderBy('id')
            ->get();
    }

    public function map($expense): array
    {
        $houses = collect([$expense->house?->name])
            ->merge($expense->houses->pluck('name'))
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            $expense->type === 'rental_extension' ? 'Perpanjangan sewa' : 'Sewa alat',
            $expense->description,
            $expense->vendor ?? '-',
            $expense->start_date?->format('d/m/Y') ?? '-',
            $expense->due_date?->format('d/m/Y') ?? '-',
            $expense->off_hire_date?->format('d/m/Y') ?? '-',
            $expense->quantity === null ? '-' : (float) $expense->quantity,
            (float) $expense->amount,
            $expense->off_hire_date ? 'Selesai' : ($expense->status === 'active' ? 'Aktif' : ucfirst($expense->status)),
            $houses !== '' ? $houses : '-',
            $expense->notes ?? '',
            $expense->createdBy?->name ?? '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0.##',
            'H' => '[$Rp-421] #,##0.00',
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
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1FA33A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

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

        $sheet->getStyle('D:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

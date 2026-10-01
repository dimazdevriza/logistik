<?php

namespace App\Exports;

use App\Models\ClusterExpense;
use App\Models\House;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HouseVendorServiceExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private int $houseId) {}

    public function title(): string
    {
        return 'Jasa Vendor';
    }

    public function headings(): array
    {
        $house = House::find($this->houseId);

        return [
            ['Laporan Jasa Vendor - '.($house->name ?? 'D\'Royal Village')],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Proyek: '.($house->house_code ?? '-').' ('.($house->type ?? '-').')'],
            [],
            ['Tanggal layanan', 'Pekerjaan', 'Vendor', 'Total biaya', 'Catatan', 'Pencatat'],
        ];
    }

    public function collection(): Collection
    {
        return ClusterExpense::query()
            ->with('createdBy:id,name')
            ->where('type', 'vendor_service')
            ->where(fn ($query) => $query->where('house_id', $this->houseId)
                ->orWhereHas('houses', fn ($houses) => $houses->whereKey($this->houseId)))
            ->orderBy('start_date')->orderBy('id')
            ->get();
    }

    public function map($expense): array
    {
        return [
            $expense->start_date?->format('d/m/Y') ?? '-',
            $expense->description,
            $expense->vendor ?? '-',
            (float) $expense->amount,
            $expense->notes ?? '',
            $expense->createdBy?->name ?? '-',
        ];
    }

    public function columnFormats(): array
    {
        return ['D' => '[$Rp-421] #,##0.00'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A5:F5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}

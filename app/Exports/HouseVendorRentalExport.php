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

class HouseVendorRentalExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
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
            ['Laporan Sewa Alat Vendor - '.($house->name ?? 'D\'Royal Village')],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            ['Proyek: '.($house->house_code ?? '-').' ('.($house->type ?? '-').')'],
            [],
            ['Jenis', 'Alat', 'Vendor', 'Mulai', 'Jatuh tempo', 'Off-hire', 'Jumlah unit', 'Biaya sewa', 'Status', 'Rumah terkait', 'Catatan', 'Pencatat'],
        ];
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
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A5:L5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('D:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

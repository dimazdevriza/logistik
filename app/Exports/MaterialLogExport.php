<?php

namespace App\Exports;

use App\Models\MaterialUsage;
use App\Models\StockIn;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaterialLogExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, WithEvents, ShouldAutoSize
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterType = '',
        private string $filterHouse = '',
        private string $filterSupplier = '',
        private string $sortDirection = 'desc'
    ) {}

    public function collection()
    {
        $keluarQuery = MaterialUsage::query()
            ->select(
                DB::raw("'keluar' as type"),
                'material_usages.usage_date as date',
                'materials.code as material_code',
                'materials.name as material_name',
                'materials.unit as material_unit',
                'houses.name as blok_rumah',
                'houses.type as cluster',
                'material_usages.notes as keterangan_pekerjaan',
                'suppliers.name as supplier_name',
                'houses.name as reference',
                'material_usages.quantity',
                'material_usages.unit_price_at_usage as unit_price',
                'material_usages.total_cost',
                'users.name as user_name',
                'material_usages.created_at as created_at'
            )
            ->join('materials', 'material_usages.material_id', '=', 'materials.id')
            ->leftJoin('suppliers', 'materials.supplier_id', '=', 'suppliers.id')
            ->join('houses', 'material_usages.house_id', '=', 'houses.id')
            ->join('users', 'material_usages.user_id', '=', 'users.id')
            ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
            ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse))
            ->whereNull('material_usages.voided_at');

        $masukQuery = StockIn::query()
            ->select(
                DB::raw("'masuk' as type"),
                'stock_ins.date',
                'materials.code as material_code',
                'materials.name as material_name',
                'materials.unit as material_unit',
                DB::raw("'-' as blok_rumah"),
                DB::raw("'-' as cluster"),
                'stock_ins.notes as keterangan_pekerjaan',
                'suppliers.name as supplier_name',
                'suppliers.name as reference',
                'stock_ins.quantity',
                'stock_ins.unit_price',
                'stock_ins.total_cost',
                'users.name as user_name',
                'stock_ins.created_at as created_at'
            )
            ->join('materials', 'stock_ins.material_id', '=', 'materials.id')
            ->join('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
            ->join('users', 'stock_ins.user_id', '=', 'users.id')
            ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
            ->when($this->filterSupplier, fn ($q) => $q->where('stock_ins.supplier_id', $this->filterSupplier));

        if ($this->filterType === 'masuk') {
            $query = $masukQuery;
        } elseif ($this->filterType === 'keluar') {
            $query = $keluarQuery;
        } else {
            $unionQuery = $keluarQuery->unionAll($masukQuery);

            return collect(
                DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
                    ->mergeBindings($unionQuery->getQuery())
                    ->orderBy('date', $this->sortDirection)
                    ->orderBy('created_at', $this->sortDirection)
                    ->get()
            );
        }

        return collect(
            $query
                ->orderBy('date', $this->sortDirection)
                ->orderBy('created_at', $this->sortDirection)
                ->get()
        );
    }

    public function headings(): array
    {
        if ($this->filterType === 'keluar') {
            return [
                [
                    'TANGGAL',
                    'BULAN',
                    'MINGGU KE',
                    'TAHUN',
                    'ADMIN',
                    'PENGAMBIL',
                    'BLOK RUMAH',
                    'CLUSTER',
                    'KETERANGAN PEKERJAAN',
                    'KODE BARANG',
                    'NAMA BARANG',
                    'VOLUME',
                    'SATUAN',
                    'HARGA SATUAN',
                    'JUMLAH',
                    'TOKO/SUPPLIER',
                ]
            ];
        }

        return [
            ['Catatan Material - Sistem Logistik'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            [],
            [
                'No',
                'Tanggal',
                'Tipe',
                'Kode Barang',
                'Nama Material',
                'Satuan',
                'Referensi (Rumah/Supplier)',
                'Jumlah',
                'Harga Satuan',
                'Total Biaya',
                'Pencatat',
            ]
        ];
    }

    public function map($record): array
    {
        $dt = \Carbon\Carbon::parse($record->date);

        if ($this->filterType === 'keluar') {
            $monthNames = [
                1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
                5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
                9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
            ];
            $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
            $weekNum = $romans[$dt->weekOfMonth] ?? (string) $dt->weekOfMonth;

            return [
                $dt->format('d/m/Y'),
                $monthNames[$dt->month] ?? strtoupper($dt->format('F')),
                $weekNum,
                $dt->year,
                $record->user_name,
                $record->user_name, // Pengambil / Admin dicatat oleh
                $record->blok_rumah ?? '-',
                $record->cluster ?? '-',
                $record->keterangan_pekerjaan ?? '-',
                $record->material_code ?? '-',
                $record->material_name,
                (float) ($record->quantity ?? 0),
                $record->material_unit,
                (float) ($record->unit_price ?? 0),
                (float) ($record->total_cost ?? 0),
                $record->supplier_name ?? '-',
            ];
        }

        $this->rowNumber++;

        return [
            $this->rowNumber,
            $dt->format('d/m/Y'),
            $record->type === 'masuk' ? 'Barang Masuk' : 'Barang Keluar',
            $record->material_code ?? '-',
            $record->material_name,
            $record->material_unit,
            $record->reference,
            (float) ($record->quantity ?? 0),
            (float) ($record->unit_price ?? 0),
            (float) ($record->total_cost ?? 0),
            $record->user_name,
        ];
    }

    public function columnFormats(): array
    {
        if ($this->filterType === 'keluar') {
            return [
                'A' => '@',
                'L' => '#,##0.00',
                'N' => '"Rp "#,##0',
                'O' => '"Rp "#,##0',
            ];
        }

        return [
            'H' => '#,##0.00',
            'I' => '"Rp "#,##0',
            'J' => '"Rp "#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->filterType === 'keluar') {
            // Header Styling (Company Style Soft Steel Blue #8EAADB or #6C8EBF)
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '8EAADB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];

            $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(28);

            // Alignment
            $sheet->getStyle('A:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('J')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('L')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('M')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('N:O')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('P')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            return [];
        }

        // Styling metadata rows
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        // Header Styling (Dark Slate Grey #334155)
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

        $sheet->getStyle('A4:K4')->applyFromArray($headerStyle);

        // Alignment
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I:J')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();

                if ($this->filterType === 'keluar') {
                    // Auto grid borders for all data rows
                    $event->sheet->getStyle('A1:P' . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'D1D5DB'],
                            ],
                        ],
                    ]);

                    // Calculate Total
                    $totalCost = MaterialUsage::query()
                        ->join('materials', 'material_usages.material_id', '=', 'materials.id')
                        ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
                        ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse))
                        ->whereNull('material_usages.voided_at')
                        ->sum('material_usages.total_cost');

                    $event->sheet->append([
                        ['', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL', $totalCost, '']
                    ]);

                    $finalRow = $event->sheet->getHighestRow();

                    $event->sheet->getStyle('N' . $finalRow . ':O' . $finalRow)->applyFromArray([
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

                    $event->sheet->getStyle('O' . $finalRow)->getNumberFormat()->setFormatCode('"Rp "#,##0');
                    return;
                }

                // Calculate Total from DB directly
                $keluarTotal = MaterialUsage::query()
                    ->join('materials', 'material_usages.material_id', '=', 'materials.id')
                    ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
                    ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse))
                    ->whereNull('material_usages.voided_at')
                    ->when($this->filterType !== 'masuk', fn ($q) => $q, fn ($q) => $q->whereRaw('1=0'))
                    ->sum('material_usages.total_cost');

                $masukTotal = StockIn::query()
                    ->join('materials', 'stock_ins.material_id', '=', 'materials.id')
                    ->join('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
                    ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
                    ->when($this->filterSupplier, fn ($q) => $q->where('stock_ins.supplier_id', $this->filterSupplier))
                    ->when($this->filterType !== 'keluar', fn ($q) => $q, fn ($q) => $q->whereRaw('1=0'))
                    ->sum('stock_ins.total_cost');

                $totalCost = $keluarTotal + $masukTotal;

                // Add Footer Row
                $event->sheet->append([
                    [],
                    ['', '', '', '', '', '', '', '', 'Total Biaya', $totalCost]
                ]);

                $finalRow = $event->sheet->getHighestRow();

                // Styling the total row
                $event->sheet->getStyle('I' . $finalRow . ':J' . $finalRow)->applyFromArray([
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
                $event->sheet->getStyle('J' . $finalRow)->getNumberFormat()->setFormatCode('"Rp "#,##0');
            },
        ];
    }
}

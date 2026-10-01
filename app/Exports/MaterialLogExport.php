<?php

namespace App\Exports;

use App\Models\MaterialUsage;
use App\Models\StockIn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaterialLogExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    private $rowNumber = 0;

    public function __construct(
        private string $search = '',
        private string $filterType = '',
        private string $filterHouse = '',
        private string $filterSupplier = '',
        private string $sort = 'date_desc',
        private ?int $clusterScope = null
    ) {}

    private function sortParts(): array
    {
        $columns = [
            'date' => 'date',
            'type' => 'type',
            'admin' => 'user_name',
            'house' => 'blok_rumah',
            'notes' => 'keterangan_pekerjaan',
            'code' => 'material_code',
            'name' => 'material_name',
            'volume' => 'quantity',
            'unit' => 'material_unit',
            'unit_price' => 'unit_price',
            'total' => 'total_cost',
            'supplier' => 'supplier_name',
        ];

        if (in_array($this->sort, ['asc', 'desc'], true)) {
            return ['date', $this->sort, $columns['date']];
        }

        if (preg_match('/^(.+)_(asc|desc)$/', $this->sort, $matches)
            && array_key_exists($matches[1], $columns)) {
            return [$matches[1], $matches[2], $columns[$matches[1]]];
        }

        return ['date', 'desc', $columns['date']];
    }

    private function applySort($query)
    {
        [$field, $direction, $column] = $this->sortParts();
        $query->orderBy($column, $direction);

        return $field === 'date'
            ? $query->orderBy('created_at', $direction)
            : $query->orderByDesc('date')->orderByDesc('created_at');
    }

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
                'material_usages.notes as keterangan_pekerjaan',
                'suppliers.name as supplier_name',
                'houses.name as reference',
                'material_usages.quantity',
                'material_usages.unit_price_at_usage as unit_price',
                'material_usages.total_cost',
                'users.name as user_name',
                'material_usages.created_at as created_at',
                'material_usages.transaction_code as event_code'
            )
            ->join('materials', 'material_usages.material_id', '=', 'materials.id')
            ->leftJoin('suppliers', 'materials.supplier_id', '=', 'suppliers.id')
            ->join('houses', 'material_usages.house_id', '=', 'houses.id')
            ->join('users', 'material_usages.user_id', '=', 'users.id')
            ->when($this->clusterScope !== null, fn ($q) => $q->where('houses.cluster_id', $this->clusterScope))
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('materials.name', 'like', "%{$this->search}%")
                ->orWhere('material_usages.transaction_code', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse))
            ->whereNull('material_usages.voided_at');

        $masukQuery = StockIn::query()
            ->select(
                DB::raw("CASE WHEN stock_ins.entry_type = 'opening_balance' THEN 'saldo_awal' WHEN stock_ins.entry_type = 'transfer' THEN 'transfer_masuk' ELSE 'masuk' END as type"),
                'stock_ins.date',
                'materials.code as material_code',
                'materials.name as material_name',
                'materials.unit as material_unit',
                DB::raw("'-' as blok_rumah"),
                'stock_ins.notes as keterangan_pekerjaan',
                'suppliers.name as supplier_name',
                DB::raw("CASE WHEN stock_ins.entry_type = 'transfer' THEN 'Transfer gudang' ELSE COALESCE(suppliers.name, '-') END as reference"),
                'stock_ins.quantity',
                'stock_ins.unit_price',
                'stock_ins.total_cost',
                DB::raw("COALESCE(users.name, 'Tidak tercatat') as user_name"),
                'stock_ins.created_at as created_at',
                'stock_ins.entry_code as event_code'
            )
            ->join('materials', 'stock_ins.material_id', '=', 'materials.id')
            ->leftJoin('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'stock_ins.user_id', '=', 'users.id')
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('materials.name', 'like', "%{$this->search}%")
                ->orWhere('stock_ins.entry_code', 'like', "%{$this->search}%")))
            ->when($this->filterSupplier, fn ($q) => $q->where('stock_ins.supplier_id', $this->filterSupplier));

        if ($this->filterType === 'masuk') {
            $query = $masukQuery;
        } elseif ($this->filterType === 'keluar') {
            $query = $keluarQuery;
        } else {
            $unionQuery = $keluarQuery->unionAll($masukQuery);

            $query = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
                ->mergeBindings($unionQuery->getQuery());

            return collect($this->applySort($query)->get());
        }

        return collect($this->applySort($query)->get());
    }

    public function headings(): array
    {
        if ($this->filterType === 'keluar') {
            return [
                [
                    'TANGGAL',
                    'BULAN',
                    'TAHUN',
                    'PENANGGUNG JAWAB',
                    'BLOK RUMAH',
                    'KETERANGAN PEKERJAAN',
                    'KODE BARANG',
                    'NAMA BARANG',
                    'VOLUME',
                    'SATUAN',
                    'HARGA SATUAN',
                    'JUMLAH',
                    'TOKO/SUPPLIER',
                    'KODE PENGIRIMAN / TRANSAKSI',
                    'DICATAT PADA',
                ],
            ];
        }

        return [
            ['Catatan Material - Sistem Logistik'],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
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
                'Penanggung Jawab',
                'Kode Penerimaan / Transaksi',
                'Dicatat pada',
            ],
        ];
    }

    public function map($record): array
    {
        $dt = Carbon::parse($record->date);

        if ($this->filterType === 'keluar') {
            $monthNames = [
                1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
                5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
                9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
            ];

            return [
                $dt->format('d/m/Y'),
                $monthNames[$dt->month] ?? strtoupper($dt->format('F')),
                $dt->year,
                $record->user_name,
                $record->blok_rumah ?? '-',
                $record->keterangan_pekerjaan ?? '-',
                $record->material_code ?? '-',
                $record->material_name,
                (float) ($record->quantity ?? 0),
                $record->material_unit,
                (float) ($record->unit_price ?? 0),
                (float) ($record->total_cost ?? 0),
                $record->supplier_name ?? '-',
                $record->event_code ?? '-',
                $record->created_at ? Carbon::parse($record->created_at)->format('d/m/Y H:i') : '-',
            ];
        }

        $this->rowNumber++;

        return [
            $this->rowNumber,
            $dt->format('d/m/Y'),
            match ($record->type) {
                'saldo_awal' => 'Saldo Warisan',
                'transfer_masuk' => 'Transfer Gudang',
                'masuk' => 'Barang Masuk',
                default => 'Barang Keluar',
            },
            $record->material_code ?? '-',
            $record->material_name,
            $record->material_unit,
            $record->reference,
            (float) ($record->quantity ?? 0),
            (float) ($record->unit_price ?? 0),
            (float) ($record->total_cost ?? 0),
            $record->user_name,
            $record->event_code ?? '-',
            $record->created_at ? Carbon::parse($record->created_at)->format('d/m/Y H:i') : '-',
        ];
    }

    public function columnFormats(): array
    {
        if ($this->filterType === 'keluar') {
            return [
                'A' => '@',
                'I' => '#,##0.00',
                'K' => '[$Rp-421] #,##0.00',
                'L' => '[$Rp-421] #,##0.00',
            ];
        }

        return [
            'H' => '#,##0.00',
            'I' => '[$Rp-421] #,##0.00',
            'J' => '[$Rp-421] #,##0.00',
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

            $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(28);

            // Alignment
            $sheet->getStyle('A:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('J')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K:L')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('M')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

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

        $sheet->getStyle('A4:M4')->applyFromArray($headerStyle);

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
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();

                if ($this->filterType === 'keluar') {
                    // Auto grid borders for all data rows
                    $event->sheet->getStyle('A1:O'.$lastRow)->applyFromArray([
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
                        ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                            ->where('materials.name', 'like', "%{$this->search}%")
                            ->orWhere('material_usages.transaction_code', 'like', "%{$this->search}%")))
                        ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse))
                        ->whereNull('material_usages.voided_at')
                        ->sum('material_usages.total_cost');

                    $event->sheet->append([
                        ['', '', '', '', '', '', '', '', '', '', 'TOTAL', $totalCost],
                    ]);

                    $finalRow = $event->sheet->getHighestRow();

                    $event->sheet->getStyle('K'.$finalRow.':L'.$finalRow)->applyFromArray([
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

                    $event->sheet->getStyle('L'.$finalRow)->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');

                    return;
                }

                // Calculate Total from DB directly
                $keluarTotal = MaterialUsage::query()
                    ->join('materials', 'material_usages.material_id', '=', 'materials.id')
                    ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                        ->where('materials.name', 'like', "%{$this->search}%")
                        ->orWhere('material_usages.transaction_code', 'like', "%{$this->search}%")))
                    ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse))
                    ->whereNull('material_usages.voided_at')
                    ->when($this->filterType !== 'masuk', fn ($q) => $q, fn ($q) => $q->whereRaw('1=0'))
                    ->sum('material_usages.total_cost');

                $masukTotal = StockIn::query()
                    ->join('materials', 'stock_ins.material_id', '=', 'materials.id')
                    ->leftJoin('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
                    ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
                    ->when($this->filterSupplier, fn ($q) => $q->where('stock_ins.supplier_id', $this->filterSupplier))
                    ->when($this->filterType !== 'keluar', fn ($q) => $q, fn ($q) => $q->whereRaw('1=0'))
                    ->sum('stock_ins.total_cost');

                $totalCost = $keluarTotal + $masukTotal;

                // Add Footer Row
                $event->sheet->append([
                    [],
                    ['', '', '', '', '', '', '', '', 'Total Biaya', $totalCost],
                ]);

                $finalRow = $event->sheet->getHighestRow();

                // Styling the total row
                $event->sheet->getStyle('I'.$finalRow.':J'.$finalRow)->applyFromArray([
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
                $event->sheet->getStyle('J'.$finalRow)->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');
            },
        ];
    }
}

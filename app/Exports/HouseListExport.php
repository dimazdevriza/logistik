<?php

namespace App\Exports;

use App\Models\House;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HouseListExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, WithEvents, ShouldAutoSize, WithTitle
{
    use Exportable;

    private $rowNumber = 0;
    private int $year;

    public function __construct(
        private ?string $search = null,
        private ?string $filterStatus = null,
        ?int $year = null,
        private ?string $filterCluster = null,
        private ?int $clusterScope = null,
        private ?string $sheetTitle = null,
        private ?string $reportTitle = null,
    ) {
        $this->year = $year ?: (int) now()->year;
    }

    public function title(): string
    {
        return $this->sheetTitle ?? 'Biaya Rumah ' . $this->year;
    }

    public function query()
    {
        $query = House::query()
            ->with('cluster')
            ->withSum(['materialUsages' => fn ($q) => $q->whereNull('voided_at')], 'total_cost')
            ->withCount(['materialUsages' => fn ($q) => $q->whereNull('voided_at')]);

        for ($m = 1; $m <= 12; $m++) {
            $query->withSum([
                'materialUsages as month_' . $m . '_cost' => function ($q) use ($m) {
                    $q->whereNull('voided_at')
                        ->whereYear('usage_date', $this->year)
                        ->whereMonth('usage_date', $m);
                }
            ], 'total_cost');
        }

        return $query
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCluster, fn ($q) => $q->where('cluster_id', $this->filterCluster))
            ->when($this->clusterScope !== null, fn ($q) => $q->where('cluster_id', $this->clusterScope))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            [$this->reportTitle ?? 'D\'ROYAL VILLAGE - LAPORAN MONITORING BIAYA RUMAH PER BULAN (TAHUN ' . $this->year . ')'],
            ['Diekspor pada: ' . now()->format('d F Y H:i')],
            [],
            [
                'No.',
                'Kode Rumah',
                'Nama / Blok',
                'Cluster',
                'Tipe',
                'Status',
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
                'Total Tahun ' . $this->year,
                'Total Keseluruhan'
            ]
        ];
    }

    public function map($house): array
    {
        $this->rowNumber++;

        $yearTotal = 0;
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $val = (float) ($house->{'month_' . $m . '_cost'} ?? 0);
            $months[] = $val;
            $yearTotal += $val;
        }

        return array_merge(
            [
                $this->rowNumber,
                $house->house_code ?? '-',
                $house->name,
                $house->cluster?->name ?? 'Tanpa Cluster',
                $house->type,
                ucfirst($house->status),
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
            'G' => '[$Rp-421] #,##0.00',
            'H' => '[$Rp-421] #,##0.00',
            'I' => '[$Rp-421] #,##0.00',
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
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:T1');
        $sheet->mergeCells('A2:T2');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '136928']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F9B3A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A4:T{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Alignment for columns
                $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B5:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F5:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G5:T{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Add summary footer row
                $totalRow = $highestRow + 1;
                $sheet->setCellValue("F{$totalRow}", 'TOTAL:');
                $sheet->setCellValue("G{$totalRow}", "=SUM(G5:G{$highestRow})");
                $sheet->setCellValue("H{$totalRow}", "=SUM(H5:H{$highestRow})");
                $sheet->setCellValue("I{$totalRow}", "=SUM(I5:I{$highestRow})");
                $sheet->setCellValue("J{$totalRow}", "=SUM(J5:J{$highestRow})");
                $sheet->setCellValue("K{$totalRow}", "=SUM(K5:K{$highestRow})");
                $sheet->setCellValue("L{$totalRow}", "=SUM(L5:L{$highestRow})");
                $sheet->setCellValue("M{$totalRow}", "=SUM(M5:M{$highestRow})");
                $sheet->setCellValue("N{$totalRow}", "=SUM(N5:N{$highestRow})");
                $sheet->setCellValue("O{$totalRow}", "=SUM(O5:O{$highestRow})");
                $sheet->setCellValue("P{$totalRow}", "=SUM(P5:P{$highestRow})");
                $sheet->setCellValue("Q{$totalRow}", "=SUM(Q5:Q{$highestRow})");
                $sheet->setCellValue("R{$totalRow}", "=SUM(R5:R{$highestRow})");
                $sheet->setCellValue("S{$totalRow}", "=SUM(S5:S{$highestRow})");
                $sheet->setCellValue("T{$totalRow}", "=SUM(T5:T{$highestRow})");

                $sheet->getStyle("F{$totalRow}:T{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle("F{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G{$totalRow}:T{$totalRow}")->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');
            },
        ];
    }
}

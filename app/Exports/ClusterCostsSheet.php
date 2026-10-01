<?php

namespace App\Exports;

use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\House;
use App\Models\MaterialUsage;
use App\Models\ToolUsage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClusterCostsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private array $sectionRows = [];

    private array $currencyCells = [];

    private int $lastColumnIndex = 1;

    public function __construct(
        private Cluster $cluster,
        private House $house,
        private string $sheetTitle,
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function columnWidths(): array
    {
        $widths = [15, 26, 12, 14, 16, 16, 24, 3, 15, 26, 16, 12, 16, 15, 24, 3, 15, 24, 20, 16, 24, 3, 16, 26, 20, 14, 14, 14, 12, 16, 14, 24];

        $columns = [];
        foreach ($widths as $index => $width) {
            $columns[Coordinate::stringFromColumnIndex($index + 1)] = $width;
        }

        return $columns;
    }

    public function array(): array
    {
        $this->sectionRows = [];
        $this->currencyCells = [];

        $rows = [
            ['Laporan Biaya Rumah: '.$this->house->name],
            ['Diekspor pada: '.now()->format('d/m/Y H:i')],
            [' '],
        ];

        $sections = [[
            'title' => 'Material',
            'headings' => ['Tanggal', 'Material', 'Jumlah', 'Satuan', 'Harga satuan', 'Total biaya', 'Catatan'],
            'items' => $this->materialUsages(),
            'map' => fn (MaterialUsage $usage): array => [
                $usage->usage_date?->format('d/m/Y') ?? '-',
                $usage->material?->name ?? 'Material tidak tersedia',
                (float) $usage->quantity,
                $usage->material?->unit ?? '-',
                (float) $usage->unit_price_at_usage,
                (float) $usage->total_cost,
                $usage->notes ?? '',
            ],
            'currencyColumns' => [5, 6],
        ]];

        $sections[] = [
            'title' => 'Peminjaman alat',
            'headings' => ['Tanggal pinjam', 'Alat', 'Kode alat', 'Jumlah', 'Status', 'Tanggal kembali', 'Catatan'],
            'items' => ToolUsage::query()
                ->with('tool:id,name,code')
                ->where('house_id', $this->house->id)
                ->whereNull('voided_at')
                ->orderBy('checkout_date')
                ->orderBy('id')
                ->get(),
            'map' => fn (ToolUsage $usage): array => [
                $usage->checkout_date?->format('d/m/Y') ?? '-',
                $usage->tool?->name ?? 'Alat tidak tersedia',
                $usage->tool?->code ?? '-',
                (int) $usage->quantity,
                $usage->return_date ? 'Dikembalikan' : 'Dipinjam',
                $usage->return_date?->format('d/m/Y') ?? '-',
                $usage->notes ?? '',
            ],
            'currencyColumns' => [],
        ];
        $sections[] = [
            'title' => 'Jasa vendor',
            'headings' => ['Tanggal layanan', 'Pekerjaan', 'Vendor', 'Total biaya', 'Catatan'],
            'items' => $this->expenses(['vendor_service']),
            'map' => fn (ClusterExpense $expense): array => [
                $expense->start_date?->format('d/m/Y') ?? '-',
                $expense->description,
                $expense->vendor ?? '-',
                (float) $expense->amount,
                $expense->notes ?? '',
            ],
            'currencyColumns' => [4],
        ];
        $sections[] = [
            'title' => 'Sewa alat vendor',
            'headings' => ['Jenis', 'Alat', 'Vendor', 'Mulai', 'Jatuh tempo', 'Off-hire', 'Jumlah unit', 'Biaya sewa', 'Status', 'Catatan'],
            'items' => $this->expenses(['rental', 'rental_extension']),
            'map' => fn (ClusterExpense $expense): array => [
                $expense->type === 'rental_extension' ? 'Perpanjangan sewa' : 'Sewa alat',
                $expense->description,
                $expense->vendor ?? '-',
                $expense->start_date?->format('d/m/Y') ?? '-',
                $expense->due_date?->format('d/m/Y') ?? '-',
                $expense->off_hire_date?->format('d/m/Y') ?? '-',
                $expense->quantity === null ? '' : (float) $expense->quantity,
                (float) $expense->amount,
                $expense->off_hire_date ? 'Selesai' : ($expense->status === 'active' ? 'Aktif' : ucfirst($expense->status)),
                $expense->notes ?? '',
            ],
            'currencyColumns' => [8],
        ];

        $startColumn = 1;
        foreach ($sections as &$section) {
            $section['startColumn'] = $startColumn;
            $startColumn += count($section['headings']) + 1;
        }
        unset($section);

        $this->lastColumnIndex = $startColumn - 2;
        $this->appendSideBySideSections($rows, ...$sections);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $metadataLastColumn = Coordinate::stringFromColumnIndex($this->lastColumnIndex);
        $metadataLastRow = 2;

        for ($row = 1; $row <= $metadataLastRow; $row++) {
            $sheet->mergeCells("A{$row}:{$metadataLastColumn}{$row}");
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('68736C');

        foreach ($this->sectionRows as [$titleRow, $headingRow, $firstColumn, $lastColumn]) {
            $firstColumnName = Coordinate::stringFromColumnIndex($firstColumn);
            $lastColumnName = Coordinate::stringFromColumnIndex($lastColumn);
            $sheet->mergeCells("{$firstColumnName}{$titleRow}:{$lastColumnName}{$titleRow}");
            $sheet->getStyle("{$firstColumnName}{$titleRow}:{$lastColumnName}{$titleRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '176B3A']],
            ]);
            $sheet->getStyle("{$firstColumnName}{$headingRow}:{$lastColumnName}{$headingRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '21362A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E4F0E7']],
            ]);
            $sheet->getStyle("{$firstColumnName}{$headingRow}:{$lastColumnName}{$headingRow}")->getAlignment()->setWrapText(true);
        }

        foreach ($this->currencyCells as $cell) {
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('[$Rp-421] #,##0.00');
        }

        return [];
    }

    private function materialUsages(): Collection
    {
        return MaterialUsage::query()
            ->with(['house:id,name', 'material:id,name,unit'])
            ->whereNull('voided_at')
            ->where('house_id', $this->house->id)
            ->orderBy('usage_date')
            ->orderBy('id')
            ->get();
    }

    private function expenses(array $types): Collection
    {
        return ClusterExpense::query()
            ->with(['house:id,name', 'houses:id,name'])
            ->where('cluster_id', $this->cluster->id)
            ->whereIn('type', $types)
            ->where(fn ($expenses) => $expenses
                ->where('house_id', $this->house->id)
                ->orWhereHas('houses', fn ($houses) => $houses->whereKey($this->house->id)))
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    private function appendSideBySideSections(array &$rows, array ...$sections): void
    {
        $lastColumn = max(array_map(
            fn ($section): int => $section['startColumn'] + count($section['headings']) - 1,
            $sections,
        ));

        $titleRow = count($rows) + 1;
        $headingRow = $titleRow + 1;
        $titleValues = array_fill(0, $lastColumn, null);
        $headingValues = array_fill(0, $lastColumn, null);

        foreach ($sections as $section) {
            $startColumn = $section['startColumn'];
            $titleValues[$startColumn - 1] = $section['title'];
            foreach ($section['headings'] as $offset => $heading) {
                $headingValues[$startColumn - 1 + $offset] = $heading;
            }

            $this->sectionRows[] = [
                $titleRow,
                $headingRow,
                $startColumn,
                $startColumn + count($section['headings']) - 1,
            ];
        }

        $rows[] = $titleValues;
        $rows[] = $headingValues;

        $rowCount = max(1, ...array_map(fn ($section): int => $section['items']->count(), $sections));
        for ($index = 0; $index < $rowCount; $index++) {
            $row = array_fill(0, $lastColumn, null);
            foreach ($sections as $section) {
                $startColumn = $section['startColumn'];
                if ($section['items']->isEmpty()) {
                    if ($index === 0) {
                        $row[$startColumn - 1] = 'Tidak ada data';
                    }

                    continue;
                }

                if ($index >= $section['items']->count()) {
                    continue;
                }

                $values = $section['map']($section['items'][$index]);
                foreach ($values as $offset => $value) {
                    $row[$startColumn - 1 + $offset] = $value;
                }

                $rowNumber = count($rows) + 1;
                foreach ($section['currencyColumns'] as $column) {
                    $value = $values[$column - 1] ?? null;
                    if (is_numeric($value)) {
                        $this->currencyCells[] = Coordinate::stringFromColumnIndex($startColumn + $column - 1).$rowNumber;
                    }
                }
            }

            $rows[] = $row;
        }

        $rows[] = [' '];
    }
}

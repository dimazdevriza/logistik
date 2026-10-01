<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ToolLogExport implements WithColumnFormatting, FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    private $rowNumber = 0;

    public function columnFormats(): array
    {
        return [
            'Q' => '[$Rp-421] #,##0.00',
            'R' => '[$Rp-421] #,##0.00',
        ];
    }

    public function __construct(private Builder $recordsQuery) {}

    public function query(): Builder
    {
        return clone $this->recordsQuery;
    }

    public function headings(): array
    {
        return [
            ['Catatan Alat - Sistem Logistik'],
            ['Diekspor pada: '.now()->format('d F Y H:i')],
            [], // Empty row
            [
                'No',
                'Tanggal',
                'Rumah / Gudang',
                'Nama Alat',
                'Kode Alat',
                'Jumlah',
                'Status',
                'Tanggal Kembali',
                'Penanggung Jawab',
                'Catatan',
                'Jenis',
                'Kode Transaksi',
                'Kode Pengiriman',
                'Kode Masuk Asal',
                'Gudang Asal',
                'Satuan',
                'Harga Satuan',
                'Total',
                'Vendor',
                'Jatuh Tempo Sewa',
                'Kode Transaksi Induk',
                'Waktu Diterima',
                'Dicatat pada',
            ],
        ];
    }

    public function map($record): array
    {
        $type = match ($record->type) {
            'masuk' => 'Masuk',
            'saldo_awal' => 'Saldo awal',
            'keluar' => 'Peminjaman',
            'kembali' => 'Pengembalian',
            'transfer' => 'Transfer antargudang',
            'rental' => 'Sewa vendor',
            'rental_extension' => 'Perpanjangan sewa',
            'rental_return' => 'Kembali ke vendor',
            default => $record->type,
        };
        $status = $record->voided_at ? 'Dibatalkan' : ($record->rental_status
            ?? ($record->type === 'kembali' ? 'Dikembalikan' : ($record->type === 'keluar' ? ($record->return_date ? 'Dikembalikan' : 'Dipinjam') : '-')));

        return [
            ++$this->rowNumber,
            $record->date ? Carbon::parse($record->date)->format('d/m/Y') : '-',
            $record->house_name,
            $record->item_name,
            $record->item_code,
            (float) $record->volume,
            $status,
            $record->return_date ? Carbon::parse($record->return_date)->format('d/m/Y') : '-',
            $record->admin_name ?? 'Tidak tercatat',
            $record->job_notes ?? '-',
            $type,
            $record->transaction_code,
            $record->dispatch_code,
            $record->source_entry_code,
            $record->source_warehouse_name,
            $record->unit,
            (float) $record->unit_price,
            (float) $record->total_cost,
            $record->vendor_name,
            $record->rental_due_date ? Carbon::parse($record->rental_due_date)->format('d/m/Y') : '-',
            $record->parent_transaction_code,
            $record->received_at,
            $record->created_at ? Carbon::parse($record->created_at)->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
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

        $sheet->getStyle('A4:W4')->applyFromArray($headerStyle);

        // Alignment
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

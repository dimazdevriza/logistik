<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// 1. Material Sample Import File
$spreadsheetMat = new Spreadsheet();
$sheetMat = $spreadsheetMat->getActiveSheet();
$sheetMat->setTitle('Material Test');

$headersMat = [
    'Nama Material', 'Kategori', 'Satuan', 'Harga Satuan', 'Sisa Stok', 'Supplier', 'Jenis', 'Unit Rumah', 'Tanggal', 'Catatan'
];
$sheetMat->fromArray([$headersMat], null, 'A1');

$dataMat = [
    ['Bata Merah Super', 'Bahan Bangunan', 'buah', 1200, 5000, 'TB. Maju Bersama', 'masuk', '', '2026-08-18', 'Restock awal impor'],
    ['Semen Tiga Roda 50kg', 'Semen', 'sak', 78000, 100, 'PT Semen Indonesia', 'masuk', '', '2026-08-18', 'Pembelian batch 1'],
    ['Cat Tembok Nippon Paint 20kg', 'Cat & Finishing', 'galon', 450000, 25, 'Toko Cat Gemilang', 'keluar', 'Blok A-01', '2026-08-18', 'Pengecatan dinding luar'],
    ['Pipa PVC 3 Inch Wavin', 'Sanitari & Pipa', 'batang', 95000, 40, 'TB. Maju Bersama', 'keluar', 'Blok B-04', '2026-08-18', 'Instalasi saluran air bersih'],
];
$sheetMat->fromArray($dataMat, null, 'A2');

$headerStyleGreen = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F9B3A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheetMat->getStyle('A1:J1')->applyFromArray($headerStyleGreen);
$sheetMat->getRowDimension(1)->setRowHeight(26);

foreach (range('A', 'J') as $col) {
    $sheetMat->getColumnDimension($col)->setAutoSize(true);
}

$writerMat = new Xlsx($spreadsheetMat);
$writerMat->save('public/sample_material_import.xlsx');


// 2. Tool Sample Import File
$spreadsheetTool = new Spreadsheet();
$sheetTool = $spreadsheetTool->getActiveSheet();
$sheetTool->setTitle('Tool Test');

$headersTool = [
    'Kode', 'Nama Alat', 'Kategori', 'Kondisi', 'Total Qty', 'Harga Beli', 'Jenis', 'Unit Rumah', 'Tanggal', 'Catatan'
];
$sheetTool->fromArray([$headersTool], null, 'A1');

$dataTool = [
    ['ALT-GEN-01', 'Genset Silent 5000W', 'Mesin & Listrik', 'baik', 3, 12500000, 'pinjam', 'Blok A-01', '2026-08-18', 'Peminjaman sumber listrik cor'],
    ['ALT-MOL-02', 'Molen Beton 350L Heavy', 'Alat Berat', 'baik', 2, 16000000, 'pinjam', 'Blok C-02', '2026-08-18', 'Pengadukan beton pondasi'],
    ['ALT-LDR-01', 'Tangga Aluminium 6 Meter', 'Peralatan Lapangan', 'baik', 5, 1200000, 'kembali', 'Blok B-04', '2026-08-18', 'Pengembalian setelah instalasi talang'],
];
$sheetTool->fromArray($dataTool, null, 'A2');

$sheetTool->getStyle('A1:J1')->applyFromArray($headerStyleGreen);
$sheetTool->getRowDimension(1)->setRowHeight(26);

foreach (range('A', 'J') as $col) {
    $sheetTool->getColumnDimension($col)->setAutoSize(true);
}

$writerTool = new Xlsx($spreadsheetTool);
$writerTool->save('public/sample_tool_import.xlsx');


// 3. House Multi-Sheet Sample Import File (Houses, Material Usages, Tool Usages)
$spreadsheetHouse = new Spreadsheet();

// Sheet 1: Unit Rumah
$sheet1 = $spreadsheetHouse->getActiveSheet();
$sheet1->setTitle('Daftar Unit Rumah');

$headersSheet1 = [
    'Kode Rumah', 'Nama / Blok', 'Tipe', 'Status', 'Mulai', 'Target Selesai'
];
$sheet1->fromArray([$headersSheet1], null, 'A1');

$dataSheet1 = [
    ['2026-D01', 'Blok D-01', 'Tipe 36/72', 'perencanaan', '2026-09-01', '2027-02-28'],
    ['2026-D02', 'Blok D-02', 'Tipe 36/72', 'perencanaan', '2026-09-01', '2027-02-28'],
    ['2026-D03', 'Blok D-03', 'Tipe 45/90', 'pembangunan', '2026-08-01', '2027-01-31'],
    ['2026-E01', 'Blok E-01', 'Tipe 54/120', 'pembangunan', '2026-07-15', '2026-12-31'],
    ['2026-E02', 'Blok E-02', 'Tipe 70/150', 'selesai', '2025-10-01', '2026-06-30'],
];
$sheet1->fromArray($dataSheet1, null, 'A2');
$sheet1->getStyle('A1:F1')->applyFromArray($headerStyleGreen);
$sheet1->getRowDimension(1)->setRowHeight(26);

foreach (range('A', 'F') as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}

// Sheet 2: Pemakaian Material
$sheet2 = $spreadsheetHouse->createSheet();
$sheet2->setTitle('Pemakaian Material');

$headersSheet2 = [
    'Unit Rumah', 'Nama Material', 'Qty', 'Satuan', 'Harga Satuan', 'Kategori', 'Tanggal', 'Peruntukkan'
];
$sheet2->fromArray([$headersSheet2], null, 'A1');

$dataSheet2 = [
    ['Blok D-03', 'Semen Tiga Roda 50kg', 25, 'sak', 78000, 'Semen & Pasir', '2026-08-15', 'Pengecoran Pondasi & Sloof'],
    ['Blok D-03', 'Bata Merah Super', 1500, 'buah', 1200, 'Bahan Bangunan', '2026-08-16', 'Pekerjaan Dinding Lantai 1'],
    ['Blok E-01', 'Cat Tembok Nippon Paint 20kg', 4, 'galon', 450000, 'Cat & Finishing', '2026-08-18', 'Pengecatan Ruang Utama'],
    ['Blok E-01', 'Pipa PVC 3 Inch Wavin', 6, 'batang', 95000, 'Sanitari & Pipa', '2026-08-18', 'Instalasi Saluran Air Bersih'],
];
$sheet2->fromArray($dataSheet2, null, 'A2');

$headerStyleBlue = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6FA5']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheet2->getStyle('A1:H1')->applyFromArray($headerStyleBlue);
$sheet2->getRowDimension(1)->setRowHeight(26);

foreach (range('A', 'H') as $col) {
    $sheet2->getColumnDimension($col)->setAutoSize(true);
}

// Sheet 3: Peminjaman Alat
$sheet3 = $spreadsheetHouse->createSheet();
$sheet3->setTitle('Peminjaman Alat');

$headersSheet3 = [
    'Unit Rumah', 'Kode Alat', 'Nama Alat', 'Qty', 'Status', 'Tanggal Pinjam', 'Tanggal Kembali', 'Peruntukkan'
];
$sheet3->fromArray([$headersSheet3], null, 'A1');

$dataSheet3 = [
    ['Blok D-03', 'ALT-MOL-02', 'Molen Beton 350L Heavy', 1, 'dipinjam', '2026-08-15', '', 'Pengadukan Beton Pondasi'],
    ['Blok E-01', 'ALT-GEN-01', 'Genset Silent 5000W', 1, 'kembali', '2026-08-10', '2026-08-18', 'Sumber Daya Listrik Lapangan'],
    ['Blok E-01', 'ALT-LDR-01', 'Tangga Aluminium 6 Meter', 2, 'dipinjam', '2026-08-18', '', 'Pemasangan Talang & Plafon'],
];
$sheet3->fromArray($dataSheet3, null, 'A2');

$headerStyleOrange = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C26D00']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheet3->getStyle('A1:H1')->applyFromArray($headerStyleOrange);
$sheet3->getRowDimension(1)->setRowHeight(26);

foreach (range('A', 'H') as $col) {
    $sheet3->getColumnDimension($col)->setAutoSize(true);
}

// Set active sheet back to first sheet
$spreadsheetHouse->setActiveSheetIndex(0);

$writerHouse = new Xlsx($spreadsheetHouse);
$writerHouse->save('public/sample_house_import.xlsx');

echo "SUCCESS ALL GENERATED";

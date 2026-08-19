<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

foreach (range('A', 'J') as $col) {
    $sheetTool->getColumnDimension($col)->setAutoSize(true);
}

$writerTool = new Xlsx($spreadsheetTool);
$writerTool->save('public/sample_tool_import.xlsx');

echo "SUCCESS";

<?php

namespace Tests\Feature;

use App\Imports\HouseImport;
use App\Imports\MaterialImport;
use App\Imports\ToolImport;
use App\Livewire\Logistik\Houses;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\Tools;
use App\Models\House;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelImportFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_material_import_parses_materials_and_transaction_logs()
    {
        Material::factory()->create(['name' => 'Besi Beton 12mm Import', 'stock' => 15]);
        House::factory()->create(['name' => 'Blok X-01']);
        $rows = collect([
            [
                'nama_material' => 'Semen Portland Import Test',
                'kategori' => 'Semen',
                'satuan' => 'sak',
                'harga_satuan' => 85000,
                'sisa_stok' => 100,
                'supplier' => 'PT Semen Maju',
                'jenis' => 'masuk',
                'catatan' => 'Restock Awal Import',
                'tanggal' => '2026-08-18',
            ],
            [
                'nama_material' => 'Besi Beton 12mm Import',
                'kategori' => 'Baja & Besi',
                'satuan' => 'batang',
                'harga_satuan' => 120000,
                'sisa_stok' => 15,
                'unit_rumah' => 'Blok X-01',
                'jenis' => 'keluar',
                'catatan' => 'Alokasi Pengecoran Atap',
                'tanggal' => '2026-08-18',
            ],
        ]);

        $import = new MaterialImport;
        $import->collection($rows);

        $this->assertEquals(1, $import->materialsImported);
        $this->assertEquals(2, $import->transactionsImported);

        $this->assertDatabaseHas('materials', ['name' => 'Semen Portland Import Test', 'unit' => 'sak']);
        $this->assertDatabaseHas('materials', ['name' => 'Semen Portland Import Test', 'stock' => 100]);
        $this->assertDatabaseHas('categories', ['name' => 'Semen']);
        $this->assertDatabaseHas('stock_ins', ['notes' => 'Restock Awal Import']);

        $this->assertDatabaseHas('materials', ['name' => 'Besi Beton 12mm Import']);
        $this->assertDatabaseHas('houses', ['name' => 'Blok X-01']);
        $this->assertDatabaseHas('material_usages', ['notes' => 'Alokasi Pengecoran Atap']);
    }

    public function test_tool_import_parses_tools_and_checkout_return_logs()
    {
        House::factory()->create(['name' => 'Blok Y-02']);
        $rows = collect([
            [
                'kode' => 'ALT-TST-01',
                'nama_alat' => 'Pompa Air Submersible Test',
                'kategori' => 'Pompa & Mesin',
                'kondisi' => 'baik',
                'total_qty' => 5,
                'tersedia' => 5,
                'harga_beli' => 2500000,
                'jenis' => 'pinjam',
                'unit_rumah' => 'Blok Y-02',
                'catatan' => 'Peminjaman Pengeringan Pondasi',
                'tanggal' => '2026-08-18',
            ],
        ]);

        $import = new ToolImport;
        $import->collection($rows);

        $this->assertEquals(1, $import->toolsImported);
        $this->assertEquals(1, $import->transactionsImported);

        $this->assertDatabaseHas('tools', ['code' => 'ALT-TST-01', 'name' => 'Pompa Air Submersible Test']);
        $openingTool = Tool::where('code', 'ALT-TST-01')->sole();
        $this->assertSame('opening_balance', $openingTool->entry_type);
        $this->assertStringStartsWith('ALT-OPEN-', $openingTool->entry_code);
        $this->assertNull($openingTool->received_at);
        $this->assertDatabaseHas('houses', ['name' => 'Blok Y-02']);
        $this->assertDatabaseHas('tool_usages', ['notes' => 'Peminjaman Pengeringan Pondasi']);
    }

    public function test_material_import_parses_m_keluar_format()
    {
        House::factory()->create(['name' => 'F-01']);
        Material::factory()->create([
            'name' => 'Alkali Jotun Jotashield', 'code' => 'BL509', 'stock' => 1, 'unit' => 'Pail',
        ]);
        $rows = collect([
            [
                0 => 46052,
                'bulan' => 'Januari',
                'tahun' => 2026,
                'admin' => 'UUL',
                'pengambil' => 'AGUS',
                'blok_rumah' => 'F-01',
                'keterangan_pekerjaan' => 'PEK.PLAFON',
                'kode_barang' => 'BL509',
                'nama_barang' => '=IFERROR(VLOOKUP(I3,Data_Sumber,2,0),"")',
                'volume' => 1,
                'satuan' => 'Pail',
                'harga_satuan' => 'Rp 1.091.000',
                'jumlah' => 'Rp 1.091.000',
                'toko_supplier' => 'DONA TIN TING',
            ],
            [
                'kode_barang' => '*DI ISI',
                'nama_barang' => '*LINK',
            ],
        ]);

        $this->assertSame(28750.0, MaterialImport::parseNumber('Rp 28.750'));
        $this->assertSame(14375.0, MaterialImport::parseNumber('Rp 14.375,00'));
        $this->assertSame(1091000.0, MaterialImport::parseNumber('1.091.000'));

        $import = new MaterialImport;
        $import->collection($rows);

        $this->assertEquals(1, $import->successfulRows);
        $this->assertEquals(1, $import->skippedRows);
        $this->assertEquals(0, $import->materialsImported);
        $this->assertEquals(1, $import->transactionsImported);

        $this->assertDatabaseHas('materials', [
            'code' => 'BL509',
            'name' => 'Alkali Jotun Jotashield',
            'unit' => 'Pail',
        ]);
        $this->assertDatabaseHas('houses', ['name' => 'F-01']);
        $this->assertDatabaseHas('material_usages', [
            'quantity' => 1,
            'unit_price_at_usage' => 1091000,
            'notes' => 'PEK.PLAFON',
            'taken_by' => 'AGUS',
            'usage_date' => '2026-01-30 00:00:00',
        ]);
    }

    public function test_material_import_parses_multi_sheet_with_barang_masuk_and_keluar()
    {
        $cluster = \App\Models\Cluster::firstOrCreate(['name' => 'VILLAGIO']);
        $warehouse = Warehouse::firstOrFail();
        $warehouse->update(['name' => 'Gudang Villagio']);

        $houseB3 = House::factory()->create(['name' => 'Blok B3', 'cluster_id' => $cluster->id]);
        $houseA1 = House::factory()->create(['name' => 'Blok A1', 'cluster_id' => $cluster->id]);
        $houseInfra = House::factory()->create([
            'name' => 'Infrastruktur & Fasilitas Umum',
            'house_code' => 'VIL-INFRA',
            'category' => 'public_work',
            'cluster_id' => $cluster->id,
        ]);

        $matPaku = Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'PAKU BALOK 3"',
            'code' => 'BL42',
            'unit' => 'KG',
            'unit_price' => 14375,
            'stock' => 10,
        ]);
        $matSemen = Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'SEMEN GRESIK 50KG',
            'code' => 'BL01',
            'unit' => 'SAK',
            'unit_price' => 68000,
            'stock' => 50,
        ]);
        $matPipa = Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'PIPA PVC 3 INCH D',
            'code' => 'BL12',
            'unit' => 'BTG',
            'unit_price' => 85000,
            'stock' => 20,
        ]);

        $filePath = public_path('sample_material_import.xlsx');
        $this->assertFileExists($filePath);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $this->assertSame(['BARANG MASUK', 'BARANG KELUAR'], $spreadsheet->getSheetNames());

        $sheetMasuk = $spreadsheet->getSheetByName('BARANG MASUK');
        $sheetKeluar = $spreadsheet->getSheetByName('BARANG KELUAR');

        $this->assertSame('1FA33A', $sheetMasuk->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertSame('1FA33A', $sheetKeluar->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertCount(1, $sheetMasuk->getDrawingCollection());
        $this->assertCount(1, $sheetKeluar->getDrawingCollection());
        $this->assertNull($sheetMasuk->getCell('A5')->getValue());
        $this->assertNull($sheetKeluar->getCell('A5')->getValue());

        $sheetMasuk->fromArray([
            [1, '01 Jan 2024', 'Barang Masuk', 'BL42', 'PAKU BALOK 3"', 'KG', 'UD. MUARA', 50, 14375, 718750, 'OKI'],
            [2, '01 Jan 2024', 'Barang Masuk', 'BL01', 'SEMEN GRESIK 50KG', 'SAK', 'TB. MAJU JAYA', 100, 68000, 6800000, 'OKI'],
            [3, '02 Jan 2024', 'Barang Masuk', 'BL12', 'PIPA PVC 3 INCH D', 'BTG', 'TB. MAJU JAYA', 30, 85000, 2550000, 'OKI'],
        ], null, 'A5');

        $sheetKeluar->fromArray([
            ['02 Jan 2024', 'Januari', 2024, 'OKI', 'PAK UJANG', 'Blok B3', 'MAL PLAT LANTAI B3', 'BL42', 'PAKU BALOK 3"', 2, 'KG', 14375, 28750, 'UD. MUARA'],
            ['03 Jan 2024', 'Januari', 2024, 'OKI', 'PAK DEDI', 'Blok A1', 'PASANGAN BATA A1', 'BL01', 'SEMEN GRESIK 50KG', 10, 'SAK', 68000, 680000, 'TB. MAJU JAYA'],
            ['04 Jan 2024', 'Januari', 2024, 'OKI', 'TIM INFRA', 'Infrastruktur & Fasilitas Umum', 'SALURAN DRAINASE UTAMA', 'BL12', 'PIPA PVC 3 INCH D', 5, 'BTG', 85000, 425000, 'TB. MAJU JAYA'],
        ], null, 'A5');

        $tempFile = storage_path('app/temp_sample_material_import_test.xlsx');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempFile);

        try {
            $import = new MaterialImport($tempFile);
            Excel::import($import, $tempFile);
        } finally {
            @unlink($tempFile);
        }

        $this->assertEquals(6, $import->successfulRows);
        $this->assertEquals(0, $import->skippedRows);
        $this->assertEquals(6, $import->transactionsImported);

        // Assert BARANG MASUK imported
        $this->assertDatabaseHas('stock_ins', [
            'material_id' => $matPaku->id,
            'quantity' => 50,
            'notes' => 'Stok Masuk dari Excel',
        ]);
        $this->assertDatabaseHas('stock_ins', [
            'material_id' => $matSemen->id,
            'quantity' => 100,
            'notes' => 'Stok Masuk dari Excel',
        ]);
        $this->assertDatabaseHas('stock_ins', [
            'material_id' => $matPipa->id,
            'quantity' => 30,
            'notes' => 'Stok Masuk dari Excel',
        ]);

        // Assert BARANG KELUAR imported
        $this->assertDatabaseHas('material_usages', [
            'house_id' => $houseB3->id,
            'quantity' => 2,
            'taken_by' => 'PAK UJANG',
            'notes' => 'MAL PLAT LANTAI B3',
        ]);
        $this->assertDatabaseHas('material_usages', [
            'house_id' => $houseA1->id,
            'quantity' => 10,
            'taken_by' => 'PAK DEDI',
            'notes' => 'PASANGAN BATA A1',
        ]);
        $this->assertDatabaseHas('material_usages', [
            'house_id' => $houseInfra->id,
            'quantity' => 5,
            'taken_by' => 'TIM INFRA',
            'notes' => 'SALURAN DRAINASE UTAMA',
        ]);

        // Final stock check: initial + restock - usage
        // Paku: 10 + 50 - 2 = 58
        $this->assertEquals(58, (float) $matPaku->fresh()->stock);
        // Semen: 50 + 100 - 10 = 140
        $this->assertEquals(140, (float) $matSemen->fresh()->stock);
        // Pipa: 20 + 30 - 5 = 45
        $this->assertEquals(45, (float) $matPipa->fresh()->stock);
    }

    public function test_tool_import_parses_a_keluar_format()
    {
        House::factory()->create(['name' => 'F-01']);
        $rows = collect([
            [
                0 => 46265,
                'bulan' => 'Agustus',
                'tahun' => 2026,
                'admin' => 'ADMIN',
                'pengambil' => 'AGUS',
                'blok_rumah' => 'F-01',
                'keterangan_pekerjaan' => 'PONDASI RUMAH',
                'kode_alat' => 'ALT510',
                'nama_alat' => 'Genset Silent 5000W',
                'volume' => 1,
                'satuan' => 'unit',
                'harga_satuan' => 12500000,
                'jumlah' => 12500000,
                'toko_supplier' => 'CV. Teknik Jaya',
            ],
            [
                'kode_alat' => '*DI ISI',
                'nama_alat' => '*LINK',
            ],
        ]);

        $import = new ToolImport;
        $import->collection($rows);

        $this->assertEquals(1, $import->successfulRows);
        $this->assertEquals(1, $import->skippedRows);
        $this->assertEquals(1, $import->toolsImported);
        $this->assertEquals(1, $import->transactionsImported);

        $this->assertDatabaseHas('tools', [
            'code' => 'ALT510',
            'name' => 'Genset Silent 5000W',
            'purchase_price' => 12500000,
            'total_qty' => 1,
            'available_qty' => 0,
        ]);
        $this->assertDatabaseHas('houses', ['name' => 'F-01']);
        $this->assertDatabaseHas('tool_usages', [
            'quantity' => 1,
            'checkout_date' => '2026-08-31 00:00:00',
            'notes' => 'PONDASI RUMAH',
        ]);
    }

    public function test_material_livewire_import_modal()
    {
        Livewire::test(Materials::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true);
    }

    public function test_tool_livewire_import_modal()
    {
        $filePath = public_path('sample_tool_import.xlsx');
        $this->assertFileExists($filePath);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $this->assertSame(['ALAT MASUK', 'ALAT KELUAR'], $spreadsheet->getSheetNames());

        $sheetMasuk = $spreadsheet->getSheetByName('ALAT MASUK');
        $sheetKeluar = $spreadsheet->getSheetByName('ALAT KELUAR');

        $this->assertSame('1FA33A', $sheetMasuk->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertSame('1FA33A', $sheetKeluar->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertCount(1, $sheetMasuk->getDrawingCollection());
        $this->assertCount(1, $sheetKeluar->getDrawingCollection());
        $this->assertNull($sheetMasuk->getCell('A5')->getValue());
        $this->assertNull($sheetKeluar->getCell('A5')->getValue());

        Livewire::test(Tools::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true);

        Livewire::test(\App\Livewire\Logistik\TransaksiLogistik::class)
            ->call('openImportModal', 'tool')
            ->assertSet('showImportModal', true)
            ->assertSet('importType', 'tool');
    }

    public function test_house_template_import_processes_all_sheets()
    {
        $warehouse = Warehouse::firstOrFail();
        Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Bata Ringan AAC Rumah',
            'unit' => 'buah',
            'unit_price' => 12500,
            'stock' => 500,
        ]);
        Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Semen Portland Rumah',
            'unit' => 'sak',
            'unit_price' => 96000,
            'stock' => 25,
        ]);
        $house = House::factory()->create([
            'house_code' => '2026-G02',
            'name' => 'Blok G-02',
            'status' => 'pembangunan',
        ]);
        $returnableTool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-HSE-002',
            'name' => 'Bor Beton Rumah',
            'total_qty' => 1,
            'available_qty' => 0,
        ]);
        Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-HSE-001',
            'name' => 'Mesin Potong Keramik Rumah',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);
        ToolUsage::factory()->create([
            'tool_id' => $returnableTool->id,
            'house_id' => $house->id,
            'user_id' => $this->user->id,
            'quantity' => 1,
            'checkout_date' => '2026-09-01',
            'return_date' => null,
        ]);

        $import = new HouseImport;

        Excel::import($import, base_path('docs/sample_house_import.xlsx'));

        $this->assertGreaterThan(0, $import->successfulRows);
        $this->assertGreaterThan(0, $import->housesImported + $import->materialsImported + $import->toolsImported);
        $this->assertSame(1, $import->housesImported);
        $this->assertDatabaseCount('houses', 2);
        $this->assertDatabaseHas('houses', [
            'house_code' => '2026-G01',
            'start_date' => '2026-09-01 00:00:00',
            'target_end_date' => '2027-02-28 00:00:00',
        ]);
        $this->assertDatabaseHas('material_usages', ['usage_date' => '2026-09-03 00:00:00']);
        $this->assertDatabaseHas('tool_usages', ['checkout_date' => '2026-09-03 00:00:00']);
    }

    public function test_same_file_cannot_be_imported_twice_for_each_import_type()
    {
        // The material template contains stock-out rows; warehouse stock must exist first.
        Material::factory()->create(['name' => 'Bata Ringan AAC 10 cm', 'stock' => 500]);
        Material::factory()->create(['name' => 'Semen Portland 50 kg', 'stock' => 25]);
        $warehouse = Warehouse::firstOrFail();
        Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Bata Ringan AAC Rumah',
            'unit' => 'buah',
            'unit_price' => 12500,
            'stock' => 500,
        ]);
        Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Semen Portland Rumah',
            'unit' => 'sak',
            'unit_price' => 96000,
            'stock' => 25,
        ]);
        $house = House::factory()->create([
            'house_code' => '2026-G02',
            'name' => 'Blok G-02',
            'status' => 'pembangunan',
        ]);
        $returnableTool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-HSE-002',
            'name' => 'Bor Beton Rumah',
            'total_qty' => 1,
            'available_qty' => 0,
        ]);
        Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-HSE-001',
            'name' => 'Mesin Potong Keramik Rumah',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);
        ToolUsage::factory()->create([
            'tool_id' => $returnableTool->id,
            'house_id' => $house->id,
            'user_id' => $this->user->id,
            'quantity' => 1,
            'checkout_date' => '2026-09-01',
            'return_date' => null,
        ]);
        House::factory()->create([
            'house_code' => '2026-G01',
            'name' => 'Blok G-01',
            'status' => 'pembangunan',
        ]);
        $cases = [
            ['material', Materials::class, 'sample_material_import.xlsx'],
            ['tool', Tools::class, 'sample_tool_import.xlsx'],
            ['house', Houses::class, 'sample_house_import.xlsx'],
        ];

        foreach ($cases as [$type, $component, $fileName]) {
            $firstImport = Livewire::test($component)
                ->set('importFile', $this->templateUpload($fileName))
                ->call('importExcel');
            if ($firstImport->errors()->has('importFile')) {
                $this->fail($type.': '.$firstImport->errors()->first('importFile'));
            }

            $countsAfterFirstImport = [
                Material::count(),
                StockIn::count(),
                MaterialUsage::count(),
                Tool::count(),
                ToolUsage::count(),
                ToolReturnLog::count(),
                House::count(),
            ];

            Livewire::test($component)
                ->set('importFile', $this->templateUpload($fileName))
                ->call('importExcel')
                ->assertHasErrors('importFile');

            $this->assertSame($countsAfterFirstImport, [
                Material::count(),
                StockIn::count(),
                MaterialUsage::count(),
                Tool::count(),
                ToolUsage::count(),
                ToolReturnLog::count(),
                House::count(),
            ]);

            $this->assertDatabaseHas('import_batches', [
                'import_type' => $type,
                'status' => 'completed',
            ]);
        }

        $this->assertDatabaseCount('import_batches', 3);
        $batch = ImportBatch::where('import_type', 'material')->where('status', 'completed')->firstOrFail();
        $this->assertNotEmpty($batch->reconciliation_before);
        $this->assertNotEmpty($batch->reconciliation_after);
        $this->assertArrayHasKey('warehouses', $batch->reconciliation_delta);
    }

    public function test_failed_import_batch_is_recorded_and_can_be_retried()
    {
        $file = UploadedFile::fake()->createWithContent('retry.xlsx', 'same import bytes');

        try {
            ImportBatch::run('material', $file, function () {
                Material::create([
                    'name' => 'Must Roll Back',
                    'unit' => 'sak',
                    'unit_price' => 1000,
                    'stock' => 10,
                ]);

                throw new \RuntimeException('Invalid workbook');
            });
            $this->fail('The simulated import should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid workbook', $exception->getMessage());
        }

        $this->assertDatabaseMissing('materials', ['name' => 'Must Roll Back']);

        $this->assertDatabaseHas('import_batches', [
            'import_type' => 'material',
            'status' => 'failed',
            'error_message' => 'Invalid workbook',
        ]);

        $result = ImportBatch::run('material', $file, fn () => (object) [
            'totalRows' => 1,
            'successfulRows' => 1,
            'skippedRows' => 0,
        ]);

        $this->assertSame(1, $result->successfulRows);
        $this->assertDatabaseHas('import_batches', [
            'import_type' => 'material',
            'status' => 'completed',
            'successful_rows' => 1,
            'error_message' => null,
        ]);
        $this->assertDatabaseCount('import_batches', 1);
    }

    private function templateUpload(string $fileName): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $fileName,
            file_get_contents(base_path('docs/'.$fileName)),
        );
    }
}

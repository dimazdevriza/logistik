<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\House;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use App\Imports\HouseImport;
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
                'tanggal' => '2026-08-18'
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
                'tanggal' => '2026-08-18'
            ]
        ]);

        $import = new \App\Imports\MaterialImport();
        $import->collection($rows);

        $this->assertEquals(2, $import->materialsImported);
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
                'tanggal' => '2026-08-18'
            ]
        ]);

        $import = new \App\Imports\ToolImport();
        $import->collection($rows);

        $this->assertEquals(1, $import->toolsImported);
        $this->assertEquals(1, $import->transactionsImported);

        $this->assertDatabaseHas('tools', ['code' => 'ALT-TST-01', 'name' => 'Pompa Air Submersible Test']);
        $this->assertDatabaseHas('houses', ['name' => 'Blok Y-02']);
        $this->assertDatabaseHas('tool_usages', ['notes' => 'Peminjaman Pengeringan Pondasi']);
    }

    public function test_material_import_parses_m_keluar_format()
    {
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
                'nama_barang' => 'Alkali Jotun Jotashield',
                'volume' => 1,
                'satuan' => 'Pail',
                'harga_satuan' => 1091000,
                'jumlah' => 1091000,
                'toko_supplier' => 'DONA TIN TING',
            ],
            [
                'kode_barang' => '*DI ISI',
                'nama_barang' => '*LINK',
            ],
        ]);

        $import = new \App\Imports\MaterialImport();
        $import->collection($rows);

        $this->assertEquals(1, $import->successfulRows);
        $this->assertEquals(1, $import->skippedRows);
        $this->assertEquals(1, $import->materialsImported);
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
            'usage_date' => '2026-01-30 00:00:00',
        ]);
    }

    public function test_tool_import_parses_a_keluar_format()
    {
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

        $import = new \App\Imports\ToolImport();
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
        Livewire::test(\App\Livewire\Logistik\Materials::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true);
    }

    public function test_tool_livewire_import_modal()
    {
        Livewire::test(\App\Livewire\Logistik\Tools::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true);
    }

    public function test_house_template_import_processes_all_sheets()
    {
        $import = new HouseImport();

        Excel::import($import, base_path('docs/sample_house_import.xlsx'));

        $this->assertGreaterThan(0, $import->successfulRows);
        $this->assertGreaterThan(0, $import->housesImported + $import->materialsImported + $import->toolsImported);
        $this->assertDatabaseCount('houses', $import->housesImported);
    }

    public function test_same_file_cannot_be_imported_twice_for_each_import_type()
    {
        $cases = [
            ['material', \App\Livewire\Logistik\Materials::class, 'sample_material_import.xlsx'],
            ['tool', \App\Livewire\Logistik\Tools::class, 'sample_tool_import.xlsx'],
            ['house', \App\Livewire\Logistik\Houses::class, 'sample_house_import.xlsx'],
        ];

        foreach ($cases as [$type, $component, $fileName]) {
            Livewire::test($component)
                ->set('importFile', $this->templateUpload($fileName))
                ->call('importExcel')
                ->assertHasNoErrors('importFile');

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
            file_get_contents(base_path('docs/' . $fileName)),
        );
    }
}

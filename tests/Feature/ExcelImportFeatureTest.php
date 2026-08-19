<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
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
}

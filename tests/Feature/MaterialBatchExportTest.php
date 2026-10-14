<?php

namespace Tests\Feature;

use App\Exports\MaterialInventoryExport;
use App\Livewire\Logistik\Materials;
use App\Models\Category;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MaterialBatchExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_export_contains_all_stock_and_restock_batches_with_exact_values(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $warehouse = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $category = Category::create(['name' => 'Besi Beton', 'type' => 'material']);
        $supplier = Supplier::create(['name' => 'PT Baja Unggul']);

        $material = Material::create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Besi 10 mm Polos',
            'code' => 'BS-001',
            'unit' => 'batang',
            'unit_price' => 65000,
            'stock' => 15,
        ]);

        // Batch 1: Initial stock
        StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'entry_code' => 'MSK-BATCH-001',
            'quantity' => 10,
            'remaining_quantity' => 0, // Depleted batch
            'unit_price' => 60000,
            'total_cost' => 600000,
            'date' => '2026-10-01',
            'received_at' => '2026-10-01 08:00:00',
        ]);

        // Batch 2: Restock batch
        StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'entry_code' => 'MSK-BATCH-002',
            'quantity' => 15,
            'remaining_quantity' => 15,
            'unit_price' => 65000,
            'total_cost' => 975000,
            'date' => '2026-10-10',
            'received_at' => '2026-10-10 14:30:00',
        ]);

        $file = tmpfile();
        try {
            fwrite($file, Excel::raw(new MaterialInventoryExport(), \Maatwebsite\Excel\Excel::XLSX));
            $workbook = IOFactory::load(stream_get_meta_data($file)['uri']);
            $sheet = $workbook->getActiveSheet();

            // Row 6 is Header
            $this->assertSame('No', $sheet->getCell('A6')->getValue());
            $this->assertSame('Kode Material', $sheet->getCell('B6')->getValue());
            $this->assertSame('Kode Masuk', $sheet->getCell('C6')->getValue());
            $this->assertSame('Nama Material', $sheet->getCell('D6')->getValue());
            $this->assertSame('Kategori', $sheet->getCell('E6')->getValue());
            $this->assertSame('Supplier', $sheet->getCell('F6')->getValue());
            $this->assertSame('Gudang', $sheet->getCell('G6')->getValue());
            $this->assertSame('Sisa Stok', $sheet->getCell('H6')->getValue());
            $this->assertSame('Satuan', $sheet->getCell('I6')->getValue());
            $this->assertSame('Harga Masuk', $sheet->getCell('J6')->getValue());
            $this->assertSame('Estimasi Nilai', $sheet->getCell('K6')->getValue());
            $this->assertSame('Tanggal Masuk', $sheet->getCell('L6')->getValue());

            // Row 7 is Batch 1
            $this->assertSame('BS-001', $sheet->getCell('B7')->getValue());
            $this->assertSame('MSK-BATCH-001', $sheet->getCell('C7')->getValue());
            $this->assertSame('Besi 10 mm Polos', $sheet->getCell('D7')->getValue());
            // Strict null comparison check: 0 is numeric 0, NOT null
            $this->assertEquals(0, $sheet->getCell('H7')->getValue());
            $this->assertNotNull($sheet->getCell('H7')->getValue());
            $this->assertEquals(60000, $sheet->getCell('J7')->getValue());
            $this->assertEquals(0, $sheet->getCell('K7')->getValue());

            // Row 8 is Batch 2 (Restock)
            $this->assertSame('BS-001', $sheet->getCell('B8')->getValue());
            $this->assertSame('MSK-BATCH-002', $sheet->getCell('C8')->getValue());
            $this->assertSame('Besi 10 mm Polos', $sheet->getCell('D8')->getValue());
            $this->assertEquals(15, $sheet->getCell('H8')->getValue());
            $this->assertEquals(65000, $sheet->getCell('J8')->getValue());
            $this->assertEquals(975000, $sheet->getCell('K8')->getValue());

            $workbook->disconnectWorksheets();
        } finally {
            fclose($file);
        }

        // Livewire export test
        Livewire::test(Materials::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}

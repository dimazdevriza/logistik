<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\Warehouse;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoInventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoInventorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_inventory_uses_realistic_supplier_data_and_stable_catalog_codes(): void
    {
        $this->seed(CategorySeeder::class);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);
        $supplier = Supplier::create(['name' => 'Supplier Demo Konstruksi']);
        $materialCategoryId = Category::where('name', 'Semen')->value('id');
        $toolCategoryId = Category::where('name', 'Alat Berat')->value('id');
        $material = Material::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'DEMO-MAT-0001',
            'supplier_id' => $supplier->id,
            'category_id' => $materialCategoryId,
            'name' => 'Semen Portland 50 kg',
            'unit' => 'sak',
            'unit_price' => 65000,
            'stock' => 1,
        ]);
        StockIn::create([
            'entry_code' => 'OPEN-DEMO-MAT-0001',
            'entry_type' => 'opening_balance',
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'quantity' => 1,
            'remaining_quantity' => 1,
            'unit_price' => 65000,
            'total_cost' => 0,
            'date' => now()->toDateString(),
            'notes' => 'Saldo awal dummy untuk demonstrasi sistem.',
        ]);
        Tool::create([
            'entry_code' => 'OPEN-DEMO-TOOL-0001',
            'entry_type' => 'opening_balance',
            'warehouse_id' => $warehouse->id,
            'category_id' => $toolCategoryId,
            'code' => 'DEMO-TOOL-0001',
            'name' => 'Molen Beton 350 Liter',
            'condition' => 'baik',
            'purchase_price' => 15000000,
            'total_qty' => 1,
            'available_qty' => 1,
            'qty_broken' => 0,
        ]);

        $this->seed(DemoInventorySeeder::class);

        $this->assertSame(63, Material::where('code', 'like', 'MAT-%')->count());
        $this->assertDatabaseHas('materials', [
            'code' => 'MAT-0001',
            'name' => 'Semen Portland 50 kg',
        ]);
        $this->assertDatabaseHas('tools', [
            'code' => 'AB-101',
            'name' => 'Molen Beton 350 Liter',
        ]);
        $this->assertDatabaseHas('stock_ins', [
            'entry_code' => 'OPEN-MAT-0001',
            'supplier_id' => Supplier::where('name', 'PT. Andalas Beton Perkasa')->value('id'),
            'notes' => 'Saldo awal persediaan.',
        ]);
        $this->assertDatabaseHas('materials', ['code' => 'MAT-0001', 'stock' => '1.00']);
        $this->assertDatabaseHas('suppliers', [
            'name' => 'PT. Andalas Beton Perkasa',
            'contact_person' => 'Tim Penjualan',
        ]);
        $this->assertDatabaseMissing('suppliers', ['name' => 'Supplier Demo Konstruksi']);
        $this->assertDatabaseMissing('materials', ['code' => 'DEMO-MAT-0001']);
        $this->assertDatabaseMissing('tools', ['code' => 'DEMO-TOOL-0001']);

        $counts = [Material::count(), StockIn::count(), Supplier::count(), Tool::count()];
        $this->seed(DemoInventorySeeder::class);

        $this->assertSame($counts, [Material::count(), StockIn::count(), Supplier::count(), Tool::count()]);
    }
}

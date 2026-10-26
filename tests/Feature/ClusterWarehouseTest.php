<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Clusters;
use App\Livewire\Logistik\Houses;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\Tools;
use App\Livewire\Logistik\WarehouseDetail;
use App\Livewire\Logistik\Warehouses;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClusterWarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_houses_are_grouped_by_cluster_and_inventory_is_assigned_to_a_warehouse(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $defaultWarehouse = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $warehouse = Warehouse::create(['name' => 'Gudang Timur', 'address' => 'Area Timur']);
        $cluster = Cluster::create(['name' => 'Cluster Mawar']);

        Livewire::test(Houses::class)
            ->set([
                'name' => 'Blok M-01',
                'type' => 'Tipe 45',
                'status' => 'pembangunan',
                'cluster_id' => $cluster->id,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $house = House::where('name', 'Blok M-01')->firstOrFail();
        $this->assertSame($cluster->id, $house->cluster_id);
        $this->assertTrue($cluster->fresh()->houses->contains($house));

        Livewire::test(Materials::class)
            ->call('create')
            ->set([
                'name' => 'Pasir Uji Gudang',
                'category_name' => 'Kategori Uji Gudang',
                'supplier_name' => 'Supplier Uji Gudang',
                'unit' => 'sak',
                'unit_price' => 12000,
                'stock' => 5,
                'warehouse_id' => $warehouse->id,
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', true)
            ->assertSee('Material berhasil ditambahkan.');

        Livewire::test(Tools::class)
            ->call('create')
            ->set([
                'name' => 'Bor Uji Gudang',
                'code' => 'GDG-UJI-001',
                'condition' => 'baik',
                'purchase_price' => 100000,
                'total_qty' => 2,
                'available_qty' => 2,
                'qty_broken' => 0,
                'warehouse_id' => $warehouse->id,
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', true)
            ->assertSee('Alat kerja baru berhasil ditambahkan.');

        $material = Material::where('name', 'Pasir Uji Gudang')->firstOrFail();
        $tool = Tool::where('code', 'GDG-UJI-001')->firstOrFail();
        $this->assertSame($warehouse->id, $material->warehouse_id);
        $this->assertSame($warehouse->id, $tool->warehouse_id);
        $this->assertSame($defaultWarehouse->id, Material::factory()->create()->warehouse_id);

        Livewire::test(Clusters::class)->call('confirmDelete', $cluster->id)
            ->assertHasErrors('delete')->assertSet('showConfirmation', false);
        Livewire::test(Warehouses::class)->call('confirmDelete', $warehouse->id)
            ->assertHasErrors('delete')->assertSet('showConfirmation', false);

        $this->get(route('logistik.clusters'))->assertOk()->assertSee('Cluster Mawar')->assertSee('Blok M-01');
        $this->get(route('logistik.warehouses'))->assertOk()->assertSee('Gudang Timur')->assertSee('1 jenis');
        $this->get(route('logistik.warehouse-detail', $warehouse))->assertOk()->assertSee('Pasir Uji Gudang');
        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouse])
            ->set('activeTab', 'tool')->assertSee('Bor Uji Gudang');
        $this->get(route('logistik.houses'))->assertOk()->assertSee('Cluster')->assertSee('Cluster Mawar');
        $this->get(route('logistik.materials'))->assertOk()->assertSee('Gudang')->assertSee('Gudang Timur')->assertSee('Pasir Uji Gudang');
        $this->get(route('logistik.tools'))->assertOk()->assertSee('Gudang')->assertSee('GDG-UJI-001');
    }

    public function test_inventory_with_history_cannot_be_reassigned_to_another_warehouse(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'Gudang Selatan']);
        $house = House::factory()->create();
        $material = Material::factory()->create(['warehouse_id' => $source->id, 'stock' => 3]);
        $tool = Tool::factory()->create(['warehouse_id' => $source->id, 'total_qty' => 2, 'available_qty' => 1]);

        StockIn::create([
            'material_id' => $material->id,
            'supplier_id' => $material->supplier_id,
            'user_id' => $user->id,
            'quantity' => 3,
            'unit_price' => $material->unit_price,
            'total_cost' => 3 * $material->unit_price,
            'date' => '2026-09-14',
        ]);
        ToolUsage::create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'checkout_date' => '2026-09-14',
        ]);

        Livewire::test(Materials::class)->call('edit', $material->id)
            ->set('warehouse_id', $target->id)->call('save')
            ->assertHasErrors('warehouse_id');
        Livewire::test(Tools::class)->call('edit', $tool->id)
            ->set('warehouse_id', $target->id)->call('save')
            ->assertHasErrors('warehouse_id');

        $this->assertSame($source->id, $material->fresh()->warehouse_id);
        $this->assertSame($source->id, $tool->fresh()->warehouse_id);
    }

    public function test_empty_warehouse_detail_shows_empty_inventory_states(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $warehouse = Warehouse::where('name', 'Gudang Utama')->firstOrFail();

        $this->get(route('logistik.warehouse-detail', $warehouse))
            ->assertOk()
            ->assertSee('Belum ada material di gudang ini.');
        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouse])
            ->set('activeTab', 'tool')
            ->assertSee('Belum ada alat di gudang ini.');
    }

    public function test_warehouse_detail_exports_its_inventory(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $warehouse = Warehouse::where('name', 'Gudang Utama')->firstOrFail();

        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouse])
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_warehouse_valuation_and_batches_match_across_materials_filter_and_warehouse_detail(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $mainWarehouse = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $siteWarehouse = Warehouse::create(['name' => 'Gudang Lapangan Uji', 'address' => 'Cluster B']);

        $sand = Material::factory()->create([
            'warehouse_id' => $mainWarehouse->id,
            'name' => 'Pasir Pasang Uji',
            'code' => 'MAT-UJI-PASIR',
            'unit' => 'm³',
            'unit_price' => 250000,
            'stock' => 30,
        ]);
        StockIn::create([
            'entry_code' => 'MSK-UJI-PASIR-1',
            'entry_type' => 'receipt',
            'material_id' => $sand->id,
            'warehouse_id' => $mainWarehouse->id,
            'user_id' => $user->id,
            'quantity' => 20,
            'remaining_quantity' => 14.5,
            'unit_price' => 240000,
            'total_cost' => 4800000,
            'date' => '2026-09-10',
        ]);
        StockIn::create([
            'entry_code' => 'MSK-UJI-PASIR-2',
            'entry_type' => 'receipt',
            'material_id' => $sand->id,
            'warehouse_id' => $siteWarehouse->id,
            'user_id' => $user->id,
            'quantity' => 15.5,
            'remaining_quantity' => 15.5,
            'unit_price' => 250000,
            'total_cost' => 3875000,
            'date' => '2026-09-12',
        ]);

        $brick = Material::factory()->create([
            'warehouse_id' => $siteWarehouse->id,
            'name' => 'Bata Uji Multi Batch',
            'code' => 'MAT-UJI-BATA',
            'unit' => 'buah',
            'unit_price' => 1200,
            'stock' => 140,
        ]);
        StockIn::create([
            'entry_code' => 'MSK-UJI-BATA-1',
            'entry_type' => 'receipt',
            'material_id' => $brick->id,
            'warehouse_id' => $siteWarehouse->id,
            'user_id' => $user->id,
            'quantity' => 100,
            'remaining_quantity' => 100,
            'unit_price' => 1200,
            'total_cost' => 120000,
            'date' => '2026-09-13',
        ]);
        StockIn::create([
            'entry_code' => 'MSK-UJI-BATA-2',
            'entry_type' => 'receipt',
            'material_id' => $brick->id,
            'warehouse_id' => $siteWarehouse->id,
            'user_id' => $user->id,
            'quantity' => 40,
            'remaining_quantity' => 40,
            'unit_price' => 1500,
            'total_cost' => 60000,
            'date' => '2026-09-14',
        ]);

        // All warehouses: (14.5 * 240,000) + (15.5 * 250,000) + (100 * 1,200) + (40 * 1,500) = 3,480,000 + 3,875,000 + 180,000 = 7,535,000
        // Site warehouse only: 3,875,000 + 120,000 + 60,000 = 4,055,000
        Livewire::test(Materials::class)
            ->assertSee('Rp 7.535.000')
            ->set('filterWarehouse', (string) $siteWarehouse->id)
            ->assertSee('Estimasi Nilai Material · Gudang Lapangan Uji')
            ->assertSee('Material Aktif · Gudang Lapangan Uji')
            ->assertSee('Rp 4.055.000');

        Livewire::test(WarehouseDetail::class, ['warehouse' => $siteWarehouse])
            ->assertSee('Rp 4.055.000')
            ->assertSee('Pasir Pasang Uji')
            ->assertSee('MSK-UJI-PASIR-2')
            ->assertSee('Bata Uji Multi Batch')
            ->assertSee('MSK-UJI-BATA-1')
            ->assertSee('MSK-UJI-BATA-2')
            ->assertSee('Rp 1.200')
            ->assertSee('Rp 1.500');

        Livewire::test(WarehouseDetail::class, ['warehouse' => $mainWarehouse])
            ->assertSee('Rp 3.480.000')
            ->assertSee('Pasir Pasang Uji')
            ->assertSee('MSK-UJI-PASIR-1')
            ->assertDontSee('MSK-UJI-PASIR-2');
    }
}

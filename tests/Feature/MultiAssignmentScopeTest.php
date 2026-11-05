<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\Tools;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Livewire\Logistik\WarehouseDetail;
use App\Livewire\Logistik\Warehouses;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolWarehouseBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiAssignmentScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_multiple_clusters_and_warehouses_to_user(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);
        $warehouseA = Warehouse::create(['name' => 'Gudang Alpha']);
        $warehouseB = Warehouse::create(['name' => 'Gudang Beta']);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Multi Staff')
            ->set('email', 'multistaff@example.test')
            ->set('password', 'password123')
            ->set('role', 'logistik')
            ->set('cluster_ids', [(string) $clusterA->id, (string) $clusterB->id])
            ->set('warehouse_ids', [(string) $warehouseA->id, (string) $warehouseB->id])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'multistaff@example.test')->firstOrFail();
        $this->assertEqualsCanonicalizing([$clusterA->id, $clusterB->id], $user->assignedClusterIds());
        $this->assertEqualsCanonicalizing([$warehouseA->id, $warehouseB->id], $user->assignedWarehouseIds());
        $this->assertTrue($user->hasWarehouseChoice());
        $this->assertNull($user->assignedWarehouseId()); // Multiple warehouses, so assignedWarehouseId returns null
    }

    public function test_multi_warehouse_user_sees_both_assigned_warehouses_and_not_unassigned(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $warehouseA = Warehouse::create(['name' => 'Gudang A']);
        $warehouseB = Warehouse::create(['name' => 'Gudang B']);
        $warehouseC = Warehouse::create(['name' => 'Gudang C']);

        $user = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $cluster->id,
            'warehouse_id' => $warehouseA->id,
        ]);
        $user->clusters()->sync([$cluster->id]);
        $user->warehouses()->sync([$warehouseA->id, $warehouseB->id]);

        $matA = Material::create([
            'warehouse_id' => $warehouseA->id,
            'code' => 'MAT-A',
            'name' => 'Semen Gudang A',
            'unit' => 'sak',
            'unit_price' => 50000,
            'stock' => 10,
        ]);
        StockIn::create([
            'material_id' => $matA->id,
            'warehouse_id' => $warehouseA->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 50000,
            'total_cost' => 500000,
            'date' => now()->toDateString(),
        ]);

        $matB = Material::create([
            'warehouse_id' => $warehouseB->id,
            'code' => 'MAT-B',
            'name' => 'Pasir Gudang B',
            'unit' => 'm3',
            'unit_price' => 150000,
            'stock' => 5,
        ]);
        StockIn::create([
            'material_id' => $matB->id,
            'warehouse_id' => $warehouseB->id,
            'quantity' => 5,
            'remaining_quantity' => 5,
            'unit_price' => 150000,
            'total_cost' => 750000,
            'date' => now()->toDateString(),
        ]);

        $matC = Material::create([
            'warehouse_id' => $warehouseC->id,
            'code' => 'MAT-C',
            'name' => 'Cat Gudang C',
            'unit' => 'kaleng',
            'unit_price' => 100000,
            'stock' => 8,
        ]);
        StockIn::create([
            'material_id' => $matC->id,
            'warehouse_id' => $warehouseC->id,
            'quantity' => 8,
            'remaining_quantity' => 8,
            'unit_price' => 100000,
            'total_cost' => 800000,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user);

        // Materials index sees A and B, but NOT C
        Livewire::test(Materials::class)
            ->assertSee('Semen Gudang A')
            ->assertSee('Pasir Gudang B')
            ->assertDontSee('Cat Gudang C')
            ->set('showFilterModal', true)
            ->assertSeeHtml('wire:model.live="filterWarehouse"') // Shows filter dropdown because user has 2 warehouses
            ->set('filterWarehouse', (string) $warehouseA->id)
            ->assertSee('Semen Gudang A')
            ->assertDontSee('Pasir Gudang B')
            ->set('filterWarehouse', (string) $warehouseB->id)
            ->assertDontSee('Semen Gudang A')
            ->assertSee('Pasir Gudang B');

        // Can access warehouse detail for A and B, but forbidden for C
        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouseA])
            ->assertOk();
        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouseB])
            ->assertOk();
        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouseC])
            ->assertForbidden();

        // Warehouses index shows A and B, but NOT C
        Livewire::test(Warehouses::class)
            ->assertSee('Gudang A')
            ->assertSee('Gudang B')
            ->assertDontSee('Gudang C');
    }

    public function test_multi_cluster_user_sees_houses_from_all_assigned_clusters(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);
        $clusterC = Cluster::create(['name' => 'Cluster Gamma']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);

        $houseA = House::factory()->create(['cluster_id' => $clusterA->id, 'name' => 'Rumah A1', 'house_code' => '2026-A1']);
        $houseB = House::factory()->create(['cluster_id' => $clusterB->id, 'name' => 'Rumah B1', 'house_code' => '2026-B1']);
        $houseC = House::factory()->create(['cluster_id' => $clusterC->id, 'name' => 'Rumah C1', 'house_code' => '2026-C1']);

        $user = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $clusterA->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $user->clusters()->sync([$clusterA->id, $clusterB->id]);
        $user->warehouses()->sync([$warehouse->id]);

        $this->actingAs($user);

        $accessibleHouses = House::forUser($user)->pluck('name')->all();
        $this->assertContains('Rumah A1', $accessibleHouses);
        $this->assertContains('Rumah B1', $accessibleHouses);
        $this->assertNotContains('Rumah C1', $accessibleHouses);

        $this->assertTrue($user->hasClusterChoice());

        Livewire::test(TransaksiLogistik::class)
            ->call('openHousePicker', 'house')
            ->assertSeeHtml('id="house-cluster"')
            ->assertSee('Cluster Alpha')
            ->assertSee('Cluster Beta')
            ->assertDontSee('Cluster Gamma')
            ->set('houseCluster', (string) $clusterA->id)
            ->assertViewHas('houseResults', fn ($rows) => $rows->contains('id', $houseA->id) && $rows->doesntContain('id', $houseB->id))
            ->set('houseCluster', (string) $clusterB->id)
            ->assertViewHas('houseResults', fn ($rows) => $rows->doesntContain('id', $houseA->id) && $rows->contains('id', $houseB->id));
    }

    public function test_multi_warehouse_and_cluster_scoping_in_allocation(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);
        $clusterC = Cluster::create(['name' => 'Cluster Gamma']);

        $warehouseA = Warehouse::create(['name' => 'Gudang Alpha']);
        $warehouseB = Warehouse::create(['name' => 'Gudang Beta']);
        $warehouseC = Warehouse::create(['name' => 'Gudang Gamma']);

        $houseA = House::factory()->create(['cluster_id' => $clusterA->id, 'name' => 'Rumah A1', 'house_code' => '2026-A1']);
        $houseB = House::factory()->create(['cluster_id' => $clusterB->id, 'name' => 'Rumah B1', 'house_code' => '2026-B1']);
        $houseC = House::factory()->create(['cluster_id' => $clusterC->id, 'name' => 'Rumah C1', 'house_code' => '2026-C1']);

        $matA = Material::create([
            'warehouse_id' => $warehouseA->id,
            'code' => 'MAT-A',
            'name' => 'Semen Gudang A',
            'unit' => 'sak',
            'unit_price' => 50000,
            'stock' => 10,
        ]);
        $batchA = StockIn::create([
            'material_id' => $matA->id,
            'warehouse_id' => $warehouseA->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 50000,
            'total_cost' => 500000,
            'date' => now()->toDateString(),
            'entry_code' => 'BATCH-A',
        ]);

        $matB = Material::create([
            'warehouse_id' => $warehouseB->id,
            'code' => 'MAT-B',
            'name' => 'Bata Gudang B',
            'unit' => 'buah',
            'unit_price' => 1000,
            'stock' => 500,
        ]);
        $batchB = StockIn::create([
            'material_id' => $matB->id,
            'warehouse_id' => $warehouseB->id,
            'quantity' => 500,
            'remaining_quantity' => 500,
            'unit_price' => 1000,
            'total_cost' => 500000,
            'date' => now()->toDateString(),
            'entry_code' => 'BATCH-B',
        ]);

        $matC = Material::create([
            'warehouse_id' => $warehouseC->id,
            'code' => 'MAT-C',
            'name' => 'Cat Gudang C',
            'unit' => 'kaleng',
            'unit_price' => 100000,
            'stock' => 20,
        ]);
        $batchC = StockIn::create([
            'material_id' => $matC->id,
            'warehouse_id' => $warehouseC->id,
            'quantity' => 20,
            'remaining_quantity' => 20,
            'unit_price' => 100000,
            'total_cost' => 2000000,
            'date' => now()->toDateString(),
            'entry_code' => 'BATCH-C',
        ]);

        $user = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $clusterA->id,
            'warehouse_id' => $warehouseA->id,
        ]);
        $user->clusters()->sync([$clusterA->id, $clusterB->id]);
        $user->warehouses()->sync([$warehouseA->id, $warehouseB->id]);

        $this->actingAs($user);

        // Test TransaksiLogistik
        $component = Livewire::test(\App\Livewire\Logistik\TransaksiLogistik::class);

        // Materials returned in component include A and B, but NOT C
        $materials = $component->instance()->getMaterials();
        $this->assertTrue($materials->contains('id', $matA->id));
        $this->assertTrue($materials->contains('id', $matB->id));
        $this->assertFalse($materials->contains('id', $matC->id));

        // Houses returned include A and B, but NOT C
        $houses = House::forUser($user)->get();
        $this->assertTrue($houses->contains('id', $houseA->id));
        $this->assertTrue($houses->contains('id', $houseB->id));
        $this->assertFalse($houses->contains('id', $houseC->id));

        // Multi-warehouse user can allocate items from both assigned warehouses to both assigned clusters
        $component
            ->set('spreadsheetRows', [
                [
                    'house_id' => (string) $houseA->id,
                    'materials' => [
                        [
                            'material_id' => (string) $matA->id,
                            'batch_id' => (string) $batchA->id,
                            'quantity' => 2,
                            'notes' => 'Fondasi Rumah A',
                            'taken_by' => 'Tukang A',
                        ],
                    ],
                    'tools' => [],
                    'returns' => [],
                ],
                [
                    'house_id' => (string) $houseB->id,
                    'materials' => [
                        [
                            'material_id' => (string) $matB->id,
                            'batch_id' => (string) $batchB->id,
                            'quantity' => 50,
                            'notes' => 'Dinding Rumah B',
                            'taken_by' => 'Tukang B',
                        ],
                    ],
                    'tools' => [],
                    'returns' => [],
                ],
            ])
            ->call('reviewSpreadsheet')
            ->assertHasNoErrors()
            ->call('confirmSpreadsheetReview')
            ->call('saveSpreadsheet')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('material_usages', [
            'house_id' => $houseA->id,
            'material_id' => $matA->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('material_usages', [
            'house_id' => $houseB->id,
            'material_id' => $matB->id,
            'quantity' => 50,
        ]);

        // Attempting to allocate unassigned Material C or unassigned House C throws validation error
        Livewire::test(\App\Livewire\Logistik\TransaksiLogistik::class)
            ->set('spreadsheetRows', [
                [
                    'house_id' => (string) $houseC->id,
                    'materials' => [
                        [
                            'material_id' => (string) $matA->id,
                            'batch_id' => (string) $batchA->id,
                            'quantity' => 1,
                            'notes' => 'Test',
                            'taken_by' => '',
                        ],
                    ],
                    'tools' => [],
                    'returns' => [],
                ],
            ])
            ->call('reviewSpreadsheet')
            ->assertHasErrors(['spreadsheetRows']);
    }

    public function test_multi_warehouse_scoping_in_material_log_and_exports(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Test']);
        $warehouseA = Warehouse::create(['name' => 'Gudang A']);
        $warehouseB = Warehouse::create(['name' => 'Gudang B']);
        $warehouseC = Warehouse::create(['name' => 'Gudang C']);

        $house = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'Rumah 1', 'house_code' => '2026-01']);

        $matA = Material::create(['warehouse_id' => $warehouseA->id, 'code' => 'MAT-A', 'name' => 'Material A', 'unit' => 'sak', 'unit_price' => 10, 'stock' => 100]);
        $stockA = StockIn::create(['material_id' => $matA->id, 'warehouse_id' => $warehouseA->id, 'quantity' => 100, 'remaining_quantity' => 100, 'unit_price' => 10, 'total_cost' => 1000, 'date' => now()->toDateString(), 'entry_code' => 'MSK-A']);

        $matB = Material::create(['warehouse_id' => $warehouseB->id, 'code' => 'MAT-B', 'name' => 'Material B', 'unit' => 'sak', 'unit_price' => 20, 'stock' => 50]);
        $stockB = StockIn::create(['material_id' => $matB->id, 'warehouse_id' => $warehouseB->id, 'quantity' => 50, 'remaining_quantity' => 50, 'unit_price' => 20, 'total_cost' => 1000, 'date' => now()->toDateString(), 'entry_code' => 'MSK-B']);

        $matC = Material::create(['warehouse_id' => $warehouseC->id, 'code' => 'MAT-C', 'name' => 'Material C', 'unit' => 'sak', 'unit_price' => 30, 'stock' => 30]);
        $stockC = StockIn::create(['material_id' => $matC->id, 'warehouse_id' => $warehouseC->id, 'quantity' => 30, 'remaining_quantity' => 30, 'unit_price' => 30, 'total_cost' => 900, 'date' => now()->toDateString(), 'entry_code' => 'MSK-C']);

        $user = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id, 'warehouse_id' => $warehouseA->id]);
        $user->clusters()->sync([$cluster->id]);
        $user->warehouses()->sync([$warehouseA->id, $warehouseB->id]);

        $this->actingAs($user);

        // MaterialLog table sees MSK-A and MSK-B, but not MSK-C
        Livewire::test(\App\Livewire\Logistik\MaterialLog::class)
            ->assertSee('MSK-A')
            ->assertSee('MSK-B')
            ->assertDontSee('MSK-C');

        // Cannot edit receipt in unassigned Warehouse C
        Livewire::test(\App\Livewire\Logistik\MaterialLog::class)
            ->call('openReceiptEditor', $stockC->id)
            ->assertForbidden();

        // MaterialLog export only exports assigned warehouses
        $export = new \App\Exports\MaterialLogExport(
            sort: 'date_desc',
            clusterScope: $user->scopedClusterIds(),
            warehouseScope: $user->scopedWarehouseIds()
        );
        $collection = $export->collection();
        $this->assertTrue($collection->contains('event_code', 'MSK-A'));
        $this->assertTrue($collection->contains('event_code', 'MSK-B'));
        $this->assertFalse($collection->contains('event_code', 'MSK-C'));
    }

    public function test_multi_warehouse_scoping_in_inventory_transfers(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Test']);
        $warehouseA = Warehouse::create(['name' => 'Gudang A']);
        $warehouseB = Warehouse::create(['name' => 'Gudang B']);
        $warehouseC = Warehouse::create(['name' => 'Gudang C']);

        $matC = Material::create(['warehouse_id' => $warehouseC->id, 'code' => 'MAT-C', 'name' => 'Material C', 'unit' => 'sak', 'unit_price' => 30, 'stock' => 30]);
        $stockC = StockIn::create(['material_id' => $matC->id, 'warehouse_id' => $warehouseC->id, 'quantity' => 30, 'remaining_quantity' => 30, 'unit_price' => 30, 'total_cost' => 900, 'date' => now()->toDateString(), 'entry_code' => 'MSK-C']);

        $user = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id, 'warehouse_id' => $warehouseA->id]);
        $user->clusters()->sync([$cluster->id]);
        $user->warehouses()->sync([$warehouseA->id, $warehouseB->id]);

        $this->actingAs($user);

        // Attempting to transfer out of unassigned Warehouse C fails validation
        Livewire::test(\App\Livewire\Logistik\InventoryTransfers::class)
            ->set('source_warehouse_id', (string) $warehouseC->id)
            ->set('destination_warehouse_id', (string) $warehouseA->id)
            ->set('inventory_type', 'material')
            ->set('item_id', (string) $matC->id)
            ->set('stock_in_id', (string) $stockC->id)
            ->set('quantity', '5')
            ->set('transferred_at', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['source_warehouse_id']);
    }

    public function test_logistik_can_access_biaya_cluster_page_and_expenses(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);
        $clusterC = Cluster::create(['name' => 'Cluster Gamma']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);

        $user = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $clusterA->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $user->clusters()->sync([$clusterA->id, $clusterB->id]);
        $user->warehouses()->sync([$warehouse->id]);

        $this->actingAs($user);

        // Multi-cluster logistik can access /biaya-cluster and see only assigned clusters
        $response = $this->get(route('logistik.cluster-costs'));
        $response->assertOk();
        $response->assertSee('Cluster Alpha');
        $response->assertSee('Cluster Beta');
        $response->assertDontSee('Cluster Gamma');

        // Can access expenses for assigned clusters
        $this->get(route('clusters.expenses', $clusterA))->assertOk();
        $this->get(route('clusters.expenses', $clusterB))->assertOk();

        // Forbidden for unassigned cluster
        $this->get(route('clusters.expenses', $clusterC))->assertForbidden();
    }

    public function test_single_cluster_logistik_redirects_from_biaya_cluster_to_expenses(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);

        $user = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $clusterA->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $user->clusters()->sync([$clusterA->id]);
        $user->warehouses()->sync([$warehouse->id]);

        $this->actingAs($user);

        // Visiting /biaya-cluster redirects directly to cluster expense detail
        $response = $this->get(route('logistik.cluster-costs'));
        $response->assertRedirect(route('clusters.expenses', $clusterA));
    }

    public function test_scoped_logistik_can_export_house_costs_and_houses(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);

        House::factory()->create(['cluster_id' => $clusterA->id, 'name' => 'House A', 'house_code' => 'A-01']);
        House::factory()->create(['cluster_id' => $clusterB->id, 'name' => 'House B', 'house_code' => 'B-01']);

        $user = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $clusterA->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $user->clusters()->sync([$clusterA->id, $clusterB->id]);
        $user->warehouses()->sync([$warehouse->id]);

        $this->actingAs($user);

        // Biaya rumah export
        Livewire::test(\App\Livewire\Admin\HouseCosts::class)
            ->call('exportExcel')
            ->assertFileDownloaded();

        // Houses list export
        Livewire::test(\App\Livewire\Logistik\Houses::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}

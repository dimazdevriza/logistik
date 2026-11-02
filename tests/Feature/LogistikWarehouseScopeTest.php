<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Livewire\Logistik\InventoryTransfers;
use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\ToolLog;
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
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LogistikWarehouseScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_must_assign_warehouse_to_logistik_and_pengawas(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Barat']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach (['logistik', 'pengawas'] as $role) {
            Livewire::test(UserManagement::class)
                ->set('name', "User {$role}")
                ->set('email', "{$role}@example.test")
                ->set('password', 'password-123')
                ->set('role', $role)
                ->set('cluster_id', $cluster->id)
                ->call('save')
                ->assertHasErrors(['warehouse_id']);

            Livewire::test(UserManagement::class)
                ->set('name', "User {$role}")
                ->set('email', "{$role}@example.test")
                ->set('password', 'password-123')
                ->set('role', $role)
                ->set('cluster_id', $cluster->id)
                ->set('warehouse_id', $warehouse->id)
                ->call('save')
                ->assertHasNoErrors();

            $this->assertDatabaseHas('users', [
                'email' => "{$role}@example.test",
                'cluster_id' => $cluster->id,
                'warehouse_id' => $warehouse->id,
            ]);
        }
    }

    public function test_assigned_logistik_and_pengawas_only_see_materials_in_assigned_warehouse(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $warehouseA = Warehouse::firstOrCreate(['name' => 'Gudang A']);
        $warehouseB = Warehouse::firstOrCreate(['name' => 'Gudang B']);

        $materialA = Material::create([
            'warehouse_id' => $warehouseA->id,
            'code' => 'MAT-A01',
            'name' => 'Semen Gudang A',
            'unit' => 'sak',
            'unit_price' => 60000,
            'stock' => 20,
        ]);
        StockIn::create([
            'material_id' => $materialA->id,
            'warehouse_id' => $warehouseA->id,
            'quantity' => 20,
            'remaining_quantity' => 20,
            'unit_price' => 60000,
            'total_cost' => 1200000,
            'date' => now()->toDateString(),
        ]);

        $materialB = Material::create([
            'warehouse_id' => $warehouseB->id,
            'code' => 'MAT-B01',
            'name' => 'Besi Gudang B',
            'unit' => 'batang',
            'unit_price' => 85000,
            'stock' => 15,
        ]);
        StockIn::create([
            'material_id' => $materialB->id,
            'warehouse_id' => $warehouseB->id,
            'quantity' => 15,
            'remaining_quantity' => 15,
            'unit_price' => 85000,
            'total_cost' => 1275000,
            'date' => now()->toDateString(),
        ]);

        foreach (['logistik', 'pengawas'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'cluster_id' => $cluster->id,
                'warehouse_id' => $warehouseA->id,
            ]);

            $this->actingAs($user);

            Livewire::test(Materials::class)
                ->assertSee('Semen Gudang A')
                ->assertDontSee('Besi Gudang B')
                ->assertDontSeeHtml('wire:model.live="filterWarehouse"')
                ->set('filterWarehouse', (string) $warehouseB->id)
                ->assertSee('Semen Gudang A')
                ->assertDontSee('Besi Gudang B')
                ->call('resetFilters')
                ->assertSet('filterWarehouse', (string) $warehouseA->id);

            Livewire::test(Materials::class)
                ->call('edit', $materialB->id)
                ->assertForbidden();
        }
    }

    public function test_assigned_logistik_cannot_spoof_warehouse_when_creating_material(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $warehouseA = Warehouse::firstOrCreate(['name' => 'Gudang A']);
        $warehouseB = Warehouse::firstOrCreate(['name' => 'Gudang B']);
        $logistik = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $cluster->id,
            'warehouse_id' => $warehouseA->id,
        ]);

        $this->actingAs($logistik);

        Livewire::test(Materials::class)
            ->call('create')
            ->set('name', 'Pasir Pasang')
            ->set('category_name', 'Material Alam')
            ->set('supplier_name', 'TB Jaya')
            ->set('unit', 'm3')
            ->set('unit_price', 250000)
            ->set('stock', 5)
            ->set('warehouse_id', (string) $warehouseB->id)
            ->set('receiptReceivedAt', now()->subMinutes(5)->format('Y-m-d\TH:i'))
            ->set('receiptSubmissionKey', (string) Str::uuid())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materials', [
            'name' => 'Pasir Pasang',
            'warehouse_id' => $warehouseA->id,
        ]);
    }

    public function test_assigned_logistik_only_sees_tools_and_allocations_from_assigned_warehouse(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $house = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'Blok A-01']);
        $warehouseA = Warehouse::firstOrCreate(['name' => 'Gudang A']);
        $warehouseB = Warehouse::firstOrCreate(['name' => 'Gudang B']);

        $toolA = Tool::create([
            'warehouse_id' => $warehouseA->id,
            'code' => 'TL-A01',
            'name' => 'Bor Gudang A',
            'condition' => 'baik',
            'purchase_price' => 500000,
            'total_qty' => 4,
            'available_qty' => 4,
            'qty_broken' => 0,
        ]);
        ToolWarehouseBalance::updateOrCreate(
            ['tool_id' => $toolA->id, 'warehouse_id' => $warehouseA->id],
            ['available_qty' => 4, 'qty_broken' => 0]
        );

        $toolB = Tool::create([
            'warehouse_id' => $warehouseB->id,
            'code' => 'TL-B01',
            'name' => 'Gerinda Gudang B',
            'condition' => 'baik',
            'purchase_price' => 400000,
            'total_qty' => 3,
            'available_qty' => 3,
            'qty_broken' => 0,
        ]);
        ToolWarehouseBalance::updateOrCreate(
            ['tool_id' => $toolB->id, 'warehouse_id' => $warehouseB->id],
            ['available_qty' => 3, 'qty_broken' => 0]
        );

        $materialA = Material::create([
            'warehouse_id' => $warehouseA->id,
            'code' => 'MAT-A02',
            'name' => 'Cat Gudang A',
            'unit' => 'pail',
            'unit_price' => 300000,
            'stock' => 10,
        ]);
        $batchA = StockIn::create([
            'material_id' => $materialA->id,
            'warehouse_id' => $warehouseA->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 300000,
            'total_cost' => 3000000,
            'date' => now()->toDateString(),
        ]);

        $materialB = Material::create([
            'warehouse_id' => $warehouseB->id,
            'code' => 'MAT-B02',
            'name' => 'Cat Gudang B',
            'unit' => 'pail',
            'unit_price' => 300000,
            'stock' => 10,
        ]);
        $batchB = StockIn::create([
            'material_id' => $materialB->id,
            'warehouse_id' => $warehouseB->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 300000,
            'total_cost' => 3000000,
            'date' => now()->toDateString(),
        ]);

        $logistik = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $cluster->id,
            'warehouse_id' => $warehouseA->id,
        ]);

        $this->actingAs($logistik);

        Livewire::test(Tools::class)
            ->assertSee('Bor Gudang A')
            ->assertDontSee('Gerinda Gudang B')
            ->assertDontSeeHtml('wire:model.live="filterWarehouse"');

        $transaksi = Livewire::test(TransaksiLogistik::class);
        $this->assertTrue($transaksi->instance()->getMaterials()->contains('id', $materialA->id));
        $this->assertFalse($transaksi->instance()->getMaterials()->contains('id', $materialB->id));
        $this->assertTrue($transaksi->instance()->getTools()->contains('id', $toolA->id));
        $this->assertFalse($transaksi->instance()->getTools()->contains('id', $toolB->id));

        // Warehouses redirects directly to assigned WarehouseDetail, which includes Edit Gudang
        Livewire::test(Warehouses::class)
            ->assertRedirect(route('logistik.warehouse-detail', ['warehouse' => $warehouseA->id]));

        $pengawas = User::factory()->create([
            'role' => 'pengawas',
            'cluster_id' => $cluster->id,
        ]);
        $this->actingAs($pengawas);
        Livewire::test(\App\Livewire\Logistik\Clusters::class)
            ->assertRedirect(route('clusters.expenses', ['cluster' => $cluster->id]));
        Livewire::test(\App\Livewire\Logistik\ClusterExpenses::class, ['cluster' => $cluster])
            ->assertSee('Cluster A')
            ->assertDontSee('Kembali ke Biaya Cluster')
            ->assertDontSee('Kembali ke Cluster');
        $this->actingAs($logistik);

        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouseA])
            ->assertSee('Gudang A')
            ->assertDontSee('Kembali ke Gudang')
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->set('name', 'Gudang A Baru')
            ->set('address', 'Jl. Proyek Blok A')
            ->call('saveWarehouse')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouseA->id,
            'name' => 'Gudang A Baru',
            'address' => 'Jl. Proyek Blok A',
        ]);
        $warehouseA->update(['name' => 'Gudang A']);

        Livewire::test(WarehouseDetail::class, ['warehouse' => $warehouseB])
            ->assertForbidden();

        Livewire::test(InventoryTransfers::class)
            ->assertSet('source_warehouse_id', (string) $warehouseA->id)
            ->assertSeeHtml('id="transfer-source-warehouse"')
            ->assertSee('Gudang B')
            ->set('source_warehouse_id', (string) $warehouseB->id)
            ->set('destination_warehouse_id', (string) $warehouseA->id)
            ->set('item_id', (string) $materialB->id)
            ->assertSet('stock_in_id', (string) $batchB->id)
            ->call('swapWarehouses')
            ->assertSet('source_warehouse_id', (string) $warehouseA->id)
            ->assertSet('destination_warehouse_id', (string) $warehouseB->id);

        $transferDestBatch = StockIn::create([
            'material_id' => $materialB->id,
            'warehouse_id' => $warehouseB->id,
            'entry_type' => 'transfer',
            'entry_code' => 'TRF-MAT-001',
            'quantity' => 2,
            'remaining_quantity' => 2,
            'unit_price' => 300000,
            'total_cost' => 600000,
            'date' => now()->toDateString(),
        ]);
        \App\Models\InventoryTransfer::create([
            'transfer_code' => 'TRF-001',
            'source_warehouse_id' => $warehouseA->id,
            'destination_warehouse_id' => $warehouseB->id,
            'material_id' => $materialA->id,
            'destination_material_id' => $materialB->id,
            'source_stock_in_id' => $batchA->id,
            'destination_stock_in_id' => $transferDestBatch->id,
            'quantity' => 2,
            'created_by' => $logistik->id,
            'transferred_at' => now()->toDateString(),
        ]);

        \App\Models\MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $materialA->id,
            'stock_in_id' => $batchA->id,
            'user_id' => $logistik->id,
            'transaction_code' => 'OUT-A01',
            'quantity' => 1,
            'unit_price_at_usage' => 300000,
            'total_cost' => 300000,
            'usage_date' => now()->toDateString(),
        ]);
        \App\Models\MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $materialB->id,
            'stock_in_id' => $batchB->id,
            'user_id' => $logistik->id,
            'transaction_code' => 'OUT-B01',
            'quantity' => 1,
            'unit_price_at_usage' => 300000,
            'total_cost' => 300000,
            'usage_date' => now()->toDateString(),
        ]);

        $usageA = \App\Models\ToolUsage::create([
            'house_id' => $house->id,
            'tool_id' => $toolA->id,
            'warehouse_id' => $warehouseA->id,
            'user_id' => $logistik->id,
            'transaction_code' => 'TL-OUT-A01',
            'quantity' => 1,
            'checkout_date' => now()->toDateString(),
        ]);
        $usageB = \App\Models\ToolUsage::create([
            'house_id' => $house->id,
            'tool_id' => $toolB->id,
            'warehouse_id' => $warehouseB->id,
            'user_id' => $logistik->id,
            'transaction_code' => 'TL-OUT-B01',
            'quantity' => 1,
            'checkout_date' => now()->toDateString(),
        ]);

        \App\Models\ToolReturnLog::create([
            'tool_usage_id' => $usageA->id,
            'house_id' => $house->id,
            'tool_id' => $toolA->id,
            'receiving_warehouse_id' => $warehouseA->id,
            'quantity' => 1,
            'report_type' => 'broken',
            'status' => 'pending',
            'notes' => 'Rusak di Gudang A',
            'reported_by' => $logistik->id,
            'received_at' => now(),
        ]);
        \App\Models\ToolReturnLog::create([
            'tool_usage_id' => $usageB->id,
            'house_id' => $house->id,
            'tool_id' => $toolB->id,
            'receiving_warehouse_id' => $warehouseB->id,
            'quantity' => 1,
            'report_type' => 'broken',
            'status' => 'pending',
            'notes' => 'Rusak di Gudang B',
            'reported_by' => $logistik->id,
            'received_at' => now(),
        ]);

        Livewire::test(MaterialLog::class)
            ->assertSee($batchA->entry_code)
            ->assertSee('Gudang A')
            ->assertSee('TRF-MAT-001')
            ->assertSee('OUT-A01')
            ->assertDontSee('OUT-B01')
            ->assertDontSee($batchB->entry_code)
            ->call('openReceiptHistory', $batchB->id)
            ->assertForbidden();

        Livewire::test(ToolLog::class)
            ->assertSee('Bor Gudang A')
            ->assertSee('TL-OUT-A01')
            ->assertDontSee('TL-OUT-B01')
            ->assertSee('Rusak di Gudang A')
            ->assertDontSee('Rusak di Gudang B')
            ->assertDontSee('Gerinda Gudang B');

        $otherCluster = Cluster::create(['name' => 'Cluster B']);
        $otherHouse = House::factory()->create(['cluster_id' => $otherCluster->id, 'name' => 'Blok B-01']);

        Livewire::test(\App\Livewire\Logistik\HouseDetail::class, ['house' => $otherHouse])
            ->assertForbidden();

        Livewire::test(\App\Livewire\Logistik\HouseFinish::class, ['house' => $otherHouse])
            ->assertForbidden();

        Livewire::test(\App\Livewire\Logistik\HouseFinish::class, ['house' => $house])
            ->assertSet("toolSelections.{$usageA->id}.receiving_warehouse_id", $warehouseA->id);

        // Sidebar indicator shows both cluster and warehouse
        $this->get('/materials')
            ->assertOk()
            ->assertSee('Cluster A • Gudang A');

        // ImportWarehouse defaults to assigned warehouse and rejects other warehouses
        $this->assertSame($warehouseA->id, \App\Support\ImportWarehouse::resolve([], 2, 'Semen'));
        $this->expectException(\RuntimeException::class);
        \App\Support\ImportWarehouse::resolve(['gudang' => 'Gudang B'], 2, 'Semen');
    }
}

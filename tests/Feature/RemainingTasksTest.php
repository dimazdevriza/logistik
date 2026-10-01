<?php

namespace Tests\Feature;

use App\Exports\MaterialLogExport;
use App\Livewire\Logistik\ClusterExpenses;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Livewire\Logistik\Dispatches;
use App\Livewire\Logistik\HouseFinish;
use App\Livewire\Logistik\HouseDetail;
use App\Livewire\Logistik\InventoryTransfers;
use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\ToolLog;
use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\House;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RemainingTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_house_detail_lists_vendor_rentals_assigned_to_the_house(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $cluster = Cluster::create(['name' => 'TEST Rental Detail Cluster']);
        $house = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'TEST Rental House']);
        $sharedHouse = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'TEST Shared Rental House']);
        $otherHouse = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'TEST Other Rental House']);

        $rental = ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'created_by' => $user->id,
            'type' => 'rental',
            'description' => 'TEST Mini Excavator',
            'vendor' => 'TEST Rental Vendor',
            'quantity' => 1,
            'start_date' => '2026-09-20',
            'due_date' => '2026-10-20',
            'amount' => 1500000,
            'status' => 'active',
        ]);
        $rental->houses()->attach([$house->id, $sharedHouse->id]);
        $extension = ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'parent_expense_id' => $rental->id,
            'created_by' => $user->id,
            'type' => 'rental_extension',
            'description' => 'TEST Mini Excavator',
            'vendor' => 'TEST Rental Vendor',
            'quantity' => 1,
            'start_date' => '2026-10-21',
            'due_date' => '2026-11-20',
            'amount' => 400000,
            'status' => 'active',
        ]);
        $extension->houses()->attach([$house->id, $sharedHouse->id]);
        $otherRental = ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'created_by' => $user->id,
            'type' => 'rental',
            'description' => 'TEST Other House Rental',
            'vendor' => 'TEST Other Vendor',
            'quantity' => 1,
            'start_date' => '2026-09-20',
            'due_date' => '2026-10-20',
            'amount' => 200000,
            'status' => 'active',
        ]);
        $otherRental->houses()->attach($otherHouse->id);

        Livewire::test(HouseDetail::class, ['house' => $house])
            ->assertSee('Jasa Vendor')
            ->assertSee('Sewa Alat')
            ->set('activeTab', 'vendor-rental')
            ->assertSee('Sewa Alat Vendor')
            ->assertSee('TEST Mini Excavator')
            ->assertSee('Perpanjangan sewa')
            ->assertSee('Satu transaksi untuk 2 rumah')
            ->assertSee('Rp 1.500.000')
            ->assertDontSee('TEST Other House Rental')
            ->set('vendorRentalSearch', 'TEST Other Vendor')
            ->assertSee('Tidak ada sewa alat yang cocok dengan pencarian.');
    }

    public function test_vendor_rental_allocation_links_multiple_houses_and_only_active_rentals_can_be_extended(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $cluster = Cluster::create(['name' => 'TEST Cluster Expense']);
        $houses = House::factory()->count(2)->create(['cluster_id' => $cluster->id]);

        Livewire::test(ClusterExpenses::class, ['cluster' => $cluster])
            ->set('type', 'rental')
            ->set([
                'description' => 'TEST Rental Outside Allocation',
                'amount' => '1',
            ])
            ->call('save')
            ->assertHasErrors('type');

        $allocation = Livewire::test(TransaksiLogistik::class)
            ->set([
                'house_ids' => $houses->pluck('id')->all(),
                'rental_description' => 'TEST Rental Excavator',
                'rental_vendor' => 'TEST Vendor',
                'rental_quantity' => '1',
                'rental_start_date' => '2026-09-19',
                'rental_due_date' => '2026-10-19',
                'rental_amount' => '1500000',
            ])
            ->call('saveRental')
            ->assertHasErrors('rental_bill_image')
            ->set('rental_bill_image', UploadedFile::fake()->image('test-rental-bill.jpg'))
            ->call('saveRental')
            ->assertHasNoErrors();

        $rental = $cluster->expenses()->where('type', 'rental')->firstOrFail();
        $this->assertSame(1, $cluster->expenses()->where('type', 'rental')->count());
        $this->assertEqualsCanonicalizing(
            $houses->modelKeys(),
            $rental->houses()->pluck('houses.id')->all(),
        );
        $this->assertSame('1500000.00', $rental->amount);

        Livewire::test(ClusterExpenses::class, ['cluster' => $cluster])
            ->call('startExtension', $rental->id)
            ->set([
                'description' => 'TEST Rental Extension',
                'start_date' => '2026-10-20',
                'due_date' => '2026-11-19',
                'amount' => '400000',
                'bill_image' => UploadedFile::fake()->image('test-extension-bill.jpg'),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $extension = $cluster->expenses()->where('type', 'rental_extension')->firstOrFail();
        $this->assertSame($rental->id, $extension->parent_expense_id);
        $this->assertEqualsCanonicalizing($houses->modelKeys(), $extension->houses()->pluck('houses.id')->all());

        $staleExtension = Livewire::test(ClusterExpenses::class, ['cluster' => $cluster])
            ->call('startExtension', $rental->id)
            ->set([
                'description' => 'TEST Stale Extension',
                'start_date' => '2026-10-20',
                'due_date' => '2026-11-19',
                'amount' => '100000',
                'bill_image' => UploadedFile::fake()->image('test-stale-extension.jpg'),
            ]);
        $rental->update(['status' => 'returned']);
        $staleExtension
            ->call('save')
            ->assertHasErrors('parent_expense_id');

        Livewire::test(ClusterExpenses::class, ['cluster' => $cluster])
            ->call('startExtension', $rental->id)
            ->assertHasErrors('parent_expense_id');
        $this->assertSame(2, $cluster->expenses()->count());
    }

    public function test_import_batch_records_the_opening_balance_cutoff_policy(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $file = UploadedFile::fake()->create('TEST-opening-balance.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        ImportBatch::run('material', $file, fn () => (object) [
            'totalRows' => 0,
            'successfulRows' => 0,
            'skippedRows' => 0,
        ]);

        $batch = ImportBatch::latest('id')->firstOrFail();
        $this->assertSame('2026-09-19', $batch->cutoff_date?->toDateString());
        $this->assertSame('opening_balance', $batch->migration_mode);
    }

    public function test_material_transfer_preserves_price_batch_and_creates_audit_record(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'TEST Gudang Transfer']);
        $material = Material::factory()->create([
            'warehouse_id' => $source->id,
            'name' => 'TEST Material Transfer',
            'unit' => 'sak',
            'unit_price' => 97500,
            'stock' => 10,
        ]);

        $firstBatch = StockIn::create([
            'entry_code' => 'TEST-TRANSFER-SOURCE-A',
            'entry_type' => 'receipt',
            'material_id' => $material->id,
            'warehouse_id' => $source->id,
            'user_id' => $user->id,
            'quantity' => 6,
            'remaining_quantity' => 6,
            'unit_price' => 97500,
            'total_cost' => 585000,
            'date' => '2026-09-19',
        ]);
        $secondBatch = StockIn::create([
            'entry_code' => 'TEST-TRANSFER-SOURCE-B',
            'entry_type' => 'receipt',
            'material_id' => $material->id,
            'warehouse_id' => $source->id,
            'user_id' => $user->id,
            'quantity' => 4,
            'remaining_quantity' => 4,
            'unit_price' => 97500,
            'total_cost' => 390000,
            'date' => '2026-09-19',
        ]);

        $transferForm = Livewire::test(InventoryTransfers::class)
            ->set([
                'inventory_type' => 'material',
                'source_warehouse_id' => $source->id,
                'destination_warehouse_id' => $target->id,
                'item_id' => $material->id,
                'stock_in_id' => $firstBatch->id,
                'quantity' => '4',
                'transferred_at' => '2026-09-19',
                'notes' => 'TEST transfer',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $transferForm->set('item_id', $material->id)
            ->set('stock_in_id', $secondBatch->id)
            ->set('quantity', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(4.0, (float) $material->fresh()->stock);
        $this->assertSame(2.0, (float) $firstBatch->fresh()->remaining_quantity);
        $this->assertSame(2.0, (float) $secondBatch->fresh()->remaining_quantity);
        $material->update(['unit_price' => 125000]);
        $destination = Material::where('warehouse_id', $target->id)->where('name', $material->name)->firstOrFail();
        $this->assertSame(6.0, (float) $destination->stock);
        $this->assertSame(97500.0, (float) $destination->unit_price);
        $transfers = \App\Models\InventoryTransfer::with(['sourceBatch', 'destinationBatch'])->orderBy('id')->get();
        $this->assertCount(2, $transfers);
        $this->assertSame($firstBatch->id, $transfers[0]->source_stock_in_id);
        $this->assertSame($secondBatch->id, $transfers[1]->source_stock_in_id);
        foreach ($transfers as $transfer) {
            $this->assertSame($destination->id, $transfer->destination_material_id);
            $this->assertSame($transfer->transfer_code, $transfer->destinationBatch->entry_code);
            $this->assertSame('transfer', $transfer->destinationBatch->entry_type);
            $this->assertSame(0.0, (float) $transfer->destinationBatch->total_cost);
            $this->assertSame(97500.0, (float) $transfer->destinationBatch->unit_price);
        }
        $this->assertCount(2, StockIn::where('material_id', $destination->id)->get());
        Livewire::test(MaterialLog::class)
            ->assertSee('Transfer')
            ->assertSee('Antargudang')
            ->assertSee('Transfer gudang')
            ->assertSee('Asal: Gudang Utama; tujuan: TEST Gudang Transfer');
        Livewire::test(InventoryTransfers::class)
            ->assertDontSee('Riwayat transfer gudang')
            ->assertDontSee($transfers[0]->transfer_code);
        $transferLog = collect((new MaterialLogExport(filterType: 'masuk'))->collection())
            ->firstWhere('type', 'transfer_masuk');
        $this->assertSame('Transfer Gudang', (new MaterialLogExport(filterType: 'masuk'))->map($transferLog)[2]);
        $request = MaterialToolRequest::create([
            'request_code' => 'TEST-TRANSFER-DISPATCH-'.\Illuminate\Support\Str::ulid(),
            'requester_id' => $user->id,
            'house_id' => House::factory()->create(['name' => '[TEST] Transfer dispatch house'])->id,
            'type' => 'material',
            'material_id' => $destination->id,
            'quantity' => 1,
            'status' => 'pending',
            'notes' => '[TEST] Destination transfer batch dispatchability',
        ]);
        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->assertSet('showDispatchModal', true)
            ->assertSee($transfers[0]->destinationBatch->entry_code);
        Livewire::test(Materials::class)
            ->call('confirm', 'delete', $destination->id)
            ->assertHasErrors('delete')
            ->assertSet('showConfirmation', false);
    }

    public function test_tool_transfer_moves_available_units_between_warehouse_balances_and_creates_audit_record(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'TEST Gudang Alat Tujuan']);
        $tool = Tool::factory()->create([
            'warehouse_id' => $source->id,
            'code' => 'TEST-TRANSFER-TOOL',
            'total_qty' => 2,
            'available_qty' => 2,
        ]);

        Livewire::test(InventoryTransfers::class)
            ->set([
                'inventory_type' => 'tool',
                'source_warehouse_id' => $source->id,
                'destination_warehouse_id' => $target->id,
                'item_id' => $tool->id,
                'quantity' => '1',
                'transferred_at' => '2026-09-19',
                'notes' => 'TEST tool transfer',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($source->id, $tool->fresh()->warehouse_id);
        $this->assertDatabaseHas('tool_warehouse_balances', ['tool_id' => $tool->id, 'warehouse_id' => $source->id, 'available_qty' => 1]);
        $this->assertDatabaseHas('tool_warehouse_balances', ['tool_id' => $tool->id, 'warehouse_id' => $target->id, 'available_qty' => 1]);
        $this->assertDatabaseHas('inventory_transfers', ['tool_id' => $tool->id, 'quantity' => 1]);
        $transfer = \App\Models\InventoryTransfer::where('tool_id', $tool->id)->firstOrFail();
        Livewire::test(ToolLog::class)
            ->assertSee($transfer->transfer_code)
            ->assertSee('Transfer')
            ->assertSee('Asal: Gudang Utama; tujuan: TEST Gudang Alat Tujuan');
        Livewire::test(InventoryTransfers::class)
            ->assertDontSee('Riwayat transfer gudang')
            ->assertDontSee($transfer->transfer_code);
    }

    public function test_house_completion_requires_full_mixed_tool_accounting_and_records_receiver(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'TEST Gudang Pengembalian']);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $tool = Tool::factory()->create([
            'warehouse_id' => $source->id,
            'total_qty' => 5,
            'available_qty' => 0,
            'qty_broken' => 0,
        ]);
        $usage = ToolUsage::create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'user_id' => $user->id,
            'quantity' => 5,
            'checkout_date' => '2026-09-10',
        ]);

        Livewire::test(HouseFinish::class, ['house' => $house])
            ->set("toolSelections.{$usage->id}", [
                'qty_good' => 3,
                'qty_broken' => 1,
                'qty_lost' => 1,
                'receiving_warehouse_id' => $target->id,
                'notes' => 'TEST mixed return',
            ])
            ->call('processCompletion')
            ->assertHasNoErrors();

        $this->assertSame('selesai', $house->fresh()->status);
        $this->assertNotNull($house->fresh()->completed_at);
        $this->assertSame($source->id, $tool->fresh()->warehouse_id);
        $this->assertDatabaseHas('tool_warehouse_balances', ['tool_id' => $tool->id, 'warehouse_id' => $source->id, 'available_qty' => 0, 'qty_broken' => 0]);
        $this->assertDatabaseHas('tool_warehouse_balances', ['tool_id' => $tool->id, 'warehouse_id' => $target->id, 'available_qty' => 3, 'qty_broken' => 1]);
        $this->assertSame(4, $tool->fresh()->total_qty);
        $this->assertSame(3, $tool->fresh()->available_qty);
        $this->assertSame(3, ToolReturnLog::where('tool_usage_id', $usage->id)->count());
    }

    public function test_house_completion_allows_cross_warehouse_return_when_batch_units_remain_at_source(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'TEST Gudang Aman']);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $tool = Tool::factory()->create([
            'name' => 'TEST Alat Batch Terbagi',
            'warehouse_id' => $source->id,
            'total_qty' => 2,
            'available_qty' => 1,
            'qty_broken' => 0,
        ]);
        $usage = ToolUsage::create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'checkout_date' => '2026-09-10',
        ]);

        Livewire::test(HouseFinish::class, ['house' => $house])
            ->set("toolSelections.{$usage->id}", [
                'qty_good' => 1,
                'qty_broken' => 0,
                'qty_lost' => 0,
                'receiving_warehouse_id' => $target->id,
                'notes' => '[TEST] batch remains at source',
            ])
            ->call('processCompletion')
            ->assertHasNoErrors();

        $this->assertSame('selesai', $house->fresh()->status);
        $this->assertSame($source->id, $tool->fresh()->warehouse_id);
        $this->assertSame(2, (int) $tool->fresh()->available_qty);
        $this->assertDatabaseHas('tool_warehouse_balances', ['tool_id' => $tool->id, 'warehouse_id' => $source->id, 'available_qty' => 1]);
        $this->assertDatabaseHas('tool_warehouse_balances', ['tool_id' => $tool->id, 'warehouse_id' => $target->id, 'available_qty' => 1]);
        $this->assertDatabaseHas('tool_return_logs', ['tool_usage_id' => $usage->id, 'receiving_warehouse_id' => $target->id]);
    }

    public function test_house_completion_rejects_open_requests(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $material = Material::factory()->create();
        MaterialToolRequest::create([
            'request_code' => 'REQ-TEST-OPEN',
            'requester_id' => $user->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);

        Livewire::test(HouseFinish::class, ['house' => $house])
            ->call('processCompletion')
            ->assertHasErrors('completion');
        $this->assertSame('pembangunan', $house->fresh()->status);
    }

    public function test_house_completion_waits_for_rejected_dispatched_goods_to_return(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $material = Material::factory()->create(['stock' => 0]);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-TEST-REJECTED-RETURN',
            'requester_id' => $user->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 1,
            'status' => 'rejected',
            'dispatched_at' => now(),
        ]);

        Livewire::test(HouseFinish::class, ['house' => $house])
            ->call('processCompletion')
            ->assertHasErrors('completion');

        $request->update(['rejected_returned_at' => now()]);

        Livewire::test(HouseFinish::class, ['house' => $house])
            ->call('processCompletion')
            ->assertHasNoErrors();

        $this->assertSame('selesai', $house->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Dispatches;
use App\Livewire\Logistik\InventoryTransfers;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MaterialToolRequest;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LogistikBatchReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_allocation_dispatches_immediately_and_consumes_the_selected_batch(): void
    {
        Storage::fake('public');
        $warehouse = Warehouse::create(['name' => 'TEST Reservation Warehouse']);
        $cluster = Cluster::create(['name' => 'TEST Reservation Cluster']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $house = House::factory()->create(['cluster_id' => $cluster->id]);
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 10]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 12500,
            'total_cost' => 125000,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($logistik);
        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$house->id])
            ->set('material_id', $material->id)
            ->call('selectMaterial', $material->id)
            ->set('material_batch_id', $batch->id)
            ->set('material_quantity', 7)
            ->set('material_notes', 'TEST selected batch reservation')
            ->call('showMaterialConfirmationModal')
            ->assertSet('showMaterialConfirmation', true)
            ->set('materialAllocationProofImage', UploadedFile::fake()->image('TEST-material-allocation.jpg'))
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->assertSet('showMaterialConfirmation', false);

        $usage = MaterialUsage::where('house_id', $house->id)->sole();
        $this->assertSame($batch->id, $usage->stock_in_id);
        $this->assertSame(3.0, (float) $batch->fresh()->remaining_quantity);
        $this->assertSame(3.0, $batch->fresh()->available_quantity);
        $this->assertSame(3.0, (float) $material->fresh()->stock);
        Storage::disk('public')->assertExists($usage->proof_image);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $destination = Warehouse::create(['name' => 'TEST Reservation Destination']);
        Livewire::test(InventoryTransfers::class)
            ->set('source_warehouse_id', $warehouse->id)
            ->set('destination_warehouse_id', $destination->id)
            ->set('item_id', $material->id)
            ->set('stock_in_id', $batch->id)
            ->set('quantity', 4)
            ->call('save')
            ->assertHasErrors(['stock_in_id']);

        $this->actingAs($logistik);
        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$house->id])
            ->set('material_id', $material->id)
            ->call('selectMaterial', $material->id)
            ->set('material_batch_id', $batch->id)
            ->set('material_quantity', 4)
            ->set('material_notes', 'TEST over-reservation')
            ->call('showMaterialConfirmationModal')
            ->assertHasErrors(['material_quantity']);
        $this->assertSame(1, MaterialUsage::where('house_id', $house->id)->count());

    }

    public function test_dispatch_consumes_the_logistiks_reserved_batch_instead_of_an_older_batch(): void
    {
        Storage::fake('public');
        $warehouse = Warehouse::create(['name' => 'TEST Batch Choice Warehouse']);
        $cluster = Cluster::create(['name' => 'TEST Batch Choice Cluster']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $house = House::factory()->create(['cluster_id' => $cluster->id]);
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 12]);
        $olderBatch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
            'remaining_quantity' => 4,
            'unit_price' => 100,
            'total_cost' => 400,
            'date' => now()->subDay()->toDateString(),
            'received_at' => now()->subDay(),
        ]);
        $chosenBatch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
            'remaining_quantity' => 8,
            'unit_price' => 200,
            'total_cost' => 1600,
            'date' => now()->toDateString(),
            'received_at' => now(),
        ]);

        $this->actingAs($logistik);
        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$house->id])
            ->set('material_id', $material->id)
            ->call('selectMaterial', $material->id)
            ->set('material_batch_id', $chosenBatch->id)
            ->set('material_quantity', 6)
            ->set('material_notes', 'TEST dispatch selected batch')
            ->call('showMaterialConfirmationModal')
            ->set('materialAllocationProofImage', UploadedFile::fake()->image('TEST-allocation.jpg'))
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->assertSet('showMaterialConfirmation', false);
        $usage = MaterialUsage::where('house_id', $house->id)->sole();

        $this->assertSame($chosenBatch->id, $usage->stock_in_id);
        $this->assertSame(200.0, (float) $usage->unit_price_at_usage);
        $this->assertSame(4.0, (float) $olderBatch->fresh()->remaining_quantity);
        $this->assertSame(2.0, (float) $chosenBatch->fresh()->remaining_quantity);
        $this->assertSame(2.0, $chosenBatch->fresh()->available_quantity);
        $this->assertSame(6.0, (float) $usage->quantity);
    }

    public function test_tool_allocation_dispatches_immediately_from_its_selected_warehouse(): void
    {
        Storage::fake('public');
        $warehouse = Warehouse::create(['name' => 'TEST Tool Reservation Warehouse']);
        $cluster = Cluster::create(['name' => 'TEST Tool Reservation Cluster']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $house = House::factory()->create(['cluster_id' => $cluster->id]);
        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'total_qty' => 4,
            'available_qty' => 4,
        ]);

        $this->actingAs($logistik);
        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$house->id])
            ->set('tool_id', $tool->id)
            ->set('tool_warehouse_id', $warehouse->id)
            ->set('tool_quantity', 3)
            ->set('tool_notes', 'TEST reserved tool')
            ->call('showToolConfirmationModal')
            ->assertSet('showToolConfirmation', true)
            ->set('toolAllocationProofImage', UploadedFile::fake()->image('TEST-tool-allocation.jpg'))
            ->call('saveTool')
            ->assertHasNoErrors()
            ->assertSet('showToolConfirmation', false);

        $usage = ToolUsage::where('house_id', $house->id)->sole();
        $this->assertSame($warehouse->id, $usage->warehouse_id);
        $this->assertSame(1, (int) $tool->fresh()->available_qty);
        $this->assertSame(3, (int) $usage->quantity);
        Storage::disk('public')->assertExists($usage->proof_image);

        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$house->id])
            ->set('tool_id', $tool->id)
            ->set('tool_warehouse_id', $warehouse->id)
            ->set('tool_quantity', 2)
            ->set('tool_notes', 'TEST over-reserved tool')
            ->call('showToolConfirmationModal')
            ->assertHasErrors(['tool_quantity']);

    }

    public function test_legacy_pending_request_dispatches_from_stock_left_unreserved(): void
    {
        Storage::fake('public');
        $warehouse = Warehouse::create(['name' => 'TEST Legacy Dispatch Warehouse']);
        $logistik = User::factory()->create(['role' => 'logistik']);
        $house = House::factory()->create();
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 5]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'remaining_quantity' => 5,
            'unit_price' => 300,
            'total_cost' => 1500,
            'date' => now()->toDateString(),
        ]);
        $request = MaterialToolRequest::create([
            'request_code' => 'TEST-LEGACY-BATCH-REQUEST',
            'requester_id' => $logistik->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 3,
            'status' => 'pending',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchProofImage', UploadedFile::fake()->create('TEST-legacy-dispatch.jpg', 100, 'image/jpeg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();

        $this->assertSame($batch->id, $request->fresh()->dispatchLines()->sole()->stock_in_id);
        $this->assertSame(2.0, (float) $batch->fresh()->remaining_quantity);
        $this->assertSame('dispatched', $request->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\Tools;
use App\Models\House;
use App\Models\InventoryTransfer;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_referenced_materials_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $house = House::factory()->create();
        foreach (['receipt', 'usage', 'voided_usage', 'request'] as $type) {
            $material = Material::factory()->create();
            $before = $material->fresh()->getRawOriginal();
            $record = match ($type) {
                'receipt' => StockIn::create(['material_id' => $material->id, 'user_id' => $user->id, 'quantity' => 2, 'unit_price' => 100, 'total_cost' => 200, 'date' => '2026-09-14']),
                'request' => MaterialToolRequest::create(['request_code' => 'DELETE-TEST', 'requester_id' => $user->id, 'house_id' => $house->id, 'type' => 'material', 'material_id' => $material->id, 'quantity' => 2, 'status' => 'pending']),
                default => MaterialUsage::create(['material_id' => $material->id, 'house_id' => $house->id, 'user_id' => $user->id, 'quantity' => 2, 'unit_price_at_usage' => 100, 'total_cost' => 200, 'usage_date' => '2026-09-14', 'voided_at' => $type === 'voided_usage' ? now() : null]),
            };
            $history = $record->fresh()->getRawOriginal();
            Livewire::test(Materials::class)->call('confirm', 'delete', $material->id)
                ->assertHasErrors('delete')->assertSet('showConfirmation', false)
                ->assertSee('Material tidak dapat dihapus');
            Livewire::test(Materials::class)->call('delete', $material->id)->assertHasErrors('delete');
            $this->assertSame($before, $material->fresh()->getRawOriginal());
            $this->assertSame($history, $record->fresh()->getRawOriginal());
        }
    }

    public function test_unreferenced_material_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $material = Material::factory()->create(['stock' => 0]);
        Livewire::test(Materials::class)->call('confirm', 'delete', $material->id)
            ->assertSet('showConfirmation', true)->call('executeConfirmedAction')
            ->assertHasNoErrors()->assertSet('showConfirmation', false);
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_inventory_transfer_history_protects_materials_and_tools(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $source = Warehouse::create(['name' => '[TEST] Transfer source', 'address' => 'A']);
        $destination = Warehouse::create(['name' => '[TEST] Transfer destination', 'address' => 'B']);

        $material = Material::factory()->create(['warehouse_id' => $source->id, 'stock' => 0]);
        $materialTransfer = InventoryTransfer::create([
            'transfer_code' => 'TRF-TEST-MATERIAL-HISTORY',
            'source_warehouse_id' => $source->id,
            'destination_warehouse_id' => $destination->id,
            'material_id' => $material->id,
            'quantity' => 1,
            'created_by' => $user->id,
            'transferred_at' => '2026-09-21',
        ]);

        Livewire::test(Materials::class)
            ->call('confirm', 'delete', $material->id)
            ->assertHasErrors('delete')
            ->assertSet('showConfirmation', false)
            ->assertSee('transfer gudang');
        $this->assertDatabaseHas('inventory_transfers', ['id' => $materialTransfer->id]);

        $tool = Tool::factory()->create(['warehouse_id' => $source->id, 'available_qty' => 1, 'total_qty' => 1]);
        $toolTransfer = InventoryTransfer::create([
            'transfer_code' => 'TRF-TEST-TOOL-HISTORY',
            'source_warehouse_id' => $source->id,
            'destination_warehouse_id' => $destination->id,
            'tool_id' => $tool->id,
            'quantity' => 1,
            'created_by' => $user->id,
            'transferred_at' => '2026-09-21',
        ]);

        Livewire::test(Tools::class)
            ->call('confirm', 'delete', $tool->id)
            ->assertSet('showConfirmation', true)
            ->call('executeConfirmedAction')
            ->assertHasErrors('delete')
            ->assertSet('showConfirmation', false)
            ->assertSee('transfer gudang');
        $this->assertDatabaseHas('inventory_transfers', ['id' => $toolTransfer->id]);
    }
}

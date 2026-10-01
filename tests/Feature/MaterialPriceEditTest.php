<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialPriceEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_edit_preserves_history_and_new_usage_uses_selected_batch_price(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Material Price Test']);
        $user = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $this->actingAs($user);
        $house = House::factory()->create(['cluster_id' => $cluster->id, 'status' => 'pembangunan']);
        $material = Material::factory()->create(['stock' => 8, 'unit_price' => 100]);
        $receipt = StockIn::create([
            'material_id' => $material->id, 'user_id' => $user->id,
            'quantity' => 10, 'unit_price' => 100, 'total_cost' => 1000,
            'date' => '2026-09-10', 'notes' => 'Stok awal',
        ]);
        $usage = MaterialUsage::create([
            'house_id' => $house->id, 'material_id' => $material->id, 'user_id' => $user->id,
            'quantity' => 2, 'unit_price_at_usage' => 100, 'total_cost' => 200,
            'usage_date' => '2026-09-10', 'notes' => 'Previous allocation',
        ]);
        $receiptBefore = $receipt->fresh()->getRawOriginal();
        $usageBefore = $usage->fresh()->getRawOriginal();

        Livewire::test(Materials::class)
            ->call('edit', $material->id)
            ->set('unit_price', 150)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertEquals(150, $material->fresh()->unit_price);
        $this->assertEquals(8, $material->fresh()->stock);
        $this->assertSame($receiptBefore, $receipt->fresh()->getRawOriginal());
        $this->assertSame($usageBefore, $usage->fresh()->getRawOriginal());
        $this->assertEquals(200, $house->total_material_cost);

        Livewire::test(TransaksiLogistik::class)
            ->set('material_id', $material->id)
            ->set('material_batch_id', $receipt->id)
            ->set('house_ids', [$house->id])
            ->set('material_quantity', 1)
            ->set('usage_date', now()->toDateString())
            ->set('material_notes', 'New allocation after price update')
            ->call('saveMaterial')
            ->assertHasNoErrors();

        $newUsage = MaterialUsage::where('material_id', $material->id)->where('id', '!=', $usage->id)->sole();
        $this->assertEquals(100, $newUsage->unit_price_at_usage);
        $this->assertEquals(100, $newUsage->total_cost);
        $this->assertEquals(7, $material->fresh()->stock);
        $this->assertEquals(300, $house->total_material_cost);
    }
}

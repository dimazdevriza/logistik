<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Livewire\Logistik\Dispatches;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\StockIn;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class LogistikClusterScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_a_cluster_to_a_logistik(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Tugas']);
        $logistik = User::factory()->create(['role' => 'logistik']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(UserManagement::class)
            ->call('edit', $logistik->id)
            ->set('cluster_id', $cluster->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($cluster->id, $logistik->fresh()->cluster_id);
    }

    public function test_admin_must_assign_a_cluster_to_logistik(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Logistik']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(UserManagement::class)
            ->set('name', 'Logistik Cluster')
            ->set('email', 'logistik-cluster@example.test')
            ->set('password', 'password-123')
            ->set('role', 'logistik')
            ->call('save')
            ->assertHasErrors(['cluster_id']);

        Livewire::test(UserManagement::class)
            ->set('name', 'Logistik Cluster')
            ->set('email', 'logistik-cluster@example.test')
            ->set('password', 'password-123')
            ->set('role', 'logistik')
            ->set('cluster_id', $cluster->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'logistik-cluster@example.test',
            'cluster_id' => $cluster->id,
        ]);
    }

    public function test_logistik_only_sees_houses_in_their_assigned_cluster(): void
    {
        $assigned = Cluster::create(['name' => 'Cluster Assigned']);
        $other = Cluster::create(['name' => 'Cluster Other']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $assigned->id]);
        $visibleHouse = House::factory()->create(['cluster_id' => $assigned->id, 'name' => 'Visible House']);
        $hiddenHouse = House::factory()->create(['cluster_id' => $other->id, 'name' => 'Hidden House']);

        $this->actingAs($logistik);

        Livewire::test(TransaksiLogistik::class)
            ->assertViewHas('houses', fn ($houses) => $houses->contains('id', $visibleHouse->id) && ! $houses->contains('id', $hiddenHouse->id));
    }

    public function test_house_search_does_not_keep_a_stale_selection_or_leak_another_cluster(): void
    {
        $assigned = Cluster::create(['name' => 'Cluster Search Assigned']);
        $other = Cluster::create(['name' => 'Cluster Search Other']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $assigned->id]);
        House::factory()->create([
            'cluster_id' => $assigned->id,
            'name' => 'Assigned A-50',
            'house_code' => 'ASSIGNED-A-50',
        ]);
        $outsideHouse = House::factory()->create([
            'cluster_id' => $other->id,
            'name' => 'Outside B-01',
            'house_code' => 'OUTSIDE-B-01',
        ]);

        $this->actingAs($logistik);

        Livewire::test(TransaksiLogistik::class)
            ->set('houseSearch', 'B-01')
            ->assertViewHas('houseResults', fn ($houses) => $houses->isEmpty())
            ->set('houseSearch', '')
            ->assertViewHas('houses', fn ($houses) => ! $houses->contains('id', $outsideHouse->id));
    }

    public function test_logistik_cannot_submit_a_request_for_another_cluster(): void
    {
        $assigned = Cluster::create(['name' => 'Cluster Assigned']);
        $other = Cluster::create(['name' => 'Cluster Other']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $assigned->id]);
        $outsideHouse = House::factory()->create(['cluster_id' => $other->id]);
        $warehouse = Warehouse::firstOrFail();
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 5]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'remaining_quantity' => 5,
            'unit_price' => 1000,
            'total_cost' => 5000,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($logistik);

        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$outsideHouse->id])
            ->set('material_id', $material->id)
            ->call('selectMaterial', $material->id)
            ->set('material_batch_id', $batch->id)
            ->set('material_quantity', 1)
            ->set('material_notes', 'Outside cluster attempt')
            ->call('showMaterialConfirmationModal')
            ->assertHasErrors(['house_ids']);

        $this->assertDatabaseMissing('material_tool_requests', [
            'requester_id' => $logistik->id,
            'house_id' => $outsideHouse->id,
        ]);
    }

    public function test_unassigned_logistik_cannot_request_for_an_unassigned_house(): void
    {
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => null]);
        $house = House::factory()->create(['cluster_id' => null]);
        $material = Material::factory()->create();
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $material->warehouse_id,
            'quantity' => $material->stock,
            'remaining_quantity' => $material->stock,
            'unit_price' => $material->unit_price,
            'total_cost' => $material->stock * $material->unit_price,
            'date' => now()->toDateString(),
        ]);
        $this->actingAs($logistik);

        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$house->id])
            ->set('material_id', $material->id)
            ->call('selectMaterial', $material->id)
            ->set('material_batch_id', $batch->id)
            ->set('material_quantity', 1)
            ->set('material_notes', 'Legacy unassigned account')
            ->call('showMaterialConfirmationModal')
            ->assertHasErrors(['house_ids']);

        $this->assertDatabaseMissing('material_tool_requests', [
            'requester_id' => $logistik->id,
            'house_id' => $house->id,
        ]);
    }

    public function test_logistik_cannot_allocate_to_a_house_outside_their_cluster(): void
    {
        $assigned = Cluster::create(['name' => 'Cluster Alokasi Assigned']);
        $other = Cluster::create(['name' => 'Cluster Alokasi Other']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $assigned->id]);
        $outsideHouse = House::factory()->create(['cluster_id' => $other->id]);
        $warehouse = Warehouse::firstOrFail();
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 10]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => $material->unit_price,
            'total_cost' => 10 * $material->unit_price,
            'date' => now()->toDateString(),
        ]);
        $this->actingAs($logistik);

        Livewire::test(TransaksiLogistik::class)
            ->set('house_ids', [$outsideHouse->id])
            ->set('material_id', $material->id)
            ->call('selectMaterial', $material->id)
            ->set('material_batch_id', $batch->id)
            ->set('material_quantity', 1)
            ->set('material_notes', 'Cross-cluster allocation attempt')
            ->call('saveMaterial')
            ->assertHasErrors(['house_ids']);

        $this->assertDatabaseMissing('material_usages', [
            'house_id' => $outsideHouse->id,
            'material_id' => $material->id,
        ]);
        $this->assertSame(10.0, (float) $material->fresh()->stock);
    }

    public function test_logistik_cannot_dispatch_a_request_for_another_cluster(): void
    {
        $assigned = Cluster::create(['name' => 'Cluster Dispatch Assigned']);
        $other = Cluster::create(['name' => 'Cluster Dispatch Other']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $assigned->id]);
        $otherLogistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $other->id]);
        $house = House::factory()->create(['cluster_id' => $other->id]);
        $material = Material::factory()->create(['stock' => 10]);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-CROSS-CLUSTER-01',
            'requester_id' => $otherLogistik->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 2,
            'notes' => 'Cross-cluster dispatch attempt',
            'status' => 'pending',
        ]);
        $this->actingAs($logistik);

        Livewire::test(Dispatches::class)
            ->assertDontSee($request->request_code);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame(10.0, (float) $material->fresh()->stock);
    }

    public function test_logistik_can_confirm_their_own_delivery_and_is_recorded_as_dispatcher_and_receiver(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Self Receipt']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $house = House::factory()->create(['cluster_id' => $cluster->id]);
        $warehouse = Warehouse::firstOrFail();
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 2, 'unit_price' => 100]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'remaining_quantity' => 2,
            'unit_price' => 100,
            'total_cost' => 200,
            'date' => now()->toDateString(),
        ]);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-LOGISTIK-SELF-RECEIPT-01',
            'requester_id' => $logistik->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 2,
            'notes' => 'Self confirmation',
            'status' => 'pending',
        ]);

        $this->actingAs($logistik);
        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [['source_id' => (string) $batch->id, 'quantity' => '2']])
            ->set('dispatchProofImage', UploadedFile::fake()->create('TEST-dispatch.jpg', 100, 'image/jpeg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();
        $line = $request->fresh()->dispatchLines()->firstOrFail();

        Livewire::test(Dispatches::class)
            ->call('openReceiptModal', $request->id)
            ->set('receiptLines.'.$line->id.'.received_quantity', 2)
            ->set('receiptLines.'.$line->id.'.damaged_quantity', 0)
            ->call('submitReceipt')
            ->assertHasNoErrors()
            ->assertSet('showReceiptModal', false);

        $this->assertSame($logistik->id, $request->fresh()->dispatcher_id);
        $this->assertDatabaseHas('dispatch_receipts', [
            'material_tool_request_id' => $request->id,
            'received_by_id' => $logistik->id,
        ]);
    }
}

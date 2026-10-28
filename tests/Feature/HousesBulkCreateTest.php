<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Houses;
use App\Models\Cluster;
use App\Models\House;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HousesBulkCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_previews_edits_and_saves_a_house_range_as_one_batch(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $cluster = Cluster::create(['name' => 'Cluster Uji Blok']);

        Livewire::test(Houses::class)
            ->call('create')
            ->set('houseCount', 10)
            ->set('bulkBlock', 'A')
            ->set('startingNumber', 1)
            ->set('type', 'Tipe 36')
            ->set('cluster_id', $cluster->id)
            ->call('previewBulkHouses')
            ->assertHasNoErrors()
            ->assertSet('bulkRows', fn (array $rows) => count($rows) === 10)
            ->assertSet('bulkRows.0.name', 'Blok A-01')
            ->assertSet('bulkRows.9.name', 'Blok A-10')
            ->set('bulkRows.1.name', 'Blok A-02 Tambahan')
            ->set('bulkRows.1.type', 'Tipe 45')
            ->call('removeBulkHouse', 2)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertDatabaseCount('houses', 9);
        $this->assertDatabaseHas('houses', [
            'name' => 'Blok A-02 Tambahan',
            'type' => 'Tipe 45',
            'cluster_id' => $cluster->id,
        ]);
        $this->assertDatabaseMissing('houses', ['name' => 'Blok A-03']);
    }

    public function test_it_blocks_existing_house_codes_in_the_preview(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        House::factory()->create([
            'name' => 'Blok A-01',
            'house_code' => House::generateCode('Blok A-01'),
        ]);

        Livewire::test(Houses::class)
            ->call('create')
            ->set('houseCount', 2)
            ->set('bulkBlock', 'A')
            ->set('startingNumber', 1)
            ->set('type', 'Tipe 36')
            ->call('previewBulkHouses')
            ->assertSet('bulkConflicts', [0])
            ->call('save')
            ->assertHasErrors('bulkRows');

        $this->assertDatabaseCount('houses', 1);
    }

    public function test_admin_and_logistik_can_only_choose_available_clusters_when_saving_houses(): void
    {
        $existingCluster = Cluster::create(['name' => 'Cluster Existing']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $existingCluster->id]);

        $this->actingAs($logistik);

        // Cannot create on-the-fly cluster with non-existent cluster name
        Livewire::test(Houses::class)
            ->call('create')
            ->assertSet('cluster_id', (string) $existingCluster->id)
            ->assertSet('cluster_name', 'Cluster Existing')
            ->set('name', 'Blok L-01')
            ->set('type', 'Tipe 36/72')
            ->set('cluster_id', '')
            ->set('cluster_name', 'Cluster Baru Logistik')
            ->call('save')
            ->assertHasErrors(['cluster_name' => 'Cluster tidak ditemukan. Pilih dari cluster yang tersedia.'])
            ->assertSet('showModal', true);

        $this->assertNull(Cluster::where('name', 'Cluster Baru Logistik')->first());

        // Saving with existing cluster name succeeds
        Livewire::test(Houses::class)
            ->call('create')
            ->set('name', 'Blok L-01')
            ->set('type', 'Tipe 36/72')
            ->set('cluster_id', '')
            ->set('cluster_name', 'cluster existing') // case-insensitive match
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('houses', [
            'name' => 'Blok L-01',
            'cluster_id' => $existingCluster->id,
        ]);

        // Bulk creation with non-existent cluster fails
        Livewire::test(Houses::class)
            ->call('create')
            ->set('houseCount', 2)
            ->set('bulkBlock', 'B')
            ->set('startingNumber', 1)
            ->set('type', 'Tipe 45')
            ->set('cluster_id', '')
            ->set('cluster_name', 'Cluster Bulk Baru')
            ->call('previewBulkHouses')
            ->call('save')
            ->assertHasErrors(['cluster_name' => 'Cluster tidak ditemukan. Pilih dari cluster yang tersedia.']);

        $this->assertNull(Cluster::where('name', 'Cluster Bulk Baru')->first());

        // Bulk creation with existing cluster succeeds
        Livewire::test(Houses::class)
            ->call('create')
            ->set('houseCount', 2)
            ->set('bulkBlock', 'B')
            ->set('startingNumber', 1)
            ->set('type', 'Tipe 45')
            ->set('cluster_id', (string) $existingCluster->id)
            ->set('cluster_name', 'Cluster Existing')
            ->call('previewBulkHouses')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('houses', [
            'name' => 'Blok B-01',
            'cluster_id' => $existingCluster->id,
        ]);

        // Edit house to Tanpa Cluster
        $house = House::where('name', 'Blok L-01')->firstOrFail();
        Livewire::test(Houses::class)
            ->call('edit', $house->id)
            ->assertSet('cluster_name', 'Cluster Existing')
            ->set('cluster_id', '')
            ->set('cluster_name', 'Tanpa Cluster')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($house->fresh()->cluster_id);
    }
}

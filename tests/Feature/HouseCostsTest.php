<?php

namespace Tests\Feature;

use App\Exports\HouseListExport;
use App\Livewire\Admin\HouseCosts;
use App\Models\Cluster;
use App\Models\House;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseCostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cluster_filter_filters_houses_and_resets(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);

        $houseA = House::factory()->create([
            'cluster_id' => $clusterA->id,
            'name' => 'Alpha House 1',
            'house_code' => 'ALP-01',
        ]);
        $houseB = House::factory()->create([
            'cluster_id' => $clusterB->id,
            'name' => 'Beta House 1',
            'house_code' => 'BET-01',
        ]);

        $this->actingAs($user);

        Livewire::test(HouseCosts::class)
            ->assertSee('Semua Cluster')
            ->assertSee('Cluster Alpha')
            ->assertSee('Cluster Beta')
            ->assertSee('Alpha House 1')
            ->assertSee('Beta House 1')
            ->set('filterCluster', (string) $clusterA->id)
            ->assertSee('Alpha House 1')
            ->assertDontSee('Beta House 1')
            ->call('resetFilters')
            ->assertSet('filterCluster', '')
            ->assertSee('Alpha House 1')
            ->assertSee('Beta House 1');
    }

    public function test_export_excel_with_cluster_filter(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'Cluster Gamma']);
        House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Gamma House 1',
            'house_code' => 'GAM-01',
        ]);

        $this->actingAs($user);

        Livewire::test(HouseCosts::class)
            ->set('filterCluster', (string) $cluster->id)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}

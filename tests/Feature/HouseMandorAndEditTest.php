<?php

namespace Tests\Feature;

use App\Livewire\Admin\HouseCosts;
use App\Livewire\Logistik\ClusterExpenses;
use App\Livewire\Logistik\HouseDetail;
use App\Livewire\Logistik\Houses;
use App\Models\Cluster;
use App\Models\House;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseMandorAndEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_cluster_expenses_and_house_costs_render_mandor_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $cluster = Cluster::create(['name' => 'Cluster Intan']);
        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok I-01',
            'type' => 'Tipe 36/72',
            'mandor_name' => 'Pak Slamet',
            'status' => 'pembangunan',
        ]);

        Livewire::test(ClusterExpenses::class, ['cluster' => $cluster])
            ->assertSee('Mandor')
            ->assertSee('Pak Slamet');

        Livewire::test(HouseCosts::class)
            ->assertSee('Mandor')
            ->assertSee('Pak Slamet');

        Livewire::test(Houses::class)
            ->assertSee('Mandor')
            ->assertSee('Pak Slamet');
    }

    public function test_house_detail_has_edit_button_and_saves_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $cluster = Cluster::create(['name' => 'Cluster Ruby']);
        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok R-01',
            'type' => 'Tipe 45/90',
            'mandor_name' => null,
            'status' => 'pembangunan',
        ]);

        Livewire::test(HouseDetail::class, ['house' => $house])
            ->assertSee('Edit')
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->assertSet('editName', 'Blok R-01')
            ->assertSet('editType', 'Tipe 45/90')
            ->assertSet('editMandorName', '')
            ->set('editMandorName', 'Pak Joko Mandor')
            ->set('editType', 'Tipe 45/100')
            ->call('saveHouse')
            ->assertSet('showEditModal', false)
            ->assertHasNoErrors();

        $house->refresh();
        $this->assertSame('Pak Joko Mandor', $house->mandor_name);
        $this->assertSame('Tipe 45/100', $house->type);
    }
}

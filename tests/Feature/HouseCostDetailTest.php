<?php

namespace Tests\Feature;

use App\Livewire\Admin\HouseCostDetail;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseCostDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_house_cost_detail_renders_budget_bobot_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'Cluster Berlian']);

        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok B-01',
            'type' => 'Tipe 45',
            'house_code' => '2026-B01',
            'category' => House::CATEGORY_HOUSE,
            'material_budget' => 50000000.0,
            'physical_progress' => 45.0,
        ]);

        $catSemen = Category::create(['name' => 'Semen & Pasir']);
        $catBesi = Category::create(['name' => 'Besi']);

        $materialSemen = Material::factory()->create([
            'name' => 'Semen Gresik 50kg',
            'category_id' => $catSemen->id,
            'unit' => 'sak',
        ]);
        $materialBesi = Material::factory()->create([
            'name' => 'Besi Beton 10mm',
            'category_id' => $catBesi->id,
            'unit' => 'batang',
        ]);

        MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $materialSemen->id,
            'user_id' => $admin->id,
            'quantity' => 10,
            'unit_price_at_usage' => 70000,
            'total_cost' => 700000,
            'usage_date' => now()->toDateString(),
            'notes' => 'Pondasi awal',
        ]);

        MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $materialBesi->id,
            'user_id' => $admin->id,
            'quantity' => 20,
            'unit_price_at_usage' => 85000,
            'total_cost' => 1700000,
            'usage_date' => now()->toDateString(),
            'notes' => 'Pembesian sloof',
        ]);

        $this->actingAs($admin);

        Livewire::test(HouseCostDetail::class, ['house' => $house])
            ->assertSee('Blok B-01 — Tipe 45')
            ->assertSee('2026-B01')
            ->assertSee('Cluster Berlian')
            ->assertSee('Rp 50.000.000') // RAP
            ->assertSee('Rp 2.400.000') // Total cost
            ->assertSee('Sisa: Rp 47.600.000')
            ->assertSee('45,0%') // Bobot fisik
            ->assertSee('4,8%') // Bobot bahan: 2.400.000 / 50.000.000 = 4.8%
            ->assertSee('Semen Gresik 50kg')
            ->assertSee('Besi Beton 10mm')
            // Search filter
            ->set('search', 'Gresik')
            ->assertSee('Semen Gresik 50kg')
            ->assertDontSee('Besi Beton 10mm')
            ->set('search', '')
            // Category filter
            ->set('selectedCategory', (string) $catBesi->id)
            ->assertSee('Besi Beton 10mm')
            ->assertDontSee('Semen Gresik 50kg')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('selectedCategory', '')
            ->assertSee('Semen Gresik 50kg')
            ->assertSee('Besi Beton 10mm');
    }

    public function test_logistik_can_update_rap_budget_on_cost_detail_page(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Melati']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);

        $houseA = House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok M-01',
            'house_code' => '2026-M01',
            'category' => House::CATEGORY_HOUSE,
            'material_budget' => 60000000.0,
        ]);

        $houseB = House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok M-02',
            'house_code' => '2026-M02',
            'category' => House::CATEGORY_HOUSE,
            'material_budget' => null,
        ]);

        $this->actingAs($logistik);

        Livewire::test(HouseCostDetail::class, ['house' => $houseB])
            ->call('openBudgetModal')
            ->assertSet('showBudgetModal', true)
            ->set('copyBudgetHouseId', (string) $houseA->id)
            ->assertSet('budgetAmount', '60000000')
            ->call('saveMaterialBudget')
            ->assertHasNoErrors()
            ->assertSet('showBudgetModal', false)
            ->assertSee('Anggaran material (RAP) berhasil disimpan.');

        $this->assertEquals(60000000.0, $houseB->fresh()->material_budget);
    }
}

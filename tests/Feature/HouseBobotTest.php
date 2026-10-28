<?php

namespace Tests\Feature;

use App\Livewire\Logistik\HouseDetail;
use App\Livewire\Logistik\HouseFinish;
use App\Livewire\Logistik\Houses;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseBobotTest extends TestCase
{
    use RefreshDatabase;

    public function test_house_has_default_stages_and_pengawas_can_toggle_stages(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Sakura']);
        $admin = User::factory()->create(['role' => 'admin']);
        $pengawas = User::factory()->create(['role' => 'pengawas', 'cluster_id' => $cluster->id]);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);

        $this->actingAs($admin);

        // Create residential house
        Livewire::test(Houses::class)
            ->call('create')
            ->set('name', 'Blok S-01')
            ->set('type', 'Tipe 36')
            ->set('cluster_id', (string) $cluster->id)
            ->set('status', 'pembangunan')
            ->set('material_budget', 150000000)
            ->call('save')
            ->assertHasNoErrors();

        $house = House::where('name', 'Blok S-01')->firstOrFail();
        $this->assertCount(9, $house->resolvedProgressStages());
        $this->assertEquals(0.0, $house->bobot_rumah);
        $this->assertEquals(150000000, $house->material_budget);

        // Pengawas toggles first stage (Persiapan 5%) and second stage (Pondasi 15%)
        $this->actingAs($pengawas);
        Livewire::test(HouseDetail::class, ['house' => $house])
            ->call('toggleStage', 0) // Persiapan (5%)
            ->call('toggleStage', 1); // Pondasi (15%)

        $house->refresh();
        $this->assertEquals(20.0, $house->physical_progress);
        $this->assertEquals(20.0, $house->bobot_rumah);

        // Pengawas editing house on Houses list cannot overwrite material_budget
        $this->actingAs($pengawas);
        Livewire::test(Houses::class)
            ->call('edit', $house->id)
            ->set('material_budget', '999999999')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertEquals(150000000, $house->fresh()->material_budget);

        // Logistik cannot toggle stages (forbidden 403)
        $this->actingAs($logistik);
        Livewire::test(HouseDetail::class, ['house' => $house])
            ->call('toggleStage', 2)
            ->assertForbidden();
    }

    public function test_pengawas_can_customize_stages_and_weights_must_equal_100_percent(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Merapi']);
        $pengawas = User::factory()->create(['role' => 'pengawas', 'cluster_id' => $cluster->id]);
        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'status' => 'pembangunan',
            'category' => House::CATEGORY_HOUSE,
        ]);

        $this->actingAs($pengawas);

        // Total weight < 100 fails
        Livewire::test(HouseDetail::class, ['house' => $house])
            ->call('openStageModal')
            ->set('editableStages', [
                ['name' => 'Pondasi & Struktur', 'weight' => 50, 'completed' => true],
                ['name' => 'Dinding & Atap', 'weight' => 40, 'completed' => false],
            ])
            ->call('saveStages')
            ->assertHasErrors(['editableStages'])
            ->assertSet('showStageModal', true);

        // Total weight = 100 succeeds
        Livewire::test(HouseDetail::class, ['house' => $house])
            ->call('openStageModal')
            ->set('editableStages', [
                ['name' => 'Pondasi & Struktur', 'weight' => 50, 'completed' => true],
                ['name' => 'Dinding & Atap', 'weight' => 30, 'completed' => false],
                ['name' => 'Finishing', 'weight' => 20, 'completed' => false],
            ])
            ->call('saveStages')
            ->assertHasNoErrors()
            ->assertSet('showStageModal', false);

        $house->refresh();
        $this->assertCount(3, $house->progress_stages);
        $this->assertEquals(50.0, $house->physical_progress);
    }

    public function test_logistik_can_set_material_budget_and_bobot_bahan_calculates_with_overbudget_handling(): void
    {
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);
        $cluster = Cluster::create(['name' => 'Cluster Bromo']);
        $pengawas = User::factory()->create(['role' => 'pengawas', 'cluster_id' => $cluster->id]);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $category = Category::factory()->material()->create();

        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'status' => 'pembangunan',
            'material_budget' => null,
        ]);

        $material = Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Besi Beton 10mm',
            'unit_price' => 100000,
        ]);

        // Pengawas cannot set material budget
        $this->actingAs($pengawas);
        Livewire::test(HouseDetail::class, ['house' => $house])
            ->set('budgetAmount', '1000000')
            ->call('saveMaterialBudget')
            ->assertForbidden();

        // Logistik sets budget to 1,000,000
        $this->actingAs($logistik);
        Livewire::test(HouseDetail::class, ['house' => $house])
            ->set('budgetAmount', '1000000')
            ->call('saveMaterialBudget')
            ->assertHasNoErrors();

        $house->refresh();
        $this->assertEquals(1000000.0, $house->material_budget);
        $this->assertEquals(0.0, $house->bobot_bahan);

        // Record material usage: Rp 650,000 (65%)
        MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $logistik->id,
            'quantity' => 6.5,
            'unit_price_at_usage' => 100000,
            'total_cost' => 650000,
            'usage_date' => now(),
        ]);

        $this->assertEquals(65.0, $house->fresh()->bobot_bahan);

        // Record additional usage: Rp 550,000 -> Total Rp 1,200,000 (120% - over budget)
        MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $logistik->id,
            'quantity' => 5.5,
            'unit_price_at_usage' => 100000,
            'total_cost' => 550000,
            'usage_date' => now(),
        ]);

        $this->assertEquals(120.0, $house->fresh()->bobot_bahan);

        // Livewire view shows overbudget warning and percentage
        Livewire::test(HouseDetail::class, ['house' => $house->fresh()])
            ->assertSee('120,0%')
            ->assertSee('Melebihi anggaran');
    }

    public function test_copy_budget_and_stages_from_another_house(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Rinjani']);
        $admin = User::factory()->create(['role' => 'admin']);

        $houseSource = House::factory()->create([
            'name' => 'Blok R-01',
            'cluster_id' => $cluster->id,
            'material_budget' => 250000000,
            'progress_stages' => [
                ['name' => 'Pondasi Khusus', 'weight' => 40, 'completed' => true],
                ['name' => 'Bangunan Utama', 'weight' => 60, 'completed' => false],
            ],
            'physical_progress' => 40,
        ]);

        $houseTarget = House::factory()->create([
            'name' => 'Blok R-02',
            'cluster_id' => $cluster->id,
            'material_budget' => null,
            'progress_stages' => null,
        ]);

        $this->actingAs($admin);

        // Copy budget
        Livewire::test(HouseDetail::class, ['house' => $houseTarget])
            ->set('copyBudgetHouseId', (string) $houseSource->id)
            ->call('saveMaterialBudget')
            ->assertHasNoErrors();

        $this->assertEquals(250000000.0, $houseTarget->fresh()->material_budget);

        // Copy stages (copies template names & weights, does not copy source house's completed state)
        Livewire::test(HouseDetail::class, ['house' => $houseTarget])
            ->set('copyStageHouseId', (string) $houseSource->id)
            ->call('saveStages')
            ->assertHasNoErrors();

        $this->assertCount(2, $houseTarget->fresh()->progress_stages);
        $this->assertSame('Pondasi Khusus', $houseTarget->fresh()->progress_stages[0]['name']);
        $this->assertFalse($houseTarget->fresh()->progress_stages[0]['completed']);
        $this->assertEquals(0.0, $houseTarget->fresh()->physical_progress);
    }

    public function test_house_finish_marks_all_stages_completed_and_progress_100(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Semeru']);
        $admin = User::factory()->create(['role' => 'admin']);

        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'status' => 'pembangunan',
            'physical_progress' => 35.0,
        ]);

        $this->actingAs($admin);

        Livewire::test(HouseFinish::class, ['house' => $house])
            ->call('processCompletion')
            ->assertHasNoErrors();

        $house->refresh();
        $this->assertSame('selesai', $house->status);
        $this->assertEquals(100.0, $house->physical_progress);
        $this->assertEquals(100.0, $house->bobot_rumah);

        foreach ($house->resolvedProgressStages() as $stage) {
            $this->assertTrue($stage['completed']);
        }
    }
}

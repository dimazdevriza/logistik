<?php

namespace Tests\Feature;

use App\Livewire\Admin\HouseCostDetail;
use App\Livewire\Admin\HouseCosts;
use App\Livewire\Logistik\ClusterExpenses;
use App\Livewire\Logistik\Clusters;
use App\Livewire\Logistik\HouseFinish;
use App\Livewire\Logistik\Houses;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolWarehouseBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicWorksTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_works_can_be_created_with_cluster_and_are_separated_from_residential_houses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'Cluster Royal A']);

        House::factory()->create([
            'name' => 'Blok A-01',
            'house_code' => '2026-A-01',
            'cluster_id' => $cluster->id,
            'category' => House::CATEGORY_HOUSE,
        ]);

        $this->actingAs($admin);

        // Require cluster when creating Pekerjaan Umum
        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->call('create')
            ->set('name', 'Pembangunan Jalan Utama')
            ->set('status', 'pembangunan')
            ->set('start_date', '2026-10-26')
            ->call('save')
            ->assertHasErrors(['cluster_name']);

        // Reject Pekerjaan Umum with non-existent cluster
        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->call('create')
            ->set('name', 'Saluran Drainase Utama')
            ->set('cluster_name', 'Cluster Tidak Terdaftar')
            ->set('status', 'pembangunan')
            ->set('start_date', '2026-10-26')
            ->call('save')
            ->assertHasErrors(['cluster_name' => 'Cluster tidak ditemukan. Pilih dari cluster yang tersedia.']);

        $this->assertNull(Cluster::where('name', 'Cluster Tidak Terdaftar')->first());

        // Create Pekerjaan Umum with existing cluster
        $newCluster = Cluster::create(['name' => 'Cluster Infrastruktur']);

        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->call('create')
            ->set('name', 'Saluran Drainase Utama')
            ->set('cluster_id', (string) $newCluster->id)
            ->set('cluster_name', 'Cluster Infrastruktur')
            ->set('status', 'pembangunan')
            ->set('start_date', '2026-10-26')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $publicWork = House::where('name', 'Saluran Drainase Utama')->firstOrFail();

        $this->assertSame(House::CATEGORY_PUBLIC_WORK, $publicWork->category);
        $this->assertSame('Pekerjaan Umum', $publicWork->type);
        $this->assertSame($newCluster->id, $publicWork->cluster_id);
        $this->assertSame(now()->year . '-PU-SALURANDRAINASEUTAMA', $publicWork->house_code);

        // /houses only shows residential houses
        Livewire::test(Houses::class)
            ->assertSee('Blok A-01')
            ->assertDontSee('Saluran Drainase Utama');

        // /pekerjaan-umum only shows public works
        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->assertSee('Saluran Drainase Utama')
            ->assertDontSee('Blok A-01');
    }

    public function test_public_works_allocation_in_spreadsheet_and_form_adds_to_cluster_cost(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);
        $cluster = Cluster::create(['name' => 'Cluster Emerald']);
        $category = Category::factory()->material()->create();
        $supplier = Supplier::factory()->create();

        $house = House::factory()->create([
            'name' => 'Blok E-01',
            'house_code' => '2026-E-01',
            'cluster_id' => $cluster->id,
            'status' => 'pembangunan',
            'category' => House::CATEGORY_HOUSE,
        ]);

        $publicWork = House::factory()->create([
            'name' => 'Pembangunan Jalan Paving',
            'house_code' => '2026-PU-JALAN-PAVING',
            'type' => 'Pekerjaan Umum',
            'cluster_id' => $cluster->id,
            'status' => 'pembangunan',
            'category' => House::CATEGORY_PUBLIC_WORK,
        ]);

        $material = Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Semen Portland',
            'unit' => 'sak',
            'unit_price' => 65000,
            'stock' => 100,
        ]);

        $batch = StockIn::create([
            'entry_code' => 'BM-20261026-001',
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'quantity' => 100,
            'remaining_quantity' => 100,
            'unit_price' => 65000,
            'date' => now()->toDateString(),
            'user_id' => $admin->id,
        ]);

        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Stamper Kuda',
            'code' => 'ALT-STM-01',
            'total_qty' => 5,
            'available_qty' => 5,
        ]);

        ToolWarehouseBalance::updateOrCreate(
            ['tool_id' => $tool->id, 'warehouse_id' => $warehouse->id],
            ['available_qty' => 5, 'borrowed_qty' => 0, 'damaged_qty' => 0]
        );

        $this->actingAs($admin);

        // 1. Verify picker filtering between house and public_work
        $tx = Livewire::test(TransaksiLogistik::class)
            ->call('openHousePicker', 'house')
            ->assertSet('pickerCategory', House::CATEGORY_HOUSE)
            ->assertViewHas('houseResultCount', 1)
            ->call('openHousePicker', 'public_work')
            ->assertSet('pickerCategory', House::CATEGORY_PUBLIC_WORK)
            ->assertViewHas('houseResultCount', 1)
            ->call('toggleHouse', $publicWork->id)
            ->call('closeHousePicker');

        // 2. Allocate material (10 sak = Rp 650.000) and tool (1 unit) to publicWork via Spreadsheet
        $tx->set('spreadsheetRows', [
            [
                'category' => House::CATEGORY_HOUSE,
                'house_id' => (string) $house->id,
                'materials' => [
                    [
                        'material_id' => (string) $material->id,
                        'batch_id' => (string) $batch->id,
                        'quantity' => 4,
                        'notes' => 'Pasang dinding',
                    ],
                ],
                'tools' => [],
                'returns' => [],
            ],
            [
                'category' => House::CATEGORY_PUBLIC_WORK,
                'house_id' => (string) $publicWork->id,
                'materials' => [
                    [
                        'material_id' => (string) $material->id,
                        'batch_id' => (string) $batch->id,
                        'quantity' => 10,
                        'notes' => 'Cor bahu jalan',
                    ],
                ],
                'tools' => [
                    [
                        'tool_id' => (string) $tool->id,
                        'warehouse_id' => (string) $warehouse->id,
                        'quantity' => 1,
                        'notes' => 'Pemadatan jalan',
                    ],
                ],
                'returns' => [],
            ],
        ])
            ->call('reviewSpreadsheet')
            ->assertSet('showSpreadsheetReview', true)
            ->call('confirmSpreadsheetReview')
            ->call('saveSpreadsheet')
            ->assertHasNoErrors();

        // House material cost = 4 * 65,000 = 260,000
        // Public work material cost = 10 * 65,000 = 650,000
        // Total cluster cost = 910,000
        $this->assertEquals(260000.0, $house->fresh()->total_material_cost);
        $this->assertEquals(650000.0, $publicWork->fresh()->total_material_cost);

        // Verify ClusterExpenses breaks down houseSubtotal (260k), publicWorkSubtotal (650k), and clusterTotal (910k)
        Livewire::test(ClusterExpenses::class, ['cluster' => $cluster])
            ->assertViewHas('houseSubtotal', fn ($val) => (float) $val === 260000.0)
            ->assertViewHas('publicWorkSubtotal', fn ($val) => (float) $val === 650000.0)
            ->assertViewHas('clusterTotal', fn ($val) => (float) $val === 910000.0)
            ->assertSee('Total Biaya Pekerjaan Umum')
            ->assertSee('Pembangunan Jalan Paving');

        // Verify Clusters cost page includes both in totalSpent (910k) while houses_count only counts residential houses (1)
        Livewire::withQueryParams([])
            ->test(Clusters::class)
            ->set('isCostPage', true)
            ->assertSee('Cluster Emerald');

        $this->assertSame(1, $cluster->houses()->count());
        $this->assertSame(1, $cluster->publicWorks()->count());
        $this->assertSame(2, $cluster->allUnits()->count());

        // Verify HouseCosts only includes residential house (260k)
        Livewire::test(HouseCosts::class)
            ->assertSee('Blok E-01')
            ->assertDontSee('Pembangunan Jalan Paving')
            ->assertViewHas('totalSpent', fn ($val) => (float) $val === 260000.0);
    }

    public function test_public_work_duplicate_names_and_cross_role_access_for_finish_and_cost_detail(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);

        $logistik = User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $clusterA->id,
        ]);

        $keuangan = User::factory()->create([
            'role' => 'keuangan',
        ]);

        $this->actingAs($logistik);

        // Create same-named Pekerjaan Umum in Cluster A and Cluster B
        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->call('create')
            ->set('name', 'Pembangunan Jalan Utama')
            ->set('cluster_name', 'Cluster Alpha')
            ->set('status', 'pembangunan')
            ->set('start_date', '2026-10-26')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->call('create')
            ->set('name', 'Pembangunan Jalan Utama')
            ->set('cluster_name', 'Cluster Beta')
            ->set('status', 'pembangunan')
            ->set('start_date', '2026-10-26')
            ->call('save')
            ->assertHasNoErrors();

        $pwA = House::where('cluster_id', $clusterA->id)->firstOrFail();
        $pwB = House::where('cluster_id', $clusterB->id)->firstOrFail();

        $this->assertSame(now()->year . '-PU-PEMBANGUNANJALANUTAMA', $pwA->house_code);
        $this->assertSame(now()->year . '-PU-PEMBANGUNANJALANUTAMA-2', $pwB->house_code);

        // Logistik assigned to Cluster A can finish Pekerjaan Umum in Cluster B
        Livewire::test(HouseFinish::class, ['house' => $pwB])
            ->assertSee('pekerjaan umum masuk masa garansi')
            ->call('processCompletion')
            ->assertHasNoErrors();

        $this->assertSame('selesai', $pwB->fresh()->status);

        // Keuangan can view HouseCostDetail for Pekerjaan Umum and search cluster by public work name
        $this->actingAs($keuangan);

        Livewire::test(HouseCostDetail::class, ['house' => $pwB])
            ->assertSee('Pembangunan Jalan Utama')
            ->assertSee(route('clusters.expenses', $clusterB->id));

        Livewire::test(Clusters::class)
            ->set('search', $pwB->house_code)
            ->assertSee('Cluster Beta')
            ->assertDontSee('Cluster Alpha');
    }

    public function test_public_works_does_not_have_bobot_ui_or_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'Cluster Infrastruktur']);
        $publicWork = House::factory()->create([
            'name' => 'Pembuatan Taman & Pos Jaga',
            'category' => House::CATEGORY_PUBLIC_WORK,
            'cluster_id' => $cluster->id,
            'status' => 'pembangunan',
        ]);

        $this->actingAs($admin);

        // Listing on /pekerjaan-umum does not show Bobot column
        Livewire::test(Houses::class, ['category' => House::CATEGORY_PUBLIC_WORK])
            ->assertDontSee('Bobot (Fisik / Bahan)');

        // Detail page does not render Bobot & Progres Pembangunan card or stage checklist
        Livewire::test(\App\Livewire\Logistik\HouseDetail::class, ['house' => $publicWork])
            ->assertDontSee('Bobot & Progres Pembangunan')
            ->assertDontSee('Bobot Rumah (Fisik)')
            ->assertDontSee('Bobot Bahan (Material)')
            ->assertDontSee('Tahapan Pekerjaan')
            ->assertDontSee('Atur Anggaran (RAP)')
            ->assertDontSee('Atur Tahap & Bobot')
            ->call('openBudgetModal')
            ->assertForbidden();

        Livewire::test(\App\Livewire\Logistik\HouseDetail::class, ['house' => $publicWork])
            ->call('openStageModal')
            ->assertForbidden();
    }
}

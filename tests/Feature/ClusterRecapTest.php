<?php

namespace Tests\Feature;

use App\Livewire\Logistik\ClusterRecap;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClusterRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelaporan_route_is_gone_and_recap_shows_on_biaya_cluster(): void
    {
        $this->assertFalse(\Route::has('keuangan.reports'));

        Cluster::create(['name' => 'Cluster Villagio']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('logistik.cluster-costs'))
            ->assertOk()
            ->assertSee('Rekap pengeluaran per rumah');
    }

    public function test_recap_aggregates_monthly_and_sarana_lainnya(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'Cluster Villagio Ampang']);
        $a1 = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'A1', 'mandor_name' => 'PAK UJANG', 'category' => House::CATEGORY_HOUSE]);
        $a2 = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'A2', 'mandor_name' => 'MAS GABOH', 'category' => House::CATEGORY_HOUSE]);
        $sarana = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'Drainase', 'category' => House::CATEGORY_PUBLIC_WORK]);
        $material = Material::create([
            'category_id' => Category::create(['name' => 'Pasir', 'type' => 'material'])->id,
            'name' => 'Pasir Pasang', 'unit' => 'm3', 'unit_price' => 150000, 'stock' => 100,
        ]);

        foreach ([[$a1, 300000, '2025-01-15', null], [$a2, 450000, '2025-02-10', null], [$sarana, 200000, '2025-02-20', null], [$a1, 999000, '2025-02-01', now()]] as [$house, $cost, $date, $voided]) {
            MaterialUsage::create([
                'house_id' => $house->id, 'material_id' => $material->id, 'user_id' => $user->id,
                'quantity' => 1, 'unit_price_at_usage' => $cost, 'total_cost' => $cost, 'usage_date' => $date, 'voided_at' => $voided,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(ClusterRecap::class)
            ->set('clusterId', (string) $cluster->id)
            ->set('filterYear', '2025')
            ->assertSee(['A1', 'PAK UJANG', 'A2', 'SARANA LAINNYA', 'JUMLAH RINCIAN PERBULAN'])
            ->assertViewHas('clusterYearGrandTotal', 950000.0)
            ->assertViewHas('monthlyGrandTotals', fn ($t) => $t[1] === 300000.0 && $t[2] === 650000.0)
            ->call('exportExcel')
            ->assertFileDownloaded('Rekapitulasi-Pengeluaran-cluster-villagio-ampang-2025.xlsx');
    }

    public function test_field_staff_cannot_switch_to_foreign_cluster(): void
    {
        $alpha = Cluster::create(['name' => 'Cluster Alpha']);
        $beta = Cluster::create(['name' => 'Cluster Beta']);
        $staff = User::factory()->create(['role' => 'logistik', 'cluster_id' => $alpha->id]);
        $staff->clusters()->sync([$alpha->id]);

        $this->actingAs($staff);

        Livewire::test(ClusterRecap::class)
            ->set('clusterId', (string) $beta->id)
            ->assertSet('clusterId', (string) $alpha->id);
    }
}

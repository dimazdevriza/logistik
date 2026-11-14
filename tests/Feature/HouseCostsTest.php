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

    public function test_export_excel_with_multi_cluster_scoped_user(): void
    {
        $clusterA = Cluster::create(['name' => 'Cluster Alpha']);
        $clusterB = Cluster::create(['name' => 'Cluster Beta']);
        $clusterC = Cluster::create(['name' => 'Cluster Gamma']);

        House::factory()->create(['cluster_id' => $clusterA->id, 'name' => 'House A', 'house_code' => 'A-01']);
        House::factory()->create(['cluster_id' => $clusterB->id, 'name' => 'House B', 'house_code' => 'B-01']);
        House::factory()->create(['cluster_id' => $clusterC->id, 'name' => 'House C', 'house_code' => 'C-01']);

        $user = User::factory()->create(['role' => 'logistik']);
        $user->clusters()->sync([$clusterA->id, $clusterB->id]);

        $this->actingAs($user);

        Livewire::test(HouseCosts::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_house_list_export_contains_bobot_kerja_and_bobot_bahan_columns(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster Test']);
        $house = House::factory()->create([
            'cluster_id' => $cluster->id,
            'name' => 'House 1',
            'house_code' => 'H-01',
            'physical_progress' => 45.0,
            'material_budget' => 10000000,
            'status' => 'pembangunan',
        ]);

        $export = new HouseListExport(clusterScope: $cluster->id);

        $headings = $export->headings();
        $headerRow = $headings[5];

        $this->assertEquals('Mandor', $headerRow[5]);
        $this->assertEquals('Status', $headerRow[6]);
        $this->assertEquals('Bobot Kerja', $headerRow[7]);
        $this->assertEquals('Bobot Bahan', $headerRow[8]);
        $this->assertEquals('Jan', $headerRow[9]);

        $formats = $export->columnFormats();
        $this->assertEquals('0%', $formats['H']);
        $this->assertEquals('0%', $formats['I']);
        $this->assertEquals('[$Rp-421] #,##0.00', $formats['J']);

        $mapped = $export->map($house);
        $this->assertEquals('-', $mapped[5]);
        $this->assertEquals('Pembangunan', $mapped[6]);
        $this->assertEquals(0.45, $mapped[7]);
        $this->assertEquals(0.0, $mapped[8]);

        // Verify AfterSheet aligns G, H, I center and J right and positions drawing at center
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A7', '1');
        $sheet->setCellValue('H7', 0.45);
        $sheet->setCellValue('I7', 0.0);
        foreach ($export->drawings() as $d) { $d->setWorksheet($sheet); }

        $events = $export->registerEvents();
        $afterSheetEvent = new \Maatwebsite\Excel\Events\AfterSheet(new \Maatwebsite\Excel\Sheet($sheet), $export);
        $events[\Maatwebsite\Excel\Events\AfterSheet::class]($afterSheetEvent);

        $this->assertEquals('center', $sheet->getStyle('G7')->getAlignment()->getHorizontal());
        $this->assertEquals('center', $sheet->getStyle('H7')->getAlignment()->getHorizontal());
        $this->assertEquals('center', $sheet->getStyle('I7')->getAlignment()->getHorizontal());
        $this->assertEquals('right', $sheet->getStyle('J7')->getAlignment()->getHorizontal());
    }

    public function test_pelaporan_route_is_removed_and_cost_pages_render_single_table(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('keuangan.reports'));

        $admin = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'Cluster A']);

        $this->actingAs($admin)
            ->get(route('logistik.cluster-costs'))
            ->assertOk()
            ->assertSee('matrix-report-table')
            ->assertDontSee('Rekap pengeluaran per rumah');

        $this->actingAs($admin)
            ->get(route('clusters.expenses', $cluster))
            ->assertOk()
            ->assertSee('matrix-report-table')
            ->assertDontSee('Rekap pengeluaran per rumah');
    }
}

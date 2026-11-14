<?php

namespace Tests\Feature;

use App\Exports\ClusterCostsExport;
use App\Exports\ClusterMaterialExport;
use App\Exports\ClusterToolExport;
use App\Exports\ClusterVendorRentalExport;
use App\Exports\ClusterVendorServiceExport;
use App\Exports\HouseListExport;
use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClusterCostsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cluster_costs_export_has_exactly_five_sheets_and_no_individual_house_sheets(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $otherCluster = Cluster::create(['name' => 'Cluster B']);

        // Create houses and public works in cluster A
        $house1 = House::create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok A-01',
            'house_code' => '2026-A01',
            'type' => 'Tipe 36/72',
            'status' => 'pembangunan',
            'category' => House::CATEGORY_HOUSE,
        ]);

        $publicWork = House::create([
            'cluster_id' => $cluster->id,
            'name' => 'Drainase',
            'house_code' => 'PU-01',
            'type' => 'Pekerjaan Umum',
            'status' => 'pembangunan',
            'category' => House::CATEGORY_PUBLIC_WORK,
        ]);

        // Create house in other cluster
        $otherHouse = House::create([
            'cluster_id' => $otherCluster->id,
            'name' => 'Blok B-01',
            'house_code' => '2026-B01',
            'type' => 'Tipe 36/72',
            'status' => 'pembangunan',
            'category' => House::CATEGORY_HOUSE,
        ]);

        $export = new ClusterCostsExport($cluster);
        $sheets = $export->sheets();

        $this->assertCount(5, $sheets);
        $this->assertInstanceOf(HouseListExport::class, $sheets[0]);
        $this->assertInstanceOf(ClusterMaterialExport::class, $sheets[1]);
        $this->assertInstanceOf(ClusterToolExport::class, $sheets[2]);
        $this->assertInstanceOf(ClusterVendorServiceExport::class, $sheets[3]);
        $this->assertInstanceOf(ClusterVendorRentalExport::class, $sheets[4]);

        $this->assertEquals('Cluster A', $sheets[0]->title());
        $this->assertEquals('Material', $sheets[1]->title());
        $this->assertEquals('Peminjaman Alat', $sheets[2]->title());
        $this->assertEquals('Jasa Vendor', $sheets[3]->title());
        $this->assertEquals('Sewa Alat Vendor', $sheets[4]->title());
    }

    public function test_cluster_breakdown_sheets_scope_data_to_cluster(): void
    {
        $cluster = Cluster::create(['name' => 'Cluster A']);
        $otherCluster = Cluster::create(['name' => 'Cluster B']);
        $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Test Cluster']);
        $user = User::factory()->create(['role' => 'admin']);

        $house = House::create([
            'cluster_id' => $cluster->id,
            'name' => 'Blok A-01',
            'house_code' => '2026-A01',
            'type' => 'Tipe 36/72',
            'status' => 'pembangunan',
            'category' => House::CATEGORY_HOUSE,
        ]);

        $otherHouse = House::create([
            'cluster_id' => $otherCluster->id,
            'name' => 'Blok B-01',
            'house_code' => '2026-B01',
            'type' => 'Tipe 36/72',
            'status' => 'pembangunan',
            'category' => House::CATEGORY_HOUSE,
        ]);

        // Materials & Usages
        $material = Material::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'MAT-01',
            'name' => 'Semen Portland',
            'unit' => 'sak',
            'unit_price' => 70000,
            'stock' => 100,
        ]);

        MaterialUsage::create([
            'house_id' => $house->id,
            'material_id' => $material->id,
            'user_id' => $user->id,
            'quantity' => 10,
            'unit_price_at_usage' => 70000,
            'total_cost' => 700000,
            'usage_date' => now()->toDateString(),
            'taken_by' => 'Pak Mandor A',
        ]);

        MaterialUsage::create([
            'house_id' => $otherHouse->id,
            'material_id' => $material->id,
            'user_id' => $user->id,
            'quantity' => 5,
            'unit_price_at_usage' => 70000,
            'total_cost' => 350000,
            'usage_date' => now()->toDateString(),
        ]);

        // Tool Usages
        $tool = Tool::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-01',
            'name' => 'Mesin Molen',
            'quantity' => 2,
        ]);

        ToolUsage::create([
            'house_id' => $house->id,
            'tool_id' => $tool->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'checkout_date' => now()->toDateString(),
        ]);

        ToolUsage::create([
            'house_id' => $otherHouse->id,
            'tool_id' => $tool->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'checkout_date' => now()->toDateString(),
        ]);

        // Vendor Expenses
        ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'house_id' => $house->id,
            'type' => 'vendor_service',
            'description' => 'Pemasangan Plafon Gypsum',
            'vendor' => 'CV Interior Jaya',
            'amount' => 5000000,
            'start_date' => now()->toDateString(),
        ]);

        ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'house_id' => $house->id,
            'type' => 'rental',
            'description' => 'Excavator Mini PC50',
            'vendor' => 'PT Rental Berat',
            'amount' => 3500000,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'quantity' => 1,
            'status' => 'active',
        ]);

        // Expenses on other cluster
        ClusterExpense::create([
            'cluster_id' => $otherCluster->id,
            'house_id' => $otherHouse->id,
            'type' => 'vendor_service',
            'description' => 'Jasa Lain',
            'amount' => 1000000,
            'start_date' => now()->toDateString(),
        ]);

        // Test Material Export
        $matExport = new ClusterMaterialExport($cluster);
        $matCollection = $matExport->collection();
        $this->assertCount(1, $matCollection);
        $mappedMat = $matExport->map($matCollection->first());
        $this->assertSame('Pengambil', $matExport->headings()[5][9]);
        $this->assertEquals('Blok A-01', $mappedMat[2]);
        $this->assertEquals('Semen Portland', $mappedMat[4]);
        $this->assertEquals(700000.0, $mappedMat[8]);
        $this->assertSame('Pak Mandor A', $mappedMat[9]);

        // Test Tool Export
        $toolExport = new ClusterToolExport($cluster);
        $toolCollection = $toolExport->collection();
        $this->assertCount(1, $toolCollection);
        $mappedTool = $toolExport->map($toolCollection->first());
        $this->assertEquals('Blok A-01', $mappedTool[2]);
        $this->assertEquals('Mesin Molen', $mappedTool[3]);

        // Test Vendor Service Export
        $serviceExport = new ClusterVendorServiceExport($cluster);
        $serviceCollection = $serviceExport->collection();
        $this->assertCount(1, $serviceCollection);
        $mappedService = $serviceExport->map($serviceCollection->first());
        $this->assertEquals('Blok A-01', $mappedService[2]);
        $this->assertEquals('Pemasangan Plafon Gypsum', $mappedService[3]);
        $this->assertEquals(5000000.0, $mappedService[5]);

        // Test Vendor Rental Export
        $rentalExport = new ClusterVendorRentalExport($cluster);
        $rentalCollection = $rentalExport->collection();
        $this->assertCount(1, $rentalCollection);
        $mappedRental = $rentalExport->map($rentalCollection->first());
        $this->assertEquals('Sewa alat', $mappedRental[1]);
        $this->assertEquals('Blok A-01', $mappedRental[2]);
        $this->assertEquals('Excavator Mini PC50', $mappedRental[3]);
        $this->assertEquals(3500000.0, $mappedRental[9]);

        // Test actual Excel rendering & file generation
        $clusterExport = new ClusterCostsExport($cluster);
        \Maatwebsite\Excel\Facades\Excel::fake();
        \Maatwebsite\Excel\Facades\Excel::store($clusterExport, 'cluster_test.xlsx');
        \Maatwebsite\Excel\Facades\Excel::assertStored('cluster_test.xlsx');
    }
}

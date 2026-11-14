<?php

namespace Tests\Feature;

use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportCompanyExcelTest extends TestCase
{
    use RefreshDatabase;
    public function test_dry_run_does_not_persist_data(): void
    {
        $filePath = 'C:\Users\DIM\Downloads\LOGISTIK AMPANG (ORI).xlsx';
        if (! file_exists($filePath)) {
            $this->markTestSkipped('Sample file not found on local machine.');
        }

        $exitCode = Artisan::call('import:company-excel', [
            'file' => $filePath,
            '--dry-run' => true,
            '--force' => true,
            '--cluster' => 'TEST_VILLAGIO',
            '--warehouse' => 'Gudang Test Villagio',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing('clusters', ['name' => 'TEST_VILLAGIO']);
        $this->assertDatabaseMissing('warehouses', ['name' => 'Gudang Test Villagio']);
    }

    public function test_successful_import_persists_cluster_and_warehouse(): void
    {
        $filePath = 'C:\Users\DIM\Downloads\LOGISTIK AMPANG (ORI).xlsx';
        if (! file_exists($filePath)) {
            $this->markTestSkipped('Sample file not found on local machine.');
        }

        $exitCode = Artisan::call('import:company-excel', [
            'file' => $filePath,
            '--force' => true,
            '--cluster' => 'TEST_VIL',
            '--warehouse' => 'Gudang Test Vil',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('clusters', ['name' => 'TEST_VIL']);
        $this->assertDatabaseHas('warehouses', ['name' => 'Gudang Test Vil']);
        $this->assertDatabaseHas('houses', ['house_code' => 'VIL-A1']);
        $this->assertDatabaseHas('houses', ['house_code' => 'VIL-INFRA']);
    }
}

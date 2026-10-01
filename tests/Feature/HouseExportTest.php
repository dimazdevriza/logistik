<?php

namespace Tests\Feature;

use App\Exports\HouseExport;
use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\House;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class HouseExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_house_export_has_material_tool_vendor_service_and_vendor_rental_sheets(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $cluster = Cluster::create(['name' => 'TEST Export Cluster']);
        $house = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'TEST Export House']);
        $otherHouse = House::factory()->create(['cluster_id' => $cluster->id, 'name' => 'TEST Other Export House']);

        ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'house_id' => $house->id,
            'created_by' => $user->id,
            'type' => 'vendor_service',
            'description' => 'TEST Export Plumbing',
            'vendor' => 'TEST Export Service Vendor',
            'start_date' => '2026-09-20',
            'amount' => 500000,
            'status' => 'active',
        ]);

        $rental = ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'created_by' => $user->id,
            'type' => 'rental',
            'description' => 'TEST Export Excavator',
            'vendor' => 'TEST Export Rental Vendor',
            'quantity' => 1,
            'start_date' => '2026-09-20',
            'due_date' => '2026-10-20',
            'amount' => 1500000,
            'status' => 'active',
        ]);
        $rental->houses()->attach($house->id);

        $otherRental = ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'created_by' => $user->id,
            'type' => 'rental',
            'description' => 'TEST Excluded Excavator',
            'vendor' => 'TEST Other Vendor',
            'quantity' => 1,
            'start_date' => '2026-09-20',
            'due_date' => '2026-10-20',
            'amount' => 200000,
            'status' => 'active',
        ]);
        $otherRental->houses()->attach($otherHouse->id);

        $file = tmpfile();
        try {
            fwrite($file, Excel::raw(new HouseExport($house->id), \Maatwebsite\Excel\Excel::XLSX));
            $workbook = IOFactory::load(stream_get_meta_data($file)['uri']);

            $this->assertSame(['Material', 'Alat', 'Jasa Vendor', 'Sewa Alat'], $workbook->getSheetNames());
            $serviceSheet = $workbook->getSheetByName('Jasa Vendor');
            $this->assertSame('Laporan Jasa Vendor - TEST Export House', $serviceSheet->getCell('A1')->getValue());
            $this->assertSame('TEST Export Plumbing', $serviceSheet->getCell('B6')->getValue());
            $this->assertSame('[$Rp-421] #,##0.00', $serviceSheet->getStyle('D6')->getNumberFormat()->getFormatCode());
            $rentalSheet = $workbook->getSheetByName('Sewa Alat');
            $this->assertSame('Jenis', $rentalSheet->getCell('A5')->getValue());
            $this->assertSame('TEST Export Excavator', $rentalSheet->getCell('B6')->getValue());
            $this->assertSame('TEST Export House', $rentalSheet->getCell('J6')->getValue());
            $this->assertSame(6, $rentalSheet->getHighestDataRow());

            foreach ($workbook->getAllSheets() as $sheet) {
                $this->assertSame('334155', $sheet->getStyle('A5')->getFill()->getStartColor()->getRGB());
                $this->assertSame('FFFFFF', $sheet->getStyle('A5')->getFont()->getColor()->getRGB());
                $this->assertEquals(14, $sheet->getStyle('A1')->getFont()->getSize());
                $this->assertTrue($sheet->getStyle('A2')->getFont()->getItalic());
            }

            $workbook->disconnectWorksheets();
        } finally {
            fclose($file);
        }
    }
}

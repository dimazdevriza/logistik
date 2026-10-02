<?php

namespace Tests\Feature;

use App\Exports\MaterialLogExport;
use App\Livewire\Logistik\MaterialLog;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MaterialLogSupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_supplier_receipts_remain_in_logs_exports_and_totals(): void
    {
        $user = User::factory()->create(['role' => 'logistik', 'name' => 'Petugas Bahan']);
        $this->actingAs($user);
        $material = Material::factory()->create(['supplier_id' => null]);
        $supplier = Supplier::factory()->create();
        foreach ([null, $supplier->id] as $supplierId) {
            StockIn::create([
                'material_id' => $material->id, 'supplier_id' => $supplierId,
                'user_id' => $user->id, 'quantity' => 2, 'unit_price' => 100,
                'total_cost' => 200, 'date' => '2026-09-13',
                'notes' => $supplierId ? 'Receipt with supplier' : 'Receipt without supplier',
            ]);
        }

        foreach ([['', '', 2], ['masuk', '', 2], ['masuk', (string) $supplier->id, 1], ['keluar', '', 0]] as [$type, $supplierFilter, $count]) {
            Livewire::test(MaterialLog::class)
                ->set('search', $material->name)
                ->set('filterType', $type)
                ->set('filterSupplier', $supplierFilter)
                ->assertViewHas('records', fn ($records) => $records->total() === $count);

            $export = new MaterialLogExport($material->name, $type, '', $supplierFilter);
            $this->assertCount($count, $export->collection());
            $file = tmpfile();
            try {
                fwrite($file, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
                $sheet = IOFactory::load(stream_get_meta_data($file)['uri'])->getActiveSheet();
                $totalColumn = $type === 'keluar' ? 'M' : 'J';
                $this->assertEquals($count * 200, $sheet->getCell($totalColumn.$sheet->getHighestRow())->getValue());
            } finally {
                fclose($file);
            }
        }

        Livewire::test(MaterialLog::class)
            ->assertSeeInOrder([$user->name, 'Logistik'])
            ->assertDontSee('Tiba');
    }
}

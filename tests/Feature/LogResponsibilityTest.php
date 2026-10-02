<?php

namespace Tests\Feature;

use App\Exports\MaterialLogExport;
use App\Exports\ToolLogExport;
use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\ToolLog;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LogResponsibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_logs_show_the_recording_user_in_one_responsibility_column(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'name' => 'Recorder Example']);
        $this->actingAs($user);
        $house = House::factory()->create();

        MaterialUsage::factory()->create(['user_id' => $user->id, 'house_id' => $house->id]);
        ToolUsage::factory()->create(['user_id' => $user->id, 'house_id' => $house->id]);

        foreach ([MaterialLog::class, ToolLog::class] as $log) {
            $component = Livewire::test($log)
                ->assertSee('Penanggung Jawab')
                ->assertDontSee('Pengambil');

            $this->assertSame(1, substr_count($component->html(), 'title="Recorder Example"'));
        }
    }

    public function test_tool_export_includes_incoming_records_and_loans_beyond_one_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $tools = \App\Models\Tool::factory()->count(12)->create(['name' => 'Export drill']);
        ToolUsage::factory()->create(['tool_id' => $tools->first()->id]);
        $component = new ToolLog;
        $query = (new \ReflectionMethod(ToolLog::class, 'buildRecordsQuery'))->invoke($component);
        $export = new ToolLogExport($query);
        $this->assertSame(13, $export->query()->count());

        $file = tmpfile();
        try {
            fwrite($file, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $sheet = IOFactory::load(stream_get_meta_data($file)['uri'])->getActiveSheet();
            $this->assertSame(17, $sheet->getHighestDataRow());
            $this->assertEquals(13, $sheet->getCell('A17')->getValue());
            foreach (['Q5', 'R5'] as $cell) {
                $this->assertSame('n', $sheet->getCell($cell)->getDataType());
                $this->assertSame('[$Rp-421] #,##0.00', $sheet->getStyle($cell)->getNumberFormat()->getFormatCode());
            }
            $types = array_column($sheet->rangeToArray('K5:K17'), 0);
            $this->assertContains('Masuk', $types);
            $this->assertContains('Peminjaman', $types);
        } finally {
            fclose($file);
        }

        $component->search = 'No matching tool';
        $filtered = (new \ReflectionMethod(ToolLog::class, 'buildRecordsQuery'))->invoke($component);
        $this->assertSame(0, (new ToolLogExport($filtered))->query()->count());
    }

    public function test_material_export_keeps_amount_under_jumlah_after_removing_duplicate_person(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'name' => 'Recorder Example']);
        $house = House::factory()->create(['name' => 'House Example']);
        $material = Material::factory()->create(['name' => 'Material Example']);
        MaterialUsage::factory()->create([
            'user_id' => $user->id,
            'house_id' => $house->id,
            'material_id' => $material->id,
            'quantity' => 2,
            'unit_price_at_usage' => 250,
            'total_cost' => 500,
        ]);

        $export = new MaterialLogExport('', 'keluar');
        $this->assertCount(15, $export->headings()[0]);
        $this->assertCount(15, $export->map($export->collection()->first()));
        $this->assertSame('Penanggung Jawab', (new ToolLogExport(\Illuminate\Support\Facades\DB::table('tool_usages')))->headings()[3][8]);

        $file = tmpfile();
        try {
            fwrite($file, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $sheet = IOFactory::load(stream_get_meta_data($file)['uri'])->getActiveSheet();

            $this->assertSame('PENANGGUNG JAWAB', $sheet->getCell('D1')->getValue());
            $this->assertSame('Recorder Example', $sheet->getCell('D2')->getValue());
            $this->assertSame('House Example', $sheet->getCell('E2')->getValue());
            $this->assertSame('JUMLAH', $sheet->getCell('L1')->getValue());
            $this->assertEquals(500, $sheet->getCell('L2')->getValue());
            $this->assertSame('TOTAL', $sheet->getCell('K3')->getValue());
            $this->assertEquals(500, $sheet->getCell('L3')->getValue());
        } finally {
            fclose($file);
        }
    }
}

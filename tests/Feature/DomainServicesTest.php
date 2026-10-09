<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\MaterialAllocationRecorder;
use App\Support\SpreadsheetBatchRecorder;
use App\Support\ToolInventory;
use App\Support\ToolLoanRecorder;
use App\Support\ToolReturnRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainServicesTest extends TestCase
{
    use RefreshDatabase;

    private Cluster $cluster;
    private Warehouse $warehouse;
    private User $user;
    private House $house;
    private Material $material;
    private StockIn $batch;
    private Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cluster = Cluster::create(['name' => 'Cluster Test', 'code' => 'CL-TEST']);
        $this->warehouse = Warehouse::create(['name' => 'Gudang Test', 'code' => 'GD-TEST']);
        $this->user = User::factory()->create(['role' => 'logistik', 'cluster_id' => $this->cluster->id]);
        $this->house = House::create([
            'cluster_id' => $this->cluster->id,
            'house_code' => '2026-T01',
            'name' => 'Blok T-01',
            'type' => 'Tipe 36/72',
            'status' => 'pembangunan',
            'start_date' => now()->toDateString(),
        ]);

        $category = Category::create(['name' => 'Material Umum', 'type' => 'material']);
        $supplier = Supplier::create(['name' => 'PT Semen Maju']);

        $this->material = Material::create([
            'warehouse_id' => $this->warehouse->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'code' => 'MAT-SMN-01',
            'name' => 'Semen Padang 50kg',
            'unit' => 'sak',
            'unit_price' => 75000,
            'stock' => 100,
        ]);

        $this->batch = StockIn::create([
            'entry_code' => 'MSK-TEST-01',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
            'material_id' => $this->material->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 100,
            'remaining_quantity' => 100,
            'unit_price' => 75000,
            'total_cost' => 7500000,
            'date' => now()->toDateString(),
            'received_at' => now(),
        ]);

        $toolCat = Category::create(['name' => 'Alat Berat', 'type' => 'tool']);
        $this->tool = Tool::create([
            'category_id' => $toolCat->id,
            'code' => 'ALT-MLN-01',
            'name' => 'Molen Semen',
            'unit' => 'unit',
            'available_qty' => 5,
            'qty_broken' => 0,
            'unit_price' => 15000000,
        ]);

        ToolInventory::ensureBalance($this->tool, $this->warehouse->id, 5, 0);
    }

    public function test_material_allocation_recorder_allocates_atomically(): void
    {
        $usages = MaterialAllocationRecorder::allocate(
            [$this->house->id],
            $this->material,
            $this->batch,
            10.0,
            'Pengecoran sloof',
            null,
            'Pak Tukang',
            $this->user
        );

        $this->assertCount(1, $usages);
        $usage = $usages->first();
        $this->assertSame((float) $usage->quantity, 10.0);
        $this->assertSame((float) $usage->unit_price_at_usage, 75000.0);
        $this->assertSame((float) $usage->total_cost, 750000.0);
        $this->assertSame($usage->taken_by, 'Pak Tukang');

        $this->assertSame(90.0, (float) $this->batch->fresh()->remaining_quantity);
        $this->assertSame(90.0, (float) $this->material->fresh()->stock);
    }

    public function test_tool_loan_recorder_loans_and_blocks_duplicate(): void
    {
        $loans = ToolLoanRecorder::loan(
            [$this->house->id],
            $this->tool,
            $this->warehouse->id,
            2,
            'Pengadukan beton',
            null,
            $this->user
        );

        $this->assertCount(1, $loans);
        $this->assertSame(2, (int) $loans->first()->quantity);

        $balance = ToolInventory::lockBalance($this->tool, $this->warehouse->id);
        $this->assertSame(3, (int) $balance->available_qty);

        // Attempt duplicate loan to same house should fail
        $this->expectException(ValidationException::class);
        ToolLoanRecorder::loan(
            [$this->house->id],
            $this->tool,
            $this->warehouse->id,
            1,
            'Duplikat',
            null,
            $this->user
        );
    }

    public function test_tool_return_recorder_splits_partial_and_triages_conditions(): void
    {
        $loan = ToolLoanRecorder::loan(
            [$this->house->id],
            $this->tool,
            $this->warehouse->id,
            5,
            'Pinjam 5 unit',
            null,
            $this->user
        )->first();

        // Return 3 out of 5: 1 normal, 1 broken, 1 lost. Remaining active loan = 2.
        ToolReturnRecorder::recordReturn(
            $loan,
            1, // normal
            1, // broken
            1, // lost
            $this->warehouse->id,
            'Sebagian kembali',
            $this->user
        );

        $loan->refresh();
        $this->assertNotNull($loan->return_date);
        $this->assertSame(3, (int) $loan->quantity);

        // Check child remainder loan
        $child = ToolUsage::where('parent_usage_id', $loan->id)->first();
        $this->assertNotNull($child);
        $this->assertNull($child->return_date);
        $this->assertSame(2, (int) $child->quantity);

        // Check inventory balance: was 0 available, now +1 normal available, +1 broken
        $balance = ToolInventory::lockBalance($this->tool, $this->warehouse->id);
        $this->assertSame(1, (int) $balance->available_qty);
        $this->assertSame(1, (int) $balance->qty_broken);

        // Check ToolReturnLog records
        $this->assertDatabaseHas('tool_return_logs', [
            'tool_id' => $this->tool->id,
            'house_id' => $this->house->id,
            'report_type' => 'normal',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('tool_return_logs', [
            'tool_id' => $this->tool->id,
            'house_id' => $this->house->id,
            'report_type' => 'broken',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('tool_return_logs', [
            'tool_id' => $this->tool->id,
            'house_id' => $this->house->id,
            'report_type' => 'lost',
            'quantity' => 1,
        ]);
    }

    public function test_spreadsheet_batch_recorder_processes_multi_entity_rows(): void
    {
        $rows = [
            [
                'house_id' => $this->house->id,
                'materials' => [
                    [
                        'material_id' => $this->material->id,
                        'batch_id' => $this->batch->id,
                        'quantity' => 5,
                        'notes' => 'Pasang dinding',
                        'taken_by' => 'Joko',
                    ],
                ],
                'tools' => [
                    [
                        'tool_id' => $this->tool->id,
                        'warehouse_id' => $this->warehouse->id,
                        'quantity' => 1,
                        'notes' => 'Pinjam molen',
                    ],
                ],
                'returns' => [],
            ],
        ];

        $summary = SpreadsheetBatchRecorder::process($rows, $this->user);

        $this->assertSame(1, $summary['materials']);
        $this->assertSame(1, $summary['tools']);
        $this->assertSame(0, $summary['returns']);

        $this->assertSame(95.0, (float) $this->material->fresh()->stock);
        $this->assertDatabaseHas('material_usages', [
            'house_id' => $this->house->id,
            'material_id' => $this->material->id,
            'quantity' => 5,
            'taken_by' => 'Joko',
        ]);
        $this->assertDatabaseHas('tool_usages', [
            'house_id' => $this->house->id,
            'tool_id' => $this->tool->id,
            'quantity' => 1,
        ]);
    }
}

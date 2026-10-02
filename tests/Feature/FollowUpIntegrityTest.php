<?php

namespace Tests\Feature;

use App\Imports\HouseImport;
use App\Livewire\Logistik\Dispatches;
use App\Livewire\Logistik\ToolLog;
use App\Models\House;
use App\Models\DispatchReceipt;
use App\Models\DispatchReceiptLine;
use App\Models\DispatchResolutionEvent;
use App\Models\MaterialUsage;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\ToolWarehouseBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ToolInventory;
use App\Support\DispatchReceiptRecorder;
use App\Support\DispatchResolutionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FollowUpIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_house_import_requires_existing_material_stock(): void
    {
        try {
            (new HouseImport)->sheets()[1]->collection(collect([[
                'unitrumah' => 'AUD-MAT-01',
                'namamaterial' => 'Material Belum Terdaftar',
                'qty' => 2,
                'tanggal' => '2026-09-17',
            ]]));
            $this->fail('House material imports must not invent zero-stock inventory.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('belum terdaftar di gudang', $exception->getMessage());
        }

        $this->assertDatabaseMissing('materials', ['name' => 'Material Belum Terdaftar']);
        $this->assertDatabaseCount('material_usages', 0);
        $this->assertDatabaseMissing('houses', ['name' => 'AUD-MAT-01']);
    }

    public function test_cluster_scoped_house_import_uses_assigned_cluster_and_rejects_foreign_house(): void
    {
        $assigned = \App\Models\Cluster::create(['name' => 'TEST Import Assigned']);
        $other = \App\Models\Cluster::create(['name' => 'TEST Import Other']);
        $foreignHouse = House::factory()->create([
            'cluster_id' => $other->id,
            'house_code' => 'TEST-FOREIGN-HOUSE',
            'name' => 'TEST Foreign House',
        ]);
        $import = new HouseImport($assigned->id);

        $import->sheets()[0]->collection(collect([[
            'kode' => 'TEST-LOCAL-HOUSE',
            'nama' => 'TEST Local House',
            'cluster' => 'TEST Unexpected File Cluster',
        ]]));

        $this->assertDatabaseHas('houses', [
            'house_code' => 'TEST-LOCAL-HOUSE',
            'cluster_id' => $assigned->id,
        ]);
        $this->assertDatabaseMissing('clusters', ['name' => 'TEST Unexpected File Cluster']);

        try {
            $import->sheets()[0]->collection(collect([[
                'kode' => $foreignHouse->house_code,
                'nama' => $foreignHouse->name,
            ]]));
            $this->fail('A cluster scoped import must not update a house assigned to another cluster.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('di luar cluster tugas', $exception->getMessage());
        }

        $this->assertSame($other->id, $foreignHouse->fresh()->cluster_id);
    }

    public function test_house_import_rejects_fractional_tool_qty_and_invalid_house_dates(): void
    {
        $warehouse = Warehouse::firstOrFail();
        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-AUD-FRACTION',
            'total_qty' => 2,
            'available_qty' => 2,
        ]);

        try {
            (new HouseImport)->sheets()[2]->collection(collect([[
                'unitrumah' => 'AUD-TOOL-01',
                'kodealat' => $tool->code,
                'namaalat' => $tool->name,
                'qty' => 1.5,
                'status' => 'dipinjam',
                'tanggalpinjam' => '2026-09-17',
            ]]));
            $this->fail('Fractional tool quantities must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('bilangan bulat', $exception->getMessage());
        }

        try {
            (new HouseImport)->sheets()[0]->collection(collect([[
                'kode' => 'AUD-DATE-01',
                'nama' => 'Blok Audit Date',
                'mulai' => 'tanggal-salah',
            ]]));
            $this->fail('Invalid house dates must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Tanggal mulai rumah tidak valid', $exception->getMessage());
        }

        $this->assertSame(2, (int) $tool->fresh()->available_qty);
        $this->assertDatabaseCount('tool_usages', 0);
        $this->assertDatabaseMissing('houses', ['house_code' => 'AUD-DATE-01']);
    }

    public function test_house_import_requires_active_loan_for_returns(): void
    {
        $warehouse = Warehouse::firstOrFail();
        $house = House::factory()->create(['house_code' => 'AUD-RETURN-IMPORT', 'name' => 'Blok Audit Return']);
        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'ALT-AUD-RETURN',
            'total_qty' => 1,
            'available_qty' => 0,
        ]);

        try {
            (new HouseImport)->sheets()[2]->collection(collect([[
                'unitrumah' => $house->name,
                'kodealat' => $tool->code,
                'namaalat' => $tool->name,
                'qty' => 1,
                'status' => 'kembali',
                'tanggalpinjam' => '2026-09-16',
                'tanggalkembali' => '2026-09-17',
            ]]));
            $this->fail('A house import return without an active loan must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Tidak ada peminjaman aktif', $exception->getMessage());
        }

        $this->assertSame(0, (int) $tool->fresh()->available_qty);
        $this->assertDatabaseCount('tool_usages', 0);
    }

    public function test_house_import_records_checkout_source_on_partial_return_remainder(): void
    {
        $warehouse = Warehouse::firstOrFail();
        $house = House::factory()->create(['house_code' => 'TEST-HOUSE-TOOL-IMPORT']);
        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'TEST-ALT-HOUSE-IMPORT',
            'total_qty' => 3,
            'available_qty' => 3,
        ]);
        $sheet = (new HouseImport)->sheets()[2];

        $sheet->collection(collect([[
            'unitrumah' => $house->house_code,
            'kodealat' => $tool->code,
            'namaalat' => $tool->name,
            'qty' => 3,
            'status' => 'dipinjam',
            'tanggalpinjam' => '2026-09-17',
            'gudang' => $warehouse->name,
        ]]));

        $original = ToolUsage::where('tool_id', $tool->id)->sole();
        $this->assertSame($warehouse->id, $original->warehouse_id);
        $this->assertTrue((bool) $original->warehouse_source_recorded);

        $sheet->collection(collect([[
            'unitrumah' => $house->house_code,
            'kodealat' => $tool->code,
            'namaalat' => $tool->name,
            'qty' => 1,
            'status' => 'kembali',
            'tanggalpinjam' => '2026-09-17',
            'tanggalkembali' => '2026-09-18',
            'gudang' => $warehouse->name,
            'kodetransaksi' => str_repeat('R', 30),
        ]]));

        $remainder = ToolUsage::where('parent_usage_id', $original->id)->sole();
        $this->assertSame(2, (int) $remainder->quantity);
        $this->assertTrue((bool) $remainder->warehouse_source_recorded);
        $this->assertLessThanOrEqual(30, strlen($remainder->transaction_code));
    }

    public function test_house_import_cannot_reopen_completed_house_or_complete_with_active_loan(): void
    {
        $completed = House::factory()->create([
            'house_code' => 'AUD-COMPLETE-01',
            'status' => 'selesai',
        ]);

        try {
            (new HouseImport)->sheets()[0]->collection(collect([[
                'kode' => $completed->house_code,
                'nama' => $completed->name,
                'status' => 'pembangunan',
            ]]));
            $this->fail('A completed house must not be reopened by import.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tidak dapat dibuka kembali', $exception->getMessage());
        }

        $house = House::factory()->create([
            'house_code' => 'AUD-COMPLETE-02',
            'status' => 'pembangunan',
        ]);
        $tool = Tool::factory()->create(['total_qty' => 1, 'available_qty' => 0]);
        ToolUsage::factory()->create([
            'house_id' => $house->id,
            'tool_id' => $tool->id,
            'user_id' => $this->user->id,
            'quantity' => 1,
            'return_date' => null,
        ]);

        try {
            (new HouseImport)->sheets()[0]->collection(collect([[
                'kode' => $house->house_code,
                'nama' => $house->name,
                'status' => 'selesai',
            ]]));
            $this->fail('A house with an active tool loan must not be completed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('alat yang belum dikembalikan', $exception->getMessage());
        }

        $this->assertSame('selesai', $completed->fresh()->status);
        $this->assertSame('pembangunan', $house->fresh()->status);
    }

    public function test_dispatch_reserves_material_once_and_uses_arrival_date_for_usage(): void
    {
        $warehouse = Warehouse::create(['name' => 'TEST Dispatch Warehouse', 'address' => 'Test only']);
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 10, 'unit_price' => 1000]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 1000,
            'total_cost' => 10000,
            'date' => '2026-09-17',
        ]);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-MAT-01',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 2,
            'notes' => 'Audit dispatch',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [['source_id' => (string) $batch->id, 'quantity' => '2']])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();

        $this->assertSame(8.0, (float) $material->fresh()->stock);
        $competingRequest = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-MAT-02',
            'requester_id' => $this->user->id,
            'house_id' => House::factory()->create(['status' => 'pembangunan'])->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 9,
            'notes' => 'Competing audit dispatch',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $competingRequest->id)
            ->set('dispatchLines', [['source_id' => (string) $batch->id, 'quantity' => '9']])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasErrors('selectedRequestId');

        $this->assertSame('pending', $competingRequest->fresh()->status);
        $this->assertSame(8.0, (float) $material->fresh()->stock);

        $material->update(['unit_price' => 2000]);
        $line = $request->fresh()->dispatchLines()->firstOrFail();
        DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 2,
            'damaged_quantity' => 0,
        ]], null, null, (string) Str::uuid());

        $this->assertSame(8.0, (float) $material->fresh()->stock);
        $this->assertDatabaseHas('material_usages', [
            'dispatch_code' => $request->fresh()->dispatch_code,
            'stock_in_id' => $batch->id,
            'usage_date' => now()->toDateString(),
            'unit_price_at_usage' => 1000,
        ]);
        $this->assertSame('arrived', $request->fresh()->status);
        $this->assertFalse($house->fresh()->hasOpenRequests());
    }

    public function test_dispatch_records_the_planned_material_batches_and_rejects_repeat_submission(): void
    {
        $warehouse = Warehouse::create(['name' => 'TEST Batch Dispatch Warehouse', 'address' => 'Test only']);
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 10, 'unit_price' => 300]);
        $firstBatch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 4,
            'remaining_quantity' => 4,
            'unit_price' => 100,
            'total_cost' => 400,
            'date' => '2026-09-17',
        ]);
        $secondBatch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 6,
            'remaining_quantity' => 6,
            'unit_price' => 200,
            'total_cost' => 1200,
            'date' => '2026-09-18',
        ]);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-MAT-MULTI-01',
            'requester_id' => $this->user->id,
            'house_id' => House::factory()->create(['status' => 'pembangunan'])->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 7,
            'notes' => 'Audit multi-batch dispatch',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();

        $this->assertSame(3.0, (float) $material->fresh()->stock);
        $this->assertSame(0.0, (float) $firstBatch->fresh()->remaining_quantity);
        $this->assertSame(3.0, (float) $secondBatch->fresh()->remaining_quantity);
        $this->assertSame(2, $request->fresh()->dispatchLines()->count());
        $this->assertStringStartsWith('DSP-', $request->fresh()->dispatch_code);
        $this->assertSame(142.86, (float) $request->fresh()->unit_price_at_dispatch);

        Livewire::test(Dispatches::class)
            ->set('selectedRequestId', $request->id)
            ->set('dispatchLines', [
                ['source_id' => (string) $firstBatch->id, 'quantity' => '4'],
                ['source_id' => (string) $secondBatch->id, 'quantity' => '3'],
            ])
            ->set('dispatchProofImage', UploadedFile::fake()->image('repeat-dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasErrors('dispatchProofImage');

        $this->assertSame(3.0, (float) $material->fresh()->stock);
        $this->assertSame(2, $request->fresh()->dispatchLines()->count());

        $receiptLines = [];
        foreach ($request->fresh()->dispatchLines as $dispatchLine) {
            $receiptLines[(string) $dispatchLine->id] = [
                'received_quantity' => $dispatchLine->quantity,
                'damaged_quantity' => 0,
            ];
        }
        DispatchReceiptRecorder::confirm($request->id, $this->user, $receiptLines, null, null, (string) Str::uuid());

        $usages = \App\Models\MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->orderBy('stock_in_id')->get();
        $this->assertSame([$firstBatch->id, $secondBatch->id], $usages->pluck('stock_in_id')->all());
        $this->assertSame([4.0, 3.0], $usages->map(fn ($usage) => (float) $usage->quantity)->all());
        $this->assertSame([100.0, 200.0], $usages->map(fn ($usage) => (float) $usage->unit_price_at_usage)->all());
    }

    public function test_material_partial_receipts_separate_transit_damage_cost_and_admin_corrections(): void
    {
        Storage::fake('public');
        $warehouse = Warehouse::create(['name' => 'TEST Partial Receipt Warehouse']);
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 100, 'unit_price' => 100]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 100,
            'remaining_quantity' => 100,
            'unit_price' => 100,
            'total_cost' => 10000,
            'date' => '2026-09-17',
        ]);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-PARTIAL-01',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 100,
            'notes' => 'Audit partial receipt',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [['source_id' => (string) $batch->id, 'quantity' => '100']])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();
        $line = $request->fresh()->dispatchLines()->firstOrFail();

        try {
            DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
                'received_quantity' => 98,
                'damaged_quantity' => 3,
            ]], null, null, (string) Str::uuid());
            $this->fail('Partial and damaged receipt requires evidence.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('arrivalProofImage', $exception->errors());
        }
        $this->assertDatabaseCount('dispatch_receipts', 0);

        $submissionKey = (string) Str::uuid();
        $firstReceipt = DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 98,
            'damaged_quantity' => 3,
        ]], UploadedFile::fake()->create('receipt.jpg', 120, 'image/jpeg'), '3 unit rusak, dua unit masih di jalan', $submissionKey);

        $retry = DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 1,
            'damaged_quantity' => 0,
        ]], null, null, $submissionKey);
        $this->assertSame($firstReceipt->id, $retry->id);
        $this->assertSame('partially_arrived', $request->fresh()->status);
        $this->assertSame(0.0, (float) $material->fresh()->stock);
        $this->assertSame(95.0, (float) MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->sum('quantity'));
        $this->assertSame(9500.0, (float) MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->sum('total_cost'));

        DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 2,
            'damaged_quantity' => 0,
        ]], null, 'Sisa tiba lengkap', (string) Str::uuid());

        $this->assertSame('arrived', $request->fresh()->status);
        $this->assertSame(100.0, (float) DispatchReceiptLine::whereHas('receipt', fn ($query) => $query->where('material_tool_request_id', $request->id))->sum('received_quantity'));
        $this->assertSame(3.0, (float) DispatchReceiptLine::whereHas('receipt', fn ($query) => $query->where('material_tool_request_id', $request->id))->sum('damaged_quantity'));
        $this->assertSame(97.0, (float) MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->sum('quantity'));
        $this->assertSame(9700.0, (float) MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->sum('total_cost'));
        $this->assertSame(300.0, (float) DispatchResolutionEvent::where('material_tool_request_id', $request->id)->where('event_type', 'material_damage_loss')->sum('total_cost'));
        $this->assertTrue($house->fresh()->hasOpenRequests(), 'Received damaged material needs a disposal decision.');
        Livewire::test(Dispatches::class)
            ->assertSeeText('Dikirim: 100')
            ->assertSeeText('Layak pakai: 97')
            ->assertSeeText('Rusak: 3')
            ->assertSeeText('Rusak belum dibuang: 3');

        $cluster = \App\Models\Cluster::create(['name' => 'TEST Receipt Correction Cluster']);
        $house->update(['cluster_id' => $cluster->id]);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        try {
            DispatchReceiptRecorder::confirm($request->id, $logistik, [(string) $line->id => [
                'received_quantity' => 100,
                'damaged_quantity' => 0,
            ]], null, 'Tidak boleh koreksi sebagai Logistik', (string) Str::uuid(), $firstReceipt->id);
            $this->fail('Only Admin can correct a receipt.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 100,
            'damaged_quantity' => 0,
        ]], null, 'Bukti foto menunjukkan semua unit layak pakai', (string) Str::uuid(), $firstReceipt->id);
        $this->assertSame(10000.0, (float) MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->sum('total_cost'));
        $this->assertSame(0.0, (float) DispatchResolutionEvent::where('material_tool_request_id', $request->id)->where('event_type', 'material_damage_loss')->sum('total_cost'));
        $this->assertFalse($house->fresh()->hasOpenRequests());
        $this->assertDatabaseHas('dispatch_receipts', [
            'material_tool_request_id' => $request->id,
            'correction_of_id' => $firstReceipt->id,
            'event_type' => 'correction',
        ]);
        $this->assertSame(98.0, (float) DispatchReceiptLine::where('dispatch_receipt_id', $firstReceipt->id)->sum('received_quantity'));
        $this->assertSame(-3.0, (float) DispatchReceiptLine::whereHas('receipt', fn ($query) => $query->where('correction_of_id', $firstReceipt->id))->sum('damaged_quantity'));
    }

    public function test_material_damage_and_transit_shortage_are_separate_costed_resolution_events(): void
    {
        Storage::fake('public');
        $warehouse = Warehouse::create(['name' => 'TEST Resolution Warehouse']);
        $material = Material::factory()->create(['warehouse_id' => $warehouse->id, 'stock' => 10, 'unit_price' => 100]);
        $batch = StockIn::create([
            'material_id' => $material->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'unit_price' => 100,
            'total_cost' => 1000,
            'date' => '2026-09-24',
        ]);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-RESOLVE-01',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 10,
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [['source_id' => (string) $batch->id, 'quantity' => '10']])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();
        $line = $request->fresh()->dispatchLines()->firstOrFail();

        DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 6,
            'damaged_quantity' => 2,
        ]], UploadedFile::fake()->create('damage.jpg', 100, 'image/jpeg'), 'Dua unit rusak; empat masih dalam perjalanan', (string) Str::uuid());

        $receiptLine = DispatchReceiptLine::whereHas('receipt', fn ($query) => $query->where('material_tool_request_id', $request->id))->firstOrFail();
        DispatchResolutionRecorder::record($request->id, $this->user, $line->id, 'dispose_damaged', 2, (string) Str::uuid(), 'Dua unit tidak dapat diperbaiki.');
        DispatchResolutionRecorder::record($request->id, $this->user, $line->id, 'return_to_warehouse', 1, (string) Str::uuid(), 'Satu unit yang belum diterima kembali utuh.');
        DispatchResolutionRecorder::record($request->id, $this->user, $line->id, 'declare_lost', 3, (string) Str::uuid(), 'Tiga unit tidak ditemukan saat pemeriksaan.');

        $this->assertSame('resolved', $request->fresh()->status);
        $this->assertSame(1.0, (float) $material->fresh()->stock);
        $this->assertSame(1.0, (float) $batch->fresh()->remaining_quantity);
        $this->assertSame(400.0, (float) MaterialUsage::where('dispatch_code', $request->fresh()->dispatch_code)->sum('total_cost'));
        $this->assertSame(500.0, (float) DispatchResolutionEvent::where('material_tool_request_id', $request->id)
            ->whereIn('event_type', ['material_damage_loss', 'declare_lost'])->sum('total_cost'));
        $this->assertDatabaseHas('dispatch_resolution_events', [
            'material_tool_request_id' => $request->id,
            'dispatch_receipt_line_id' => $receiptLine->id,
            'event_type' => 'material_damage_loss',
            'total_cost' => 200,
        ]);
        $this->assertSame(2.0, (float) DispatchResolutionEvent::where('material_tool_request_id', $request->id)->where('event_type', 'dispose_damaged')->sum('quantity'));
    }

    public function test_logistik_can_resolve_broken_tool_as_fixed_or_written_off(): void
    {
        $cluster = \App\Models\Cluster::create(['name' => 'TEST Broken Tool Cluster']);
        $house = House::factory()->create(['cluster_id' => $cluster->id]);
        $warehouse = Warehouse::create(['name' => 'TEST Broken Tool Warehouse']);
        $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);
        $tool = Tool::factory()->create(['warehouse_id' => $warehouse->id, 'total_qty' => 5, 'available_qty' => 3]);
        ToolInventory::ensureBalance($tool, $warehouse->id, 3, 0);
        ToolInventory::receive($tool, $warehouse->id, 0, 2);
        $usage = ToolUsage::create([
            'dispatch_code' => 'DSP-TEST-BROKEN-TOOL',
            'house_id' => $house->id,
            'tool_id' => $tool->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'quantity' => 2,
            'checkout_date' => '2026-09-23',
            'return_date' => '2026-09-24',
        ]);
        $logs = collect([1, 1])->map(fn () => ToolReturnLog::create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'tool_usage_id' => $usage->id,
            'reported_by' => $this->user->id,
            'receiving_warehouse_id' => $warehouse->id,
            'received_at' => now(),
            'received_by_id' => $this->user->id,
            'quantity' => 1,
            'report_type' => 'broken',
            'status' => 'pending',
            'notes' => 'Rusak di lapangan',
        ]));

        Livewire::actingAs($logistik)->test(ToolLog::class)
            ->set("resolutionNotesById.{$logs[0]->id}", 'Perbaikan selesai')
            ->set("repairCostsById.{$logs[0]->id}", '45000')
            ->call('resolveBrokenReturn', $logs[0]->id, 'fixed')
            ->assertHasNoErrors()
            ->set("resolutionNotesById.{$logs[1]->id}", 'Tidak layak diperbaiki')
            ->call('resolveBrokenReturn', $logs[1]->id, 'discarded')
            ->assertHasNoErrors();

        $this->assertSame(4, (int) $tool->fresh()->available_qty);
        $this->assertSame(0, (int) $tool->fresh()->qty_broken);
        $this->assertSame(4, (int) $tool->fresh()->total_qty);
        $this->assertDatabaseHas('tool_return_logs', [
            'id' => $logs[0]->id,
            'status' => 'fixed',
            'resolved_by_id' => $logistik->id,
            'resolution_notes' => 'Perbaikan selesai',
            'repair_cost' => 45000,
        ]);
        $this->assertDatabaseHas('tool_return_logs', [
            'id' => $logs[1]->id,
            'status' => 'discarded',
            'resolved_by_id' => $logistik->id,
            'resolution_notes' => 'Tidak layak diperbaiki',
        ]);
    }

    public function test_dispatch_reserves_tool_once_and_uses_receipt_date_for_checkout(): void
    {
        $warehouse = Warehouse::create(['name' => 'TEST Tool Dispatch Warehouse', 'address' => 'Test only']);
        $tool = Tool::factory()->create(['warehouse_id' => $warehouse->id, 'total_qty' => 2, 'available_qty' => 2]);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-TOOL-02',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'tool',
            'tool_id' => $tool->id,
            'quantity' => 1,
            'notes' => 'Audit tool approval',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [['source_id' => (string) $tool->warehouse_id, 'quantity' => '1']])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();

        $sourceWarehouseId = $tool->warehouse_id;
        $this->assertSame($sourceWarehouseId, $request->fresh()->source_warehouse_id);
        $this->assertSame(1, (int) ToolWarehouseBalance::where('tool_id', $tool->id)
            ->where('warehouse_id', $sourceWarehouseId)
            ->value('available_qty'));

        $line = $request->fresh()->dispatchLines()->firstOrFail();
        DispatchReceiptRecorder::confirm($request->id, $this->user, [(string) $line->id => [
            'received_quantity' => 1,
            'damaged_quantity' => 0,
        ]], null, null, (string) Str::uuid());

        $this->assertSame(1, (int) $tool->fresh()->available_qty);
        $this->assertDatabaseHas('tool_usages', [
            'dispatch_code' => $request->fresh()->dispatch_code,
            'dispatch_line_id' => $line->id,
            'checkout_date' => now()->toDateString().' 00:00:00',
            'warehouse_id' => $sourceWarehouseId,
            'warehouse_source_recorded' => true,
        ]);
    }

    public function test_legacy_tool_receipt_keeps_unknown_source_warehouse_unassigned(): void
    {
        $tool = Tool::factory()->create(['total_qty' => 2, 'available_qty' => 1]);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-TEST-LEGACY-TOOL-SOURCE',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'tool',
            'tool_id' => $tool->id,
            'quantity' => 1,
            'status' => 'dispatched',
            'dispatched_at' => now(),
            'source_warehouse_id' => null,
        ]);

        DispatchReceiptRecorder::confirm($request->id, $this->user, ['legacy' => [
            'received_quantity' => 1,
            'damaged_quantity' => 0,
        ]], null, null, (string) Str::uuid());

        $this->assertDatabaseHas('tool_usages', [
            'dispatch_code' => $request->request_code,
            'tool_id' => $tool->id,
            'warehouse_id' => null,
            'warehouse_source_recorded' => false,
        ]);
    }

    public function test_dispatch_uses_one_warehouse_balance_for_a_split_tool(): void
    {
        $warehouses = Warehouse::orderBy('id')->take(2)->get();
        if ($warehouses->count() < 2) {
            $warehouses->push(Warehouse::create([
                'name' => '[TEST] Audit Split Warehouse',
                'address' => 'Test fixture only',
            ]));
        }

        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouses[0]->id,
            'total_qty' => 3,
            'available_qty' => 1,
        ]);
        ToolWarehouseBalance::create([
            'tool_id' => $tool->id,
            'warehouse_id' => $warehouses[1]->id,
            'available_qty' => 2,
            'qty_broken' => 0,
        ]);
        ToolInventory::refreshAggregates($tool);

        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-TOOL-SPLIT-01',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'tool',
            'tool_id' => $tool->id,
            'quantity' => 2,
            'notes' => 'Audit split warehouse dispatch',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [
                ['source_id' => (string) $warehouses[0]->id, 'quantity' => '1'],
                ['source_id' => (string) $warehouses[1]->id, 'quantity' => '1'],
            ])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();

        $this->assertNull($request->fresh()->source_warehouse_id);
        $this->assertSame(0, (int) ToolWarehouseBalance::where('tool_id', $tool->id)
            ->where('warehouse_id', $warehouses[0]->id)
            ->value('available_qty'));
        $this->assertSame(1, (int) ToolWarehouseBalance::where('tool_id', $tool->id)
            ->where('warehouse_id', $warehouses[1]->id)
            ->value('available_qty'));
        $this->assertSame(1, (int) $tool->fresh()->available_qty);

        $receiptLines = [];
        foreach ($request->fresh()->dispatchLines as $dispatchLine) {
            $receiptLines[(string) $dispatchLine->id] = [
                'received_quantity' => $dispatchLine->quantity,
                'damaged_quantity' => 0,
            ];
        }
        DispatchReceiptRecorder::confirm($request->id, $this->user, $receiptLines, null, null, (string) Str::uuid());

        $usages = ToolUsage::where('dispatch_code', $request->fresh()->dispatch_code)->orderBy('warehouse_id')->get();
        $this->assertSame([$warehouses[0]->id, $warehouses[1]->id], $usages->pluck('warehouse_id')->all());
        $this->assertSame([1, 1], $usages->pluck('quantity')->all());
    }

    public function test_rejecting_a_dispatched_tool_waits_for_physical_return_before_releasing_stock(): void
    {
        $warehouse = Warehouse::create(['name' => 'TEST Tool Return Warehouse', 'address' => 'Test only']);
        $tool = Tool::factory()->create(['warehouse_id' => $warehouse->id, 'total_qty' => 3, 'available_qty' => 3]);
        $house = House::factory()->create(['status' => 'pembangunan']);
        $request = MaterialToolRequest::create([
            'request_code' => 'REQ-AUD-TOOL-01',
            'requester_id' => $this->user->id,
            'house_id' => $house->id,
            'type' => 'tool',
            'tool_id' => $tool->id,
            'quantity' => 2,
            'notes' => 'Audit tool dispatch',
            'status' => 'pending',
        ]);

        Livewire::test(Dispatches::class)
            ->call('dispatchRequest', $request->id)
            ->set('dispatchLines', [['source_id' => (string) $tool->warehouse_id, 'quantity' => '2']])
            ->set('dispatchProofImage', UploadedFile::fake()->image('dispatch-proof.jpg'))
            ->call('submitDispatch')
            ->assertHasNoErrors();

        $this->assertSame(1, (int) $tool->fresh()->available_qty);
        $sourceWarehouseId = $tool->warehouse_id;
        $this->assertSame($sourceWarehouseId, $request->fresh()->source_warehouse_id);

        Livewire::test(Dispatches::class)
            ->call('rejectRequest', $request->id)
            ->assertHasNoErrors();

        $this->assertSame(1, (int) $tool->fresh()->available_qty);
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertNull($request->fresh()->rejected_returned_at);

        Livewire::test(Dispatches::class)
            ->call('receiveRejectedReturn', $request->id)
            ->assertHasNoErrors();

        $this->assertSame(3, (int) $tool->fresh()->available_qty);
        $this->assertSame(3, (int) ToolWarehouseBalance::where('tool_id', $tool->id)
            ->where('warehouse_id', $sourceWarehouseId)
            ->value('available_qty'));
        $this->assertNotNull($request->fresh()->rejected_returned_at);
    }
}

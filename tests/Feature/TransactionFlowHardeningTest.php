<?php

use App\Livewire\Logistik\HouseFinish;
use App\Livewire\Logistik\Dispatches;
use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\ToolLog;
use App\Livewire\Logistik\Tools;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\DispatchReceipt;
use App\Models\DispatchLine;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MaterialToolRequest;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->cluster = Cluster::create(['name' => 'TEST Transaction Cluster']);
    $this->warehouse = Warehouse::create(['name' => 'TEST Transaction Defaults']);
    $this->user = User::factory()->create(['role' => 'logistik', 'cluster_id' => $this->cluster->id]);
    $this->materialCategory = Category::factory()->material()->create();
    $this->supplier = Supplier::factory()->create();
    $this->actingAs($this->user);
});

test('material allocation requires an explicit receipt batch selection', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'stock' => 10,
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('material_id', $material->id)
        ->set('material_quantity', 1)
        ->set('material_notes', 'Alokasi tanpa batch')
        ->call('saveMaterial')
        ->assertHasErrors('material_batch_id');

    expect((float) $material->fresh()->stock)->toBe(10.0)
        ->and(MaterialUsage::count())->toBe(0);
});

test('material allocation records the optional taker and clears the field after saving', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'stock' => 5,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'quantity' => 5,
        'remaining_quantity' => 5,
        'unit_price' => 1000,
        'total_cost' => 5000,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    $component = Livewire::test(TransaksiLogistik::class)
        ->assertSee('Pengambil')
        ->assertSee('Belum ada riwayat nama pengambil. Ketik nama untuk mencatatnya.')
        ->set('house_ids', [$house->id])
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('material_quantity', 1)
        ->set('material_notes', 'Pemasangan pondasi')
        ->set('material_taken_by', 'Budi Santoso')
        ->call('showMaterialConfirmationModal')
        ->assertHasNoErrors()
        ->assertSet('materialConfirmationData.takenBy', 'Budi Santoso')
        ->call('saveMaterial')
        ->assertHasNoErrors()
        ->assertSet('material_taken_by', '')
        ->assertSee('Budi Santoso');

    expect(MaterialUsage::where('house_id', $house->id)->value('taken_by'))->toBe('Budi Santoso');
});

test('material selection leaves receipt batch choice to the operator', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'stock' => 12,
        'unit_price' => 25000,
    ]);
    $smallerBatch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'quantity' => 2,
        'remaining_quantity' => 2,
        'unit_price' => 20000,
        'total_cost' => 40000,
        'date' => now()->toDateString(),
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_price' => 25000,
        'total_cost' => 250000,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->call('selectMaterial', $material->id)
        ->set('material_quantity', 3)
        ->assertSet('material_id', (string) $material->id)
        ->assertSet('material_batch_id', '')
        ->assertSet('materialBatchMap.'.$batch->id.'.remaining_quantity', 10.0)
        ->assertSet('materialBatchMap.'.$batch->id.'.unit_price', 25000.0)
        ->assertSet('materialBatchMap.'.$smallerBatch->id.'.remaining_quantity', 2.0)
        ->assertSee('Batch masuk')
        ->assertSee('Pilih batch masuk')
        ->set('material_batch_id', $batch->id)
        ->assertSet('material_batch_id', (string) $batch->id);
});

test('material allocation preview uses selected receipt price instead of catalog price', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'stock' => 10,
        'unit_price' => 30000,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_price' => 25000,
        'total_cost' => 250000,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('material_quantity', 2)
        ->set('material_notes', 'Konfirmasi memakai harga batch')
        ->call('showMaterialConfirmationModal')
        ->assertHasNoErrors()
        ->assertSet('materialConfirmationData.unitPrice', 25000)
        ->assertSet('materialConfirmationData.totalCost', 50000)
        ->assertSet('showMaterialConfirmation', true);
});

test('decimal material allocation consumes the selected batch immediately', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'unit' => 'm3',
        'unit_price' => 100000,
        'stock' => 10,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $material->warehouse_id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_price' => 100000,
        'total_cost' => 1000000,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id]);

    Livewire::test(TransaksiLogistik::class)
        ->set('activeTab', 'material')
        ->set('material_id', $material->id)
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('house_ids', [$house->id])
        ->set('material_quantity', 0.4)
        ->set('material_notes', 'Pekerjaan pondasi')
        ->call('saveMaterial')
        ->assertHasNoErrors();

    expect((float) $material->fresh()->stock)->toBe(9.6);
    expect((float) $batch->fresh()->remaining_quantity)->toBe(9.6);
    expect($batch->fresh()->available_quantity)->toBe(9.6);
    expect((float) MaterialUsage::sole()->quantity)->toBe(0.4);
});

test('A1 restock accepts fractional quantity', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'unit' => 'm3',
        'unit_price' => 50000,
        'stock' => 1.5,
        'name' => 'Pasir Halus',
    ]);

    Livewire::test(Materials::class)
        ->call('restock', $material->id)
        ->set('restockQuantity', 0.75)
        ->set('restockUnitPrice', 50000)
        ->call('saveRestock')
        ->assertHasNoErrors();

    expect((float) $material->fresh()->stock)->toBe(2.25);
    expect((float) StockIn::latest('id')->first()->quantity)->toBe(0.75);
});

test('A1 material create accepts decimal initial stock', function () {
    Livewire::test(Materials::class)
        ->call('create')
        ->set('name', 'Batu Split')
        ->set('unit', 'm3')
        ->set('unit_price', 200000)
        ->set('stock', 2.5)
        ->set('category_id', $this->materialCategory->id)
        ->set('supplier_name', $this->supplier->name)
        ->call('save')
        ->assertHasNoErrors();

    $created = Material::where('name', 'Batu Split')->first();
    expect($created)->not->toBeNull();
    expect((float) $created->stock)->toBe(2.5);
});

// ─────────────────────────────────────────
// Slice 3 — Transaksi flow hardening (A2/A3/A4/C)
// ─────────────────────────────────────────

test('A2 broken return moves to qty_broken pool, available unchanged', function () {
    $tool = Tool::factory()->create(['condition' => 'baik', 'total_qty' => 20, 'available_qty' => 10, 'qty_broken' => 0]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 5, 'return_date' => null]);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('activeTab', 'return')
        ->set('returnSelections', [$usage->id => ['selected' => true, 'qty_normal' => 0, 'qty_broken' => 3, 'qty_lost' => 0, 'notes' => 'jatuh']])
        ->call('showReturnConfirmationModal')
        ->call('saveReturn')
        ->assertHasNoErrors();

    $tool->refresh();
    expect($tool->available_qty)->toBe(10);
    expect($tool->qty_broken)->toBe(3);
    expect($tool->condition)->toBe('rusak');
});

test('tool allocation checks out units immediately with proof', function () {
    $tool = Tool::factory()->create(['condition' => 'baik', 'total_qty' => 20, 'available_qty' => 20, 'qty_broken' => 0]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('tool_id', $tool->id)
        ->set('tool_quantity', 4)
        ->set('tool_notes', 'Pekerjaan pondasi')
        ->set('toolAllocationProofImage', UploadedFile::fake()->image('tool-allocation.jpg'))
        ->call('saveTool')
        ->assertHasNoErrors();

    expect((int) $tool->fresh()->available_qty)->toBe(16);
    expect((int) ToolUsage::where('tool_id', $tool->id)->sum('quantity'))->toBe(4);
    expect(MaterialToolRequest::where('tool_id', $tool->id)->count())->toBe(0);

    // Livewire 4: component methods are NOT auto-forwarded by Testable::__call,
    // so access through ->instance() (Testable.php:411-418 forwards the call to
    // the TestResponse, which has no such method).
    $component = Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('activeTab', 'return');
    expect($component->instance()->getActiveToolUsages())->toHaveCount(1);
});

test('A4 partial return remainder sets parent_usage_id', function () {
    $tool = Tool::factory()->create(['condition' => 'baik', 'total_qty' => 10, 'available_qty' => 10, 'qty_broken' => 0]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 5, 'return_date' => null]);
    $request = \App\Models\MaterialToolRequest::create([
        'request_code' => 'REQ-AUD-PARTIAL-RETURN',
        'dispatch_code' => 'DSP-AUD-PARTIAL-RETURN',
        'requester_id' => $this->user->id,
        'house_id' => $house->id,
        'type' => 'tool',
        'tool_id' => $tool->id,
        'quantity' => 5,
        'status' => 'arrived',
    ]);
    $line = DispatchLine::create([
        'material_tool_request_id' => $request->id,
        'tool_id' => $tool->id,
        'warehouse_id' => $tool->warehouse_id,
        'quantity' => 5,
        'unit_price' => $tool->purchase_price,
    ]);
    $usage->update(['dispatch_code' => $request->dispatch_code, 'dispatch_line_id' => $line->id]);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('activeTab', 'return')
        ->set('returnSelections', [$usage->id => ['selected' => true, 'qty_normal' => 2, 'qty_broken' => 1, 'qty_lost' => 0, 'notes' => '']])
        ->call('showReturnConfirmationModal')
        ->call('saveReturn')
        ->assertHasNoErrors();

    $remainder = ToolUsage::whereNotNull('parent_usage_id')->first();
    expect($remainder)->not->toBeNull();
    expect($remainder->parent_usage_id)->toBe($usage->id);
    expect($remainder->dispatch_code)->toBe($request->dispatch_code);
    expect($remainder->dispatch_line_id)->toBe($line->id);
    expect((int) $remainder->quantity)->toBe(2); // 5 - (2 normal + 1 broken)
});

test('cross-warehouse partial return records units in the receiving balance', function () {
    $source = Warehouse::create(['name' => '[TEST] Return Source Warehouse']);
    $target = Warehouse::create(['name' => '[TEST] Return Target Warehouse']);
    $tool = Tool::factory()->create([
        'warehouse_id' => $source->id,
        'total_qty' => 2,
        'available_qty' => 1,
        'qty_broken' => 0,
        'condition' => 'baik',
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create([
        'house_id' => $house->id,
        'tool_id' => $tool->id,
        'user_id' => $this->user->id,
        'quantity' => 1,
        'return_date' => null,
    ]);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('activeTab', 'return')
        ->set('returnSelections', [$usage->id => [
            'selected' => true,
            'qty_normal' => 1,
            'qty_broken' => 0,
            'qty_lost' => 0,
            'receiving_warehouse_id' => $target->id,
            'notes' => '[TEST] split batch remains at source',
        ]])
        ->call('showReturnConfirmationModal')
        ->call('saveReturn')
        ->assertHasNoErrors();

    $tool->refresh();
    $usage->refresh();
    expect($tool->warehouse_id)->toBe($source->id);
    expect((int) $tool->available_qty)->toBe(2);
    expect($usage->return_date)->not->toBeNull();
    expect(ToolReturnLog::where('tool_usage_id', $usage->id)->exists())->toBeTrue();
    expect((int) DB::table('tool_warehouse_balances')->where(['tool_id' => $tool->id, 'warehouse_id' => $source->id])->value('available_qty'))->toBe(1);
    expect((int) DB::table('tool_warehouse_balances')->where(['tool_id' => $tool->id, 'warehouse_id' => $target->id])->value('available_qty'))->toBe(1);
});

test('C duplicate active checkout for same tool+house rejected', function () {
    $tool = Tool::factory()->create(['condition' => 'baik', 'total_qty' => 50, 'available_qty' => 50, 'qty_broken' => 0]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])->set('tool_id', $tool->id)->set('tool_quantity', 5)->set('tool_notes', 'Pekerjaan pondasi')
        ->set('toolAllocationProofImage', UploadedFile::fake()->image('tool-allocation.jpg'))
        ->call('saveTool')->assertHasNoErrors();

    expect(ToolUsage::where('tool_id', $tool->id)->where('house_id', $house->id)->count())->toBe(1);
    expect((int) $tool->fresh()->available_qty)->toBe(45);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])->set('tool_id', $tool->id)->set('tool_quantity', 5)->set('tool_notes', 'Pekerjaan pondasi')
        ->set('toolAllocationProofImage', UploadedFile::fake()->image('tool-allocation-again.jpg'))
        ->call('saveTool')->assertHasErrors(['tool_quantity']);
});

test('C saving guard blocks re-entrant save', function () {
    $tool = Tool::factory()->create(['condition' => 'baik', 'total_qty' => 50, 'available_qty' => 50, 'qty_broken' => 0]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])->set('tool_id', $tool->id)->set('tool_quantity', 5)
        ->set('saving', true)
        ->call('saveTool');

    expect((int) $tool->fresh()->available_qty)->toBe(50);
});

test('final material save rejects stale or invalid form data without changing stock', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'stock' => 10,
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id, $house->id])
        ->set('material_id', $material->id)
        ->call('selectMaterial', $material->id)
        ->set('material_quantity', 2)
        ->set('material_notes', '')
        ->call('saveMaterial')
        ->assertHasErrors(['house_ids.0', 'material_batch_id', 'material_notes']);

    expect((float) $material->fresh()->stock)->toBe(10.0);
    expect(MaterialUsage::count())->toBe(0);
});

test('final material save rechecks house status after confirmation', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'stock' => 10,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $material->warehouse_id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_price' => $material->unit_price,
        'total_cost' => 10 * $material->unit_price,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    $component = Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('material_id', $material->id)
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('material_quantity', 2)
        ->set('material_notes', 'Pekerjaan dinding')
        ->call('showMaterialConfirmationModal')
        ->assertSet('showMaterialConfirmation', true);

    $house->update(['status' => 'selesai', 'warranty_expires_at' => now()->subDay()]);

    $component->call('saveMaterial')
        ->assertHasErrors(['house_ids'])
        ->assertSet('showMaterialConfirmation', true);

    expect((float) $material->fresh()->stock)->toBe(10.0);
    expect(MaterialUsage::count())->toBe(0);
    expect(MaterialToolRequest::where('material_id', $material->id)->count())->toBe(0);
});

test('material allocations write usage rows and consume one selected batch', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'stock' => 20,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $material->warehouse_id,
        'quantity' => 20,
        'remaining_quantity' => 20,
        'unit_price' => $material->unit_price,
        'total_cost' => 20 * $material->unit_price,
        'date' => now()->toDateString(),
    ]);
    $houses = House::factory()->count(2)->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $houseIds = $houses->pluck('id')->all();

    $component = Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', $houseIds)
        ->set('material_id', $material->id)
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('material_quantity', 2)
        ->set('material_notes', 'Pekerjaan atap');

    $component->call('saveMaterial')->assertHasNoErrors();
    expect(MaterialUsage::where('material_id', $material->id)->count())->toBe(2);
    expect((float) $material->fresh()->stock)->toBe(16.0);
    expect((float) $batch->fresh()->remaining_quantity)->toBe(16.0);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', $houseIds)
        ->set('material_id', $material->id)
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('material_quantity', 2)
        ->set('material_notes', 'Pekerjaan atap')
        ->call('saveMaterial')
        ->assertHasNoErrors();

    expect(MaterialToolRequest::where('material_id', $material->id)->count())->toBe(0)
        ->and((float) MaterialUsage::where('material_id', $material->id)->sum('quantity'))->toBe(8.0)
        ->and((float) $material->fresh()->stock)->toBe(12.0)
        ->and((float) $batch->fresh()->remaining_quantity)->toBe(12.0)
        ->and($batch->fresh()->available_quantity)->toBe(12.0);
});

test('tool allocation requires a purpose but no checkout date', function () {
    $tool = Tool::factory()->create(['total_qty' => 10, 'available_qty' => 10]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('tool_id', $tool->id)
        ->set('tool_quantity', 2)
        ->set('tool_notes', '')
        ->call('saveTool')
        ->assertHasErrors(['tool_notes']);

    expect((int) $tool->fresh()->available_qty)->toBe(10);
    expect(ToolUsage::count())->toBe(0);
});

test('tool allocation creates one active loan per house and blocks duplicates', function () {
    $tool = Tool::factory()->create(['total_qty' => 10, 'available_qty' => 10]);
    $houses = House::factory()->count(2)->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $houseIds = $houses->pluck('id')->all();

    $component = Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', $houseIds)
        ->set('tool_id', $tool->id)
        ->set('tool_quantity', 2)
        ->set('tool_notes', 'Pekerjaan dinding')
        ->set('toolAllocationProofImage', UploadedFile::fake()->image('tool-allocation.jpg'));

    $component->call('saveTool')->assertHasNoErrors();
    expect(ToolUsage::where('tool_id', $tool->id)->count())->toBe(2)
        ->and((int) ToolUsage::where('tool_id', $tool->id)->sum('quantity'))->toBe(4)
        ->and((int) $tool->fresh()->available_qty)->toBe(6)
        ->and(MaterialToolRequest::where('tool_id', $tool->id)->count())->toBe(0);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', $houseIds)
        ->set('tool_id', $tool->id)
        ->set('tool_quantity', 2)
        ->set('tool_notes', 'Pekerjaan dinding')
        ->set('toolAllocationProofImage', UploadedFile::fake()->image('tool-allocation-again.jpg'))
        ->call('saveTool')
        ->assertHasErrors(['tool_quantity']);

    expect(ToolUsage::where('tool_id', $tool->id)->count())->toBe(2);
});

// ─────────────────────────────────────────
// Slice 4 — Return mirror + tools CRUD (A2 mirror)
// ─────────────────────────────────────────

test('A2 mirror HouseFinish broken completion moves to qty_broken, available untouched', function () {
    $tool = Tool::factory()->create(['condition' => 'baik', 'total_qty' => 20, 'available_qty' => 10, 'qty_broken' => 0]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 5, 'return_date' => null]);

    Livewire::test(HouseFinish::class, ['house' => $house])
        ->set('toolSelections', [$usage->id => ['action' => 'broken', 'notes' => '', 'replacement_cost' => '', 'has_charge' => false]])
        ->call('processCompletion')
        ->assertHasNoErrors();

    $tool->refresh();
    expect($tool->qty_broken)->toBe(5);
    expect($tool->available_qty)->toBe(10); // NOT 15 — A2 bug fixed on the house-finish path too
    expect($tool->condition)->toBe('rusak');
    expect($house->fresh()->status)->toBe('selesai');
});

test('A2 tools CRUD persists qty_broken on edit', function () {
    $tool = Tool::factory()->create(['condition' => 'rusak', 'total_qty' => 10, 'available_qty' => 8, 'qty_broken' => 2]);

    Livewire::test(Tools::class)
        ->call('edit', $tool->id)
        ->set('qty_broken', 3)
        ->set('available_qty', 7)
        ->call('save')
        ->assertHasNoErrors();

    $tool->refresh();
    expect((int) $tool->qty_broken)->toBe(3);
    expect((int) $tool->available_qty)->toBe(7);
});

// ─────────────────────────────────────────
// Slice 5 — Material void (B5-material)
// ─────────────────────────────────────────

test('B5 material void restores stock and excludes from cost aggregates', function () {
    $material = Material::factory()->create(['category_id' => $this->materialCategory->id, 'supplier_id' => $this->supplier->id, 'unit' => 'sak', 'unit_price' => 50000, 'stock' => 10]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $material->warehouse_id,
        'quantity' => 10,
        'remaining_quantity' => 8,
        'unit_price' => 50000,
        'total_cost' => 500000,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = MaterialUsage::factory()->create([
        'house_id' => $house->id,
        'material_id' => $material->id,
        'stock_in_id' => $batch->id,
        'user_id' => $this->user->id,
        'quantity' => 2,
        'unit_price_at_usage' => 50000,
        'total_cost' => 100000,
    ]);

    expect(MaterialUsage::whereNull('voided_at')->sum('total_cost'))->toEqual(100000.0);
    expect((float) $house->total_material_cost)->toBe(100000.0);

    Livewire::test(MaterialLog::class)
        ->call('voidMaterial', $usage->id)
        ->assertHasNoErrors();

    expect((float) $material->fresh()->stock)->toBe(12.0);
    expect((float) $batch->fresh()->remaining_quantity)->toBe(10.0);
    $usage->refresh();
    expect($usage->voided_at)->not->toBeNull();
    expect($usage->voided_by)->toBe($this->user->id);
    expect(MaterialUsage::whereNull('voided_at')->sum('total_cost'))->toBe(0);
    expect((float) $house->fresh()->total_material_cost)->toBe(0.0);
});

test('B5 material void rejects double void', function () {
    $material = Material::factory()->create(['category_id' => $this->materialCategory->id, 'supplier_id' => $this->supplier->id, 'unit' => 'sak', 'unit_price' => 50000, 'stock' => 5]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = MaterialUsage::factory()->create(['house_id' => $house->id, 'material_id' => $material->id, 'user_id' => $this->user->id, 'quantity' => 1, 'unit_price_at_usage' => 50000, 'total_cost' => 50000]);

    $log = Livewire::test(MaterialLog::class);
    $log->call('voidMaterial', $usage->id)->assertHasNoErrors();
    $log->call('voidMaterial', $usage->id)->assertHasErrors(['void']);

    expect((float) $material->fresh()->stock)->toBe(6.0); // restored once only
});

// ─────────────────────────────────────────
// Slice 6 — Tool void (B5-tool)
// ─────────────────────────────────────────

test('B5 tool void restores available_qty and excludes from loan count', function () {
    $toolCategory = Category::factory()->tool()->create();
    $tool = Tool::factory()->create(['warehouse_id' => $this->warehouse->id, 'category_id' => $toolCategory->id, 'total_qty' => 10, 'available_qty' => 8, 'qty_broken' => 0, 'condition' => 'baik']);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create([
        'house_id' => $house->id,
        'tool_id' => $tool->id,
        'warehouse_id' => $this->warehouse->id,
        'warehouse_source_recorded' => true,
        'user_id' => $this->user->id,
        'quantity' => 2,
        'checkout_date' => now()->subDays(3),
        'return_date' => null,
    ]);

    expect(ToolUsage::whereNull('return_date')->whereNull('voided_at')->count())->toBe(1);

    Livewire::test(ToolLog::class)
        ->call('voidTool', $usage->id)
        ->assertHasNoErrors();

    expect((int) $tool->fresh()->available_qty)->toBe(10);
    $usage->refresh();
    expect($usage->voided_at)->not->toBeNull();
    expect($usage->voided_by)->toBe($this->user->id);
    expect(ToolUsage::whereNull('return_date')->whereNull('voided_at')->count())->toBe(0);
});

test('legacy tool void does not trust an unverified warehouse copied from current tool location', function () {
    $toolCategory = Category::factory()->tool()->create();
    $currentWarehouse = Warehouse::create(['name' => 'TEST Gudang alat saat ini']);
    $tool = Tool::factory()->create([
        'category_id' => $toolCategory->id,
        'warehouse_id' => $currentWarehouse->id,
        'total_qty' => 10,
        'available_qty' => 8,
        'qty_broken' => 0,
        'condition' => 'baik',
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create([
        'house_id' => $house->id,
        'tool_id' => $tool->id,
        'user_id' => $this->user->id,
        'warehouse_id' => $this->warehouse->id,
        'warehouse_source_recorded' => false,
        'quantity' => 2,
        'checkout_date' => now()->subDays(3),
        'return_date' => null,
    ]);

    Livewire::test(ToolLog::class)
        ->call('voidTool', $usage->id)
        ->assertHasErrors(['void']);

    expect((int) $tool->fresh()->available_qty)->toBe(8)
        ->and($usage->fresh()->voided_at)->toBeNull()
        ->and((int) $tool->warehouseBalances()->where('warehouse_id', $currentWarehouse->id)->value('available_qty'))->toBe(8);
});

test('B5 tool void rejects already-returned checkout', function () {
    $toolCategory = Category::factory()->tool()->create();
    $tool = Tool::factory()->create(['category_id' => $toolCategory->id, 'total_qty' => 5, 'available_qty' => 4]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);
    $usage = ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 1, 'checkout_date' => now()->subDays(5), 'return_date' => now()->subDays(1)]);

    Livewire::test(ToolLog::class)
        ->call('voidTool', $usage->id)
        ->assertHasErrors(['void']);

    expect((int) $tool->fresh()->available_qty)->toBe(4); // unchanged
});

// ─────────────────────────────────────────
// Direct Logistik Transaction Flow with Photo Proof
// ─────────────────────────────────────────

test('Logistik allocates a batch with proof and records its cost immediately', function () {
    Storage::fake('public');
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'unit' => 'sak',
        'unit_price' => 50000,
        'stock' => 20,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $material->warehouse_id,
        'quantity' => 20,
        'remaining_quantity' => 20,
        'unit_price' => 50000,
        'total_cost' => 1000000,
        'date' => now()->toDateString(),
    ]);
    $house = House::factory()->create(['cluster_id' => $this->cluster->id, 'status' => 'pembangunan']);

    $this->actingAs($this->user);
    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('material_id', $material->id)
        ->call('selectMaterial', $material->id)
        ->set('material_batch_id', $batch->id)
        ->set('material_quantity', 5)
        ->set('material_notes', 'Cor Pondasi')
        ->set('materialAllocationProofImage', UploadedFile::fake()->image('allocation-proof.jpg'))
        ->call('saveMaterial')
        ->assertHasNoErrors();

    $usage = MaterialUsage::where('house_id', $house->id)->sole();
    expect($usage->stock_in_id)->toBe($batch->id)
        ->and((float) $usage->total_cost)->toBe(250000.0)
        ->and((float) $material->fresh()->stock)->toBe(15.0)
        ->and((float) $batch->fresh()->remaining_quantity)->toBe(15.0)
        ->and(MaterialToolRequest::where('house_id', $house->id)->count())->toBe(0);
    Storage::disk('public')->assertExists($usage->proof_image);
});

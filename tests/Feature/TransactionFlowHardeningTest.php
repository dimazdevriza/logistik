<?php

use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Category;
use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'logistik']);
    $this->materialCategory = Category::factory()->material()->create();
    $this->supplier = Supplier::factory()->create();
    $this->actingAs($this->user);
});

test('A1 decimal stock: allocating 0.4 from 10 leaves 9.60 not 10', function () {
    $material = Material::factory()->create([
        'category_id' => $this->materialCategory->id,
        'supplier_id' => $this->supplier->id,
        'unit' => 'm3',
        'unit_price' => 100000,
        'stock' => 10,
    ]);
    $house = House::factory()->create();

    Livewire::test(TransaksiLogistik::class)
        ->set('activeTab', 'material')
        ->set('material_id', $material->id)
        ->set('house_ids', [$house->id])
        ->set('material_quantity', 0.4)
        ->set('usage_date', now()->format('Y-m-d'))
        ->call('saveMaterial')
        ->assertHasNoErrors();

    expect((float) $material->fresh()->stock)->toBe(9.60);
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
        ->set('restockDate', now()->format('Y-m-d'))
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
    $tool = App\Models\Tool::factory()->create(['condition' => 'baik', 'total_qty' => 20, 'available_qty' => 10, 'qty_broken' => 0]);
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 5, 'return_date' => null]);

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

test('A3 checkout always decrements and surfaces in return tab', function () {
    $tool = App\Models\Tool::factory()->create(['condition' => 'baik', 'total_qty' => 20, 'available_qty' => 20, 'qty_broken' => 0]);
    $house = House::factory()->create(['status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('tool_id', $tool->id)
        ->set('tool_quantity', 4)
        ->set('checkout_date', now()->format('Y-m-d'))
        ->call('saveTool')
        ->assertHasNoErrors();

    expect((int) $tool->fresh()->available_qty)->toBe(16);

    // Livewire 4: component methods are NOT auto-forwarded by Testable::__call,
    // so access through ->instance() (Testable.php:411-418 forwards the call to
    // the TestResponse, which has no such method).
    $component = Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('activeTab', 'return');
    expect($component->instance()->getActiveToolUsages())->toHaveCount(1);
});

test('A4 partial return remainder sets parent_usage_id', function () {
    $tool = App\Models\Tool::factory()->create(['condition' => 'baik', 'total_qty' => 10, 'available_qty' => 10, 'qty_broken' => 0]);
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 5, 'return_date' => null]);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])
        ->set('activeTab', 'return')
        ->set('returnSelections', [$usage->id => ['selected' => true, 'qty_normal' => 2, 'qty_broken' => 1, 'qty_lost' => 0, 'notes' => '']])
        ->call('showReturnConfirmationModal')
        ->call('saveReturn')
        ->assertHasNoErrors();

    $remainder = App\Models\ToolUsage::whereNotNull('parent_usage_id')->first();
    expect($remainder)->not->toBeNull();
    expect($remainder->parent_usage_id)->toBe($usage->id);
    expect((int) $remainder->quantity)->toBe(2); // 5 - (2 normal + 1 broken)
});

test('C duplicate active checkout for same tool+house rejected', function () {
    $tool = App\Models\Tool::factory()->create(['condition' => 'baik', 'total_qty' => 50, 'available_qty' => 50, 'qty_broken' => 0]);
    $house = House::factory()->create(['status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])->set('tool_id', $tool->id)->set('tool_quantity', 5)->set('checkout_date', now()->format('Y-m-d'))
        ->call('saveTool')->assertHasNoErrors();

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])->set('tool_id', $tool->id)->set('tool_quantity', 5)->set('checkout_date', now()->format('Y-m-d'))
        ->call('saveTool')->assertHasErrors(['tool_quantity']);
});

test('C saving guard blocks re-entrant save', function () {
    $tool = App\Models\Tool::factory()->create(['condition' => 'baik', 'total_qty' => 50, 'available_qty' => 50, 'qty_broken' => 0]);
    $house = House::factory()->create(['status' => 'pembangunan']);

    Livewire::test(TransaksiLogistik::class)
        ->set('house_ids', [$house->id])->set('tool_id', $tool->id)->set('tool_quantity', 5)->set('checkout_date', now()->format('Y-m-d'))
        ->set('saving', true)
        ->call('saveTool');

    expect((int) $tool->fresh()->available_qty)->toBe(50);
});

// ─────────────────────────────────────────
// Slice 4 — Return mirror + tools CRUD (A2 mirror)
// ─────────────────────────────────────────

test('A2 mirror HouseFinish broken completion moves to qty_broken, available untouched', function () {
    $tool = App\Models\Tool::factory()->create(['condition' => 'baik', 'total_qty' => 20, 'available_qty' => 10, 'qty_broken' => 0]);
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 5, 'return_date' => null]);

    Livewire::test(App\Livewire\Logistik\HouseFinish::class, ['house' => $house])
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
    $tool = App\Models\Tool::factory()->create(['condition' => 'rusak', 'total_qty' => 10, 'available_qty' => 8, 'qty_broken' => 2]);

    Livewire::test(App\Livewire\Logistik\Tools::class)
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
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\MaterialUsage::factory()->create([
        'house_id' => $house->id,
        'material_id' => $material->id,
        'user_id' => $this->user->id,
        'quantity' => 2,
        'unit_price_at_usage' => 50000,
        'total_cost' => 100000,
    ]);

    expect(\App\Models\MaterialUsage::whereNull('voided_at')->sum('total_cost'))->toEqual(100000.0);
    expect((float) $house->total_material_cost)->toBe(100000.0);

    Livewire::test(App\Livewire\Logistik\MaterialLog::class)
        ->call('voidMaterial', $usage->id)
        ->assertHasNoErrors();

    expect((float) $material->fresh()->stock)->toBe(12.0);
    $usage->refresh();
    expect($usage->voided_at)->not->toBeNull();
    expect($usage->voided_by)->toBe($this->user->id);
    expect(\App\Models\MaterialUsage::whereNull('voided_at')->sum('total_cost'))->toBe(0);
    expect((float) $house->fresh()->total_material_cost)->toBe(0.0);
});

test('B5 material void rejects double void', function () {
    $material = Material::factory()->create(['category_id' => $this->materialCategory->id, 'supplier_id' => $this->supplier->id, 'unit' => 'sak', 'unit_price' => 50000, 'stock' => 5]);
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\MaterialUsage::factory()->create(['house_id' => $house->id, 'material_id' => $material->id, 'user_id' => $this->user->id, 'quantity' => 1, 'unit_price_at_usage' => 50000, 'total_cost' => 50000]);

    $log = Livewire::test(App\Livewire\Logistik\MaterialLog::class);
    $log->call('voidMaterial', $usage->id)->assertHasNoErrors();
    $log->call('voidMaterial', $usage->id)->assertHasErrors(['void']);

    expect((float) $material->fresh()->stock)->toBe(6.0); // restored once only
});

// ─────────────────────────────────────────
// Slice 6 — Tool void (B5-tool)
// ─────────────────────────────────────────

test('B5 tool void restores available_qty and excludes from loan count', function () {
    $toolCategory = Category::factory()->tool()->create();
    $tool = App\Models\Tool::factory()->create(['category_id' => $toolCategory->id, 'total_qty' => 10, 'available_qty' => 8, 'qty_broken' => 0, 'condition' => 'baik']);
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\ToolUsage::factory()->create([
        'house_id' => $house->id,
        'tool_id' => $tool->id,
        'user_id' => $this->user->id,
        'quantity' => 2,
        'checkout_date' => now()->subDays(3),
        'return_date' => null,
    ]);

    expect(App\Models\ToolUsage::whereNull('return_date')->whereNull('voided_at')->count())->toBe(1);

    Livewire::test(App\Livewire\Logistik\ToolLog::class)
        ->call('voidTool', $usage->id)
        ->assertHasNoErrors();

    expect((int) $tool->fresh()->available_qty)->toBe(10);
    $usage->refresh();
    expect($usage->voided_at)->not->toBeNull();
    expect($usage->voided_by)->toBe($this->user->id);
    expect(App\Models\ToolUsage::whereNull('return_date')->whereNull('voided_at')->count())->toBe(0);
});

test('B5 tool void rejects already-returned checkout', function () {
    $toolCategory = Category::factory()->tool()->create();
    $tool = App\Models\Tool::factory()->create(['category_id' => $toolCategory->id, 'total_qty' => 5, 'available_qty' => 4]);
    $house = House::factory()->create(['status' => 'pembangunan']);
    $usage = App\Models\ToolUsage::factory()->create(['house_id' => $house->id, 'tool_id' => $tool->id, 'user_id' => $this->user->id, 'quantity' => 1, 'checkout_date' => now()->subDays(5), 'return_date' => now()->subDays(1)]);

    Livewire::test(App\Livewire\Logistik\ToolLog::class)
        ->call('voidTool', $usage->id)
        ->assertHasErrors(['void']);

    expect((int) $tool->fresh()->available_qty)->toBe(4); // unchanged
});

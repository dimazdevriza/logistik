<?php

use App\Livewire\Logistik\TransaksiLogistik;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\ToolWarehouseBalance;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

test('500 houses remain paged and selections survive search, cluster filters and removal', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $clusterA = Cluster::create(['name' => 'Cluster Anggrek']);
    $clusterB = Cluster::create(['name' => 'Cluster Bougenville']);
    House::factory()->count(500)->sequence(fn ($sequence) => [
        'name' => 'Blok '.($sequence->index < 250 ? 'A' : 'B').'-'.($sequence->index + 1),
        'house_code' => '2026-TEST-'.($sequence->index + 1),
        'cluster_id' => $sequence->index < 250 ? $clusterA->id : $clusterB->id,
    ])->create();
    $first = House::where('name', 'Blok A-1')->firstOrFail();
    $last = House::where('name', 'Blok B-500')->firstOrFail();

    $component = Livewire::test(TransaksiLogistik::class)
        ->assertViewHas('houseResultCount', 500)
        ->assertViewHas('houseResults', fn ($rows) => $rows->count() === 8 && $rows->first()->id === $first->id)
        ->call('selectVisibleHouses')
        ->assertSet('house_ids', fn ($ids) => count($ids) === 8)
        ->set('housePage', 63)
        ->assertViewHas('houseResults', fn ($rows) => $rows->count() === 4)
        ->set('houseSearch', 'B500')
        ->assertSet('housePage', 1)
        ->assertViewHas('houseResults', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $last->id)
        ->call('selectVisibleHouses')
        ->assertSet('house_ids', fn ($ids) => count($ids) === 9 && in_array($first->id, $ids) && in_array($last->id, $ids))
        ->set('houseSearch', '')
        ->set('houseCluster', (string) $clusterB->id)
        ->assertViewHas('houseResultCount', 250)
        ->set('selectedHousesOnly', true)
        ->assertViewHas('houseResultCount', 1)
        ->call('toggleHouse', $last->id)
        ->assertViewHas('houseResultCount', 0)
        ->assertSet('house_ids', fn ($ids) => count($ids) === 8)
        ->set('houseCluster', 'all')
        ->assertViewHas('houseResultCount', 8)
        ->call('clearHouses')
        ->assertSet('house_ids', [])
        ->assertViewHas('houseResultCount', 0);

    $component->set('selectedHousesOnly', false)->set('houseSearch', 'no matching house')
        ->assertSee('Tidak ada rumah yang cocok');
});

test('transaction pickers show the actual cluster and warehouse context', function () {
    $warehouse = Warehouse::create(['name' => 'Gudang Timur']);
    $cluster = Cluster::create(['name' => 'Cluster Mawar']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $category = Category::factory()->material()->create();
    $supplier = Supplier::factory()->create();

    House::factory()->create(['cluster_id' => $cluster->id, 'status' => 'pembangunan']);
    Material::factory()->create([
        'warehouse_id' => $warehouse->id,
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'stock' => 10,
    ]);
    Tool::factory()->create([
        'warehouse_id' => $warehouse->id,
        'available_qty' => 2,
        'total_qty' => 2,
    ]);

    Livewire::test(TransaksiLogistik::class)
        ->assertSee('Cluster Mawar')
        ->assertSee('Gudang Timur')
        ->assertViewHas('houseClusters', fn ($clusters) => $clusters->contains(fn ($item) => $item['name'] === 'Cluster Mawar'));
});

test('return picker includes completed houses with loans and excludes returned or voided loans', function () {
    $cluster = Cluster::create(['name' => 'Cluster Pengembalian']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $completed = House::factory()->completed()->create(['cluster_id' => $cluster->id]);
    $empty = House::factory()->create(['cluster_id' => $cluster->id]);
    $returned = House::factory()->create(['cluster_id' => $cluster->id]);
    $voided = House::factory()->create(['cluster_id' => $cluster->id]);
    $usage = ToolUsage::factory()->create(['house_id' => $completed->id, 'quantity' => 1, 'return_date' => null]);
    ToolUsage::factory()->create(['house_id' => $returned->id, 'return_date' => now()]);
    ToolUsage::factory()->create(['house_id' => $voided->id, 'return_date' => null, 'voided_at' => now()]);

    Livewire::test(TransaksiLogistik::class)
        ->call('toggleHouse', $completed->id)
        ->assertSet('house_ids', [$completed->id])
        ->set('activeTab', 'allocation')
        ->assertSet('house_ids', [])
        ->set('house_ids', [$empty->id])
        ->set('activeTab', 'return')
        ->assertSet('house_ids', [])
        ->assertViewHas('houseResults', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $completed->id)
        ->call('toggleHouse', $completed->id)
        ->assertViewHas('activeUsages', fn ($rows) => $rows->count() === 1)
        ->set('activeTab', 'allocation')
        ->assertSet('house_ids', [])
        ->set('activeTab', 'return')
        ->call('toggleHouse', $completed->id)
        ->set('returnSelections.'.$usage->id.'.selected', true)
        ->set('returnSelections.'.$usage->id.'.qty_normal', 1)
        ->call('showReturnConfirmationModal')
        ->call('saveReturn')
        ->assertHasNoErrors()
        ->assertSet('house_ids', [])
        ->assertViewHas('houseResultCount', 0);
});

test('allocation picker includes completed houses while their warranty is active', function () {
    $cluster = Cluster::create(['name' => 'Cluster Garansi']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $underWarranty = House::factory()->completed()->create([
        'cluster_id' => $cluster->id,
        'warranty_expires_at' => now()->addYear(),
    ]);
    House::factory()->completed()->create([
        'cluster_id' => $cluster->id,
        'warranty_expires_at' => now()->subSecond(),
    ]);

    Livewire::test(TransaksiLogistik::class)
        ->set('activeTab', 'allocation')
        ->assertViewHas('houseResults', fn ($rows) => $rows->pluck('id')->all() === [$underWarranty->id])
        ->assertViewHas('houseResultCount', 1);
});

test('empty taker suggestions explain that a name can be typed', function () {
    $warehouse = Warehouse::create(['name' => 'Gudang Pengujian']);
    $cluster = Cluster::create(['name' => 'Cluster Pengujian']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $house = House::factory()->create(['cluster_id' => $cluster->id]);
    $category = Category::factory()->material()->create();
    $supplier = Supplier::factory()->create();
    $material = Material::factory()->create([
        'warehouse_id' => $warehouse->id,
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'stock' => 5,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
        'remaining_quantity' => 5,
        'unit_price' => 1000,
        'total_cost' => 5000,
        'date' => now()->toDateString(),
    ]);

    Livewire::test(TransaksiLogistik::class)
        ->set('spreadsheetRows', [[
            'house_id' => (string) $house->id,
            'materials' => [[
                'material_id' => (string) $material->id,
                'batch_id' => (string) $batch->id,
                'quantity' => 1,
                'notes' => 'Pengujian alokasi',
            ]],
            'tools' => [],
            'returns' => [],
        ]])
        ->assertSee('Pilih atau ketik pengambil')
        ->assertSee('Belum ada riwayat nama pengambil. Ketik nama di kolom ini untuk mengisi.');
});

test('spreadsheet allocation previews selected houses and requires final confirmation before saving', function () {
    $warehouse = Warehouse::create(['name' => 'Gudang Uji Alokasi']);
    $cluster = Cluster::create(['name' => 'Cluster Uji Alokasi']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $category = Category::factory()->material()->create();
    $supplier = Supplier::factory()->create(['name' => 'Supplier Uji Alokasi']);
    $houses = House::factory()->count(2)->create(['cluster_id' => $cluster->id]);
    $material = Material::factory()->create([
        'warehouse_id' => $warehouse->id,
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'stock' => 20,
        'unit_price' => 125000,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 20,
        'remaining_quantity' => 20,
        'unit_price' => 125000,
        'total_cost' => 2500000,
        'date' => now()->toDateString(),
    ]);

    $rows = $houses->map(fn ($house) => [
        'house_id' => (string) $house->id,
        'materials' => [[
            'material_id' => (string) $material->id,
            'batch_id' => (string) $batch->id,
            'quantity' => 2,
            'notes' => 'Alokasi uji lot material',
        ]],
        'tools' => [],
        'returns' => [],
    ])->all();

    Livewire::test(TransaksiLogistik::class)
        ->set('spreadsheetRows', $rows)
        ->call('reviewSpreadsheet')
        ->assertSet('showSpreadsheetReview', true)
        ->assertSet('spreadsheetReviewTotals.materials', 2)
        ->assertSet('spreadsheetReviewRows', fn ($preview) => count($preview) === 2
            && str_contains($preview[0]['details'][0], $batch->entry_code)
            && str_contains($preview[0]['details'][0], 'Rp 125.000/')
            && str_contains($preview[0]['details'][0], 'subtotal Rp 250.000')
            && $preview[0]['material_total'] === 250000.0
            && $preview[0]['material_count'] === 1)
        ->call('saveSpreadsheet')
        ->assertSet('showSpreadsheetReview', true)
        ->assertSet('showSpreadsheetConfirmation', false)
        ->assertSet('spreadsheetReviewTotals.materials', 2)
        ->call('confirmSpreadsheetReview')
        ->assertSet('showSpreadsheetReview', false)
        ->assertSet('showSpreadsheetConfirmation', true)
        ->call('saveSpreadsheet')
        ->assertHasNoErrors()
        ->assertSet('showSpreadsheetConfirmation', false);

    expect((float) $batch->fresh()->remaining_quantity)->toBe(16.0);
    expect((float) $material->fresh()->stock)->toBe(16.0);
    $this->assertDatabaseCount('material_usages', 2);
    $this->assertDatabaseHas('material_usages', [
        'house_id' => $houses->first()->id,
        'material_id' => $material->id,
        'quantity' => 2,
    ]);

    $this->get(route('logistik.alokasi'))
        ->assertOk()
        ->assertSee('Buat alokasi', false)
        ->assertSee('lapangan', false)
        ->assertSee('Alokasi Material &amp; Alat', false);

    $this->get(route('logistik.transaksi'))
        ->assertOk()
        ->assertSee('Buat alokasi', false)
        ->assertSee('lapangan', false);
});

test('spreadsheet allocation dispatches tools and records returns after confirmation', function () {
    $warehouse = Warehouse::create(['name' => 'Gudang Uji Alat']);
    $cluster = Cluster::create(['name' => 'Cluster Uji Alat']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $houses = House::factory()->count(2)->create(['cluster_id' => $cluster->id]);
    $dispatchTool = Tool::factory()->create([
        'warehouse_id' => $warehouse->id,
        'total_qty' => 5,
        'available_qty' => 5,
        'qty_broken' => 0,
    ]);
    $returnTool = Tool::factory()->create([
        'warehouse_id' => $warehouse->id,
        'total_qty' => 3,
        'available_qty' => 0,
        'qty_broken' => 0,
    ]);
    $usage = ToolUsage::factory()->create([
        'house_id' => $houses[1]->id,
        'tool_id' => $returnTool->id,
        'user_id' => auth()->id(),
        'quantity' => 3,
        'return_date' => null,
    ]);

    $rows = [
        [
            'house_id' => (string) $houses[0]->id,
            'materials' => [],
            'tools' => [[
                'tool_id' => (string) $dispatchTool->id,
                'warehouse_id' => (string) $warehouse->id,
                'quantity' => 2,
                'notes' => 'Pekerjaan uji alat',
            ]],
            'returns' => [],
        ],
        [
            'house_id' => (string) $houses[1]->id,
            'materials' => [],
            'tools' => [],
            'returns' => [[
                'usage_id' => (string) $usage->id,
                'qty_normal' => 2,
                'qty_broken' => 1,
                'qty_lost' => 0,
                'receiving_warehouse_id' => (string) $warehouse->id,
                'notes' => 'Pengembalian uji alat',
            ]],
        ],
    ];

    Livewire::test(TransaksiLogistik::class)
        ->set('spreadsheetRows', $rows)
        ->call('reviewSpreadsheet')
        ->assertSet('showSpreadsheetReview', true)
        ->assertSet('spreadsheetReviewTotals.tools', 1)
        ->assertSet('spreadsheetReviewTotals.returns', 1)
        ->call('saveSpreadsheet')
        ->assertSet('showSpreadsheetReview', true)
        ->assertSet('showSpreadsheetConfirmation', false)
        ->call('confirmSpreadsheetReview')
        ->call('saveSpreadsheet')
        ->assertHasNoErrors()
        ->assertSet('showSpreadsheetConfirmation', false);

    expect((int) ToolWarehouseBalance::where('tool_id', $dispatchTool->id)->where('warehouse_id', $warehouse->id)->value('available_qty'))->toBe(3);
    expect((int) ToolWarehouseBalance::where('tool_id', $returnTool->id)->where('warehouse_id', $warehouse->id)->value('available_qty'))->toBe(2);
    expect((int) ToolWarehouseBalance::where('tool_id', $returnTool->id)->where('warehouse_id', $warehouse->id)->value('qty_broken'))->toBe(1);
    expect($usage->fresh()->return_date)->not->toBeNull();
    expect(ToolReturnLog::where('tool_usage_id', $usage->id)->count())->toBe(2);
    $this->assertDatabaseHas('tool_usages', [
        'house_id' => $houses[0]->id,
        'tool_id' => $dispatchTool->id,
        'quantity' => 2,
        'return_date' => null,
    ]);
});

test('spreadsheet allocation shows useful errors beside missing material fields', function () {
    $warehouse = Warehouse::create(['name' => 'Gudang Uji Validasi Alokasi']);
    $cluster = Cluster::create(['name' => 'Cluster Uji Validasi Alokasi']);
    $this->actingAs(User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]));
    $category = Category::factory()->material()->create();
    $supplier = Supplier::factory()->create();
    $house = House::factory()->create(['cluster_id' => $cluster->id]);
    $material = Material::factory()->create([
        'warehouse_id' => $warehouse->id,
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'stock' => 20,
    ]);
    $batch = StockIn::create([
        'material_id' => $material->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 20,
        'remaining_quantity' => 20,
        'unit_price' => 125000,
        'total_cost' => 2500000,
        'date' => now()->toDateString(),
    ]);

    Livewire::test(TransaksiLogistik::class)
        ->set('spreadsheetRows', [[
            'house_id' => (string) $house->id,
            'materials' => [[
                'material_id' => (string) $material->id,
                'batch_id' => '',
                'quantity' => 1,
                'notes' => '',
            ]],
            'tools' => [],
            'returns' => [],
        ]])
        ->call('reviewSpreadsheet')
        ->assertHasErrors([
            'spreadsheetRows.0.materials.0.batch_id',
            'spreadsheetRows.0.materials.0.notes',
        ])
        ->assertSee('Pilih batch material.')
        ->assertSee('Isi peruntukan material.');

    expect((float) $batch->fresh()->remaining_quantity)->toBe(20.0)
        ->and((float) $material->fresh()->stock)->toBe(20.0);
});

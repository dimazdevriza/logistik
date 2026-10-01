<?php

namespace Tests\Feature;

use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\Materials;
use App\Models\Category;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialReceiptIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'logistik']));
    }

    public function test_new_material_arrival_gets_one_receipt_and_replayed_submission_is_ignored(): void
    {
        $category = Category::factory()->material()->create();
        $warehouse = Warehouse::query()->firstOrCreate(['name' => 'AUDIT-RECEIPT-WH']);
        $name = 'AUDIT-RECEIPT-'.bin2hex(random_bytes(4));
        $form = Livewire::test(Materials::class)->call('create');
        $submissionKey = $form->get('receiptSubmissionKey');

        $form->set('name', $name)
            ->set('category_id', $category->id)
            ->set('unit', 'sak')
            ->set('unit_price', 125000)
            ->set('stock', 2)
            ->set('warehouse_id', $warehouse->id)
            ->set('receiptReceivedAt', '2026-09-24T10:15')
            ->set('receiptSubmissionKey', $submissionKey)
            ->call('save')
            ->assertHasNoErrors();

        $receipt = StockIn::where('submission_key', $submissionKey)->sole();
        $material = Material::where('name', $name)->sole();
        $this->assertSame('MSK-', substr($receipt->entry_code, 0, 4));
        $this->assertEquals(2, $receipt->remaining_quantity);
        $this->assertSame('2026-09-24 10:15', $receipt->received_at->format('Y-m-d H:i'));
        $this->assertEquals(2, $material->stock);

        $form->set('name', $name)
            ->set('category_id', $category->id)
            ->set('unit', 'sak')
            ->set('unit_price', 125000)
            ->set('stock', 2)
            ->set('warehouse_id', $warehouse->id)
            ->set('receiptReceivedAt', '2026-09-24T10:15')
            ->set('receiptSubmissionKey', $submissionKey)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, StockIn::where('submission_key', $submissionKey)->count());
        $this->assertSame(1, Material::where('name', $name)->count());
        $this->assertEquals(2, $material->fresh()->stock);
    }

    public function test_identical_restocks_keep_distinct_codes_and_log_search_finds_each_receipt(): void
    {
        $material = Material::factory()->create([
            'name' => 'AUDIT-RECEIPT-'.bin2hex(random_bytes(4)),
            'unit' => 'sak',
            'unit_price' => 125000,
            'stock' => 0,
        ]);

        $form = Livewire::test(Materials::class)->call('restock', $material->id);
        $firstKey = $form->get('restockSubmissionKey');
        $form->set('restockQuantity', 3)
            ->set('restockUnitPrice', 125000)
            ->set('restockReceivedAt', '2026-09-24T11:00')
            ->call('saveRestock')
            ->assertHasNoErrors();

        $form->set('restockMaterialId', $material->id)
            ->set('restockSubmissionKey', $firstKey)
            ->set('restockQuantity', 3)
            ->set('restockUnitPrice', 125000)
            ->set('restockReceivedAt', '2026-09-24T11:00')
            ->call('saveRestock')
            ->assertHasNoErrors();

        $form->call('restock', $material->id)
            ->set('restockQuantity', 3)
            ->set('restockUnitPrice', 125000)
            ->set('restockReceivedAt', '2026-09-24T11:00')
            ->call('saveRestock')
            ->assertHasNoErrors();

        $receipts = StockIn::where('material_id', $material->id)->orderBy('id')->get();
        $this->assertCount(2, $receipts);
        $this->assertCount(2, $receipts->pluck('entry_code')->unique());
        $this->assertSame([3.0, 3.0], $receipts->map(fn (StockIn $receipt) => (float) $receipt->remaining_quantity)->all());
        $this->assertEquals(6, $material->fresh()->stock);

        Livewire::test(MaterialLog::class)
            ->set('filterType', 'masuk')
            ->set('search', $receipts[0]->entry_code)
            ->assertViewHas('records', fn ($records) => $records->total() === 1
                && $records->first()->transaction_code === $receipts[0]->entry_code);
    }

    public function test_existing_material_choice_keeps_its_code_when_the_new_batch_has_another_price_and_supplier(): void
    {
        $material = Material::factory()->create([
            'name' => 'AUDIT-RECEIPT-'.bin2hex(random_bytes(4)),
            'stock' => 5,
            'unit_price' => 125000,
        ]);
        $originalCode = $material->code;
        $supplierName = 'AUDIT-SUPPLIER-'.bin2hex(random_bytes(4));

        Livewire::test(Materials::class)
            ->call('create')
            ->call('setCreateMode', 'existing')
            ->set('existingMaterialId', $material->id)
            ->assertSet('name', $material->name)
            ->set('stock', 3)
            ->set('unit_price', 130000)
            ->set('supplier_name', $supplierName)
            ->set('receiptReceivedAt', '2026-09-24T11:00')
            ->call('save')
            ->assertHasNoErrors();

        $receipt = StockIn::where('material_id', $material->id)->sole();
        $this->assertSame(1, Material::where('name', $material->name)->count());
        $this->assertSame($originalCode, $material->fresh()->code);
        $this->assertEquals(8, $material->fresh()->stock);
        $this->assertEquals(130000, $receipt->unit_price);
        $this->assertEquals(3, $receipt->remaining_quantity);
        $this->assertSame($supplierName, Supplier::findOrFail($receipt->supplier_id)->name);
    }
}

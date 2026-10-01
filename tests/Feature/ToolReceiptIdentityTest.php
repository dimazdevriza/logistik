<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Tools;
use App\Models\Category;
use App\Models\Tool;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToolReceiptIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'logistik']));
    }

    public function test_manual_tool_receipt_keeps_batch_code_time_and_recorder(): void
    {
        $category = Category::factory()->tool()->create();
        $warehouse = Warehouse::query()->firstOrCreate(['name' => 'AUDIT-TOOL-RECEIPT-WH']);
        $name = 'AUDIT Tool Receipt '.bin2hex(random_bytes(4));
        $form = Livewire::test(Tools::class)->call('create');
        $submissionKey = $form->get('submissionKey');

        $form->set('name', $name)
            ->set('category_id', $category->id)
            ->set('code', 'AUD-TOOL-'.strtoupper(bin2hex(random_bytes(3))))
            ->set('total_qty', 2)
            ->set('available_qty', 2)
            ->set('warehouse_id', $warehouse->id)
            ->set('receivedAt', '2026-09-24T10:15')
            ->set('submissionKey', $submissionKey)
            ->call('save')
            ->assertHasNoErrors();

        $tool = Tool::where('submission_key', $submissionKey)->sole();
        $this->assertStringStartsWith('ALT-MSK-', $tool->entry_code);
        $this->assertSame('2026-09-24 10:15', $tool->received_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-24', $tool->received_date->format('Y-m-d'));
        $this->assertSame(auth()->id(), $tool->recorded_by_id);
        $this->assertSame(2, $tool->available_qty);

        $form->call('create')
            ->set('name', $name)
            ->set('category_id', $category->id)
            ->set('code', $tool->code)
            ->set('total_qty', 2)
            ->set('available_qty', 2)
            ->set('warehouse_id', $warehouse->id)
            ->set('receivedAt', '2026-09-24T10:15')
            ->set('submissionKey', $submissionKey)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Tool::where('submission_key', $submissionKey)->count());
    }
}

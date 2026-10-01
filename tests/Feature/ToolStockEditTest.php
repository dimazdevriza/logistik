<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Tools;
use App\Models\{Tool, ToolUsage, House, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToolStockEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_counts_active_loans_and_preserves_history(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $tool = Tool::factory()->create(['total_qty' => 5, 'available_qty' => 2, 'qty_broken' => 0]);
        $house = House::factory()->create();
        foreach (['active', 'returned', 'voided'] as $status) {
            ToolUsage::create([
                'tool_id' => $tool->id, 'house_id' => $house->id, 'user_id' => $user->id,
                'quantity' => 3, 'checkout_date' => '2026-09-14',
                'return_date' => $status === 'returned' ? '2026-09-14' : null,
                'voided_at' => $status === 'voided' ? now() : null,
            ]);
        }
        $before = $tool->fresh()->getRawOriginal();
        $history = $tool->usages()->get()->toArray();
        foreach ([['available_qty', 5], ['total_qty', 4], ['qty_broken', 1]] as [$field, $value]) {
            Livewire::test(Tools::class)->call('edit', $tool->id)->set($field, $value)->call('save')
                ->assertHasErrors('available_qty')->assertSet('showModal', true)->assertSee('dipinjam (3)')
                ->assertSee('3 unit sedang dipinjam');
            $this->assertSame($before, $tool->fresh()->getRawOriginal());
        }
        Livewire::test(Tools::class)->call('edit', $tool->id)->set('name', 'Renamed tool')->call('save')->assertHasNoErrors();
        $this->assertSame(2, $tool->fresh()->available_qty);
        Livewire::test(Tools::class)->call('edit', $tool->id)->set('total_qty', 6)->set('available_qty', 3)->call('save')->assertHasNoErrors();
        $this->assertSame(3, $tool->fresh()->available_qty);
        $this->assertSame($history, $tool->usages()->get()->toArray());
    }
}

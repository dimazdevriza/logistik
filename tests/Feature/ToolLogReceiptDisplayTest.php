<?php

namespace Tests\Feature;

use App\Livewire\Logistik\ToolLog;
use App\Models\House;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToolLogReceiptDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_receipts_and_loans_share_one_table(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'name' => 'Admin Gudang']);
        $this->actingAs($user);
        $warehouse = Warehouse::create(['name' => 'Receipt history warehouse']);
        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'TOOL-RECEIPT-001',
            'entry_code' => 'ALT-RCV-TEST-001',
            'entry_type' => 'receipt',
            'received_at' => now()->subDay(),
            'received_date' => today()->subDay(),
            'recorded_by_id' => $user->id,
        ]);
        $house = House::factory()->create();
        $usage = ToolUsage::factory()->create([
            'house_id' => $house->id,
            'tool_id' => $tool->id,
            'user_id' => $user->id,
            'transaction_code' => 'ALT-LOAN-TEST-001',
            'checkout_date' => today(),
            'notes' => 'Tool loan test',
        ]);

        Livewire::test(ToolLog::class)
            ->assertSee('Rumah / Gudang')
            ->assertSee('Masuk')
            ->assertSee('Dipinjam')
            ->assertSeeInOrder([$usage->transaction_code, $tool->entry_code])
            ->assertSee($tool->name)
            ->assertSee($warehouse->name)
            ->assertSee($house->name)
            ->assertSeeInOrder([$user->name, 'Admin'])
            ->assertDontSee('Tiba');
    }
}

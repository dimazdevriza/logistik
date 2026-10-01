<?php

namespace Tests\Feature;

use App\Livewire\Logistik\Dispatches;
use App\Models\DispatchReceipt;
use App\Models\DispatchReceiptLine;
use App\Models\DispatchResolutionEvent;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DispatchQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_queues_history_and_search_match_the_request_workflow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $logistik = User::factory()->create(['role' => 'logistik', 'name' => 'TEST Queue Logistik']);
        $house = House::factory()->create(['name' => 'TEST Blok Q-01']);
        $material = Material::factory()->create(['name' => 'TEST Semen Queue']);
        $this->actingAs($admin);

        $makeRequest = function (string $code, string $status, array $attributes = []) use ($logistik, $house, $material): MaterialToolRequest {
            return MaterialToolRequest::create(array_merge([
                'request_code' => $code,
                'requester_id' => $logistik->id,
                'house_id' => $house->id,
                'type' => 'material',
                'material_id' => $material->id,
                'quantity' => 2,
                'status' => $status,
            ], $attributes));
        };

        $pending = $makeRequest('TEST-QUEUE-PENDING', 'pending');
        $dispatched = $makeRequest('TEST-QUEUE-DISPATCHED', 'dispatched', [
            'dispatch_code' => 'TEST-DSP-QUEUE-01',
            'dispatched_at' => now(),
        ]);
        $partial = $makeRequest('TEST-QUEUE-PARTIAL', 'partially_arrived');
        $awaitingReturn = $makeRequest('TEST-QUEUE-RETURN', 'rejected', ['dispatched_at' => now()]);
        $cleanArrival = $makeRequest('TEST-QUEUE-ARRIVED', 'arrived');
        $damagedArrival = $makeRequest('TEST-QUEUE-DAMAGE', 'arrived');
        $settledDamage = $makeRequest('TEST-QUEUE-SETTLED-DAMAGE', 'resolved');

        foreach ([$damagedArrival, $settledDamage] as $request) {
            $receipt = DispatchReceipt::create([
                'material_tool_request_id' => $request->id,
                'received_by_id' => $admin->id,
                'event_type' => 'confirmation',
                'submission_key' => (string) Str::uuid(),
                'received_at' => now(),
            ]);

            $receiptLine = DispatchReceiptLine::create([
                'dispatch_receipt_id' => $receipt->id,
                'received_quantity' => 2,
                'damaged_quantity' => 1,
            ]);

            if ($request === $settledDamage) {
                DispatchResolutionEvent::create([
                    'material_tool_request_id' => $request->id,
                    'dispatch_receipt_line_id' => $receiptLine->id,
                    'event_type' => 'dispose_damaged',
                    'submission_key' => (string) Str::uuid(),
                    'quantity' => 1,
                    'notes' => 'TEST damage settled',
                    'recorded_at' => now(),
                ]);
            }
        }

        $searchOnly = MaterialToolRequest::create([
            'request_code' => 'TEST-QUEUE-SEARCH',
            'requester_id' => User::factory()->create(['role' => 'logistik', 'name' => 'TEST Search Only Logistik'])->id,
            'house_id' => House::factory()->create(['name' => 'TEST Search Only House'])->id,
            'type' => 'material',
            'material_id' => $material->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $queue = Livewire::test(Dispatches::class)
            ->assertSet('queueView', 'transit')
            ->assertSee($dispatched->request_code)
            ->assertSee($partial->request_code)
            ->assertSee($awaitingReturn->request_code)
            ->assertSee($damagedArrival->request_code)
            ->assertDontSee($pending->request_code)
            ->call('showQueue', 'history')
            ->assertSee($pending->request_code)
            ->assertSee($cleanArrival->request_code)
            ->assertSee($settledDamage->request_code)
            ->assertDontSee($damagedArrival->request_code)
            ->call('showQueue', 'transit')
            ->assertSee($dispatched->request_code)
            ->assertSee($partial->request_code)
            ->assertSee($awaitingReturn->request_code)
            ->assertSee($damagedArrival->request_code)
            ->assertDontSee($pending->request_code)
            ->assertDontSee($settledDamage->request_code)
            ->call('showQueue', 'history')
            ->set('search', 'TEST Search Only Logistik')
            ->assertSee($searchOnly->request_code)
            ->assertDontSee($pending->request_code)
            ->set('search', 'TEST Blok Q-01')
            ->assertSee($pending->request_code)
            ->set('search', 'TEST Semen Queue')
            ->assertSee($pending->request_code)
            ->set('search', 'TEST-QUEUE-PENDING')
            ->assertSee($pending->request_code)
            ->call('showQueue', 'transit')
            ->set('search', 'TEST-DSP-QUEUE-01')
            ->assertSee($dispatched->request_code)
            ->assertDontSee($partial->request_code);
    }
}

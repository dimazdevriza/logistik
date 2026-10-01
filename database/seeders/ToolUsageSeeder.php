<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ToolInventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolUsageSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('role', 'logistik')->value('id');
        $warehouseId = Warehouse::where('name', 'Gudang Utama')->value('id');
        if (! $userId || ! $warehouseId) return;

        foreach ([
            ['Blok A-01', 'AB-101', 1, 7, 'Pengecoran kolom utama'],
            ['Blok A-02', 'AB-102', 1, 5, 'Pemadatan tanah dasar'],
            ['Blok B-01', 'AT-101', 2, 3, 'Pemotongan besi tulangan'],
            ['Blok B-02', 'AT-109', 1, 2, 'Pemotongan keramik teras'],
            ['Blok A-03', 'AU-101', 1, 1, 'Pengukuran elevasi lantai'],
        ] as [$houseName, $toolCode, $quantity, $daysAgo, $notes]) {
            $house = House::where('name', $houseName)->first();
            $tool = Tool::where('code', $toolCode)->first();
            if (! $house || ! $tool) continue;

            DB::transaction(function () use ($house, $tool, $quantity, $daysAgo, $notes, $userId, $warehouseId): void {
                ToolInventory::checkout($tool, $warehouseId, $quantity);
                $date = now()->subDays($daysAgo)->toDateString();
                ToolUsage::create([
                    'transaction_code' => 'KLR-'.Str::ulid(),
                    'house_id' => $house->id,
                    'tool_id' => $tool->id,
                    'warehouse_id' => $warehouseId,
                    'warehouse_source_recorded' => true,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'checkout_date' => $date,
                    'return_date' => null,
                    'notes' => $notes,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            });
        }
    }
}

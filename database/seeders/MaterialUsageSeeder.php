<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialUsageSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('role', 'logistik')->value('id');
        $entries = [
            ['Blok A-01', 'MAT-0001', 35, 10, 'Pengecoran sloof dan kolom'],
            ['Blok A-02', 'MAT-0010', 48, 8, 'Pemasangan tulangan pondasi'],
            ['Blok B-01', 'MAT-0027', 4.5, 6, 'Pekerjaan pasangan bata dan plester'],
            ['Blok B-02', 'MAT-0001', 22, 3, 'Pengecoran lantai kerja'],
            ['Blok A-03', 'MAT-0010', 32, 2, 'Perakitan tulangan balok'],
        ];

        foreach ($entries as [$houseName, $materialCode, $quantity, $daysAgo, $notes]) {
            $house = House::where('name', $houseName)->first();
            $material = Material::where('code', $materialCode)->first();
            if (! $house || ! $material || ! $userId) continue;

            DB::transaction(function () use ($house, $material, $quantity, $daysAgo, $notes, $userId): void {
                $batch = StockIn::where('material_id', $material->id)
                    ->where('entry_type', 'receipt')->where('remaining_quantity', '>=', $quantity)
                    ->orderBy('received_at')->lockForUpdate()->first();
                if (! $batch) return;

                $date = now()->subDays($daysAgo)->toDateString();
                $cost = (float) $batch->unit_price * $quantity;
                MaterialUsage::create([
                    'transaction_code' => 'KLR-'.Str::ulid(),
                    'house_id' => $house->id,
                    'material_id' => $material->id,
                    'stock_in_id' => $batch->id,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'unit_price_at_usage' => $batch->unit_price,
                    'total_cost' => $cost,
                    'usage_date' => $date,
                    'notes' => $notes,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
                $batch->decrement('remaining_quantity', $quantity);
                $material->decrement('stock', $quantity);
            });
        }
    }
}

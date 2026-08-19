<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialUsageSeeder extends Seeder
{
    public function run(): void
    {
        $houses = House::all();
        $materials = Material::all();
        $user = User::where('role', 'logistik')->first() ?? User::first();

        if ($houses->isEmpty() || $materials->isEmpty()) {
            return;
        }

        $sampleUsages = [
            ['house' => $houses[0] ?? null, 'material' => $materials->where('name', 'Baja Ringan C75')->first() ?? $materials[0], 'qty' => 20, 'notes' => 'Pemasangan Rangka Atap Utama'],
            ['house' => $houses[0] ?? null, 'material' => $materials->where('name', 'Semen Portland 50kg')->first() ?? $materials[1], 'qty' => 15, 'notes' => 'Pekerjaan Cor Pondasi'],
            ['house' => $houses[1] ?? null, 'material' => $materials->where('name', 'Bata Ringan / Hebel')->first() ?? $materials[2], 'qty' => 120, 'notes' => 'Pemasangan Dinding Lantai 1'],
            ['house' => $houses[1] ?? null, 'material' => $materials->where('name', 'Pasir Pasang')->first() ?? $materials[3], 'qty' => 5, 'notes' => 'Plesteran Dinding Depan'],
            ['house' => $houses[2] ?? null, 'material' => $materials->where('name', 'Besi Beton 10mm')->first() ?? $materials[4], 'qty' => 30, 'notes' => 'Perakitan Kolom Struktur'],
        ];

        foreach ($sampleUsages as $item) {
            if (!$item['house'] || !$item['material']) continue;

            $unitPrice = $item['material']->unit_price ?? 50000;
            $totalCost = $unitPrice * $item['qty'];

            MaterialUsage::create([
                'house_id' => $item['house']->id,
                'material_id' => $item['material']->id,
                'user_id' => $user->id ?? 1,
                'quantity' => $item['qty'],
                'unit_price_at_usage' => $unitPrice,
                'total_cost' => $totalCost,
                'usage_date' => now()->subDays(rand(1, 10)),
                'notes' => $item['notes'],
            ]);
        }
    }
}

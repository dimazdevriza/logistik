<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            // Alat Berat (category 10)
            ['category_id' => 10, 'name' => 'Molen Beton', 'code' => 'AB-001', 'condition' => 'baik', 'purchase_price' => 15000000, 'total_qty' => 2, 'available_qty' => 2],
            ['category_id' => 10, 'name' => 'Stamper/Mesin Pemadat', 'code' => 'AB-002', 'condition' => 'baik', 'purchase_price' => 8000000, 'total_qty' => 3, 'available_qty' => 3],
            ['category_id' => 10, 'name' => 'Bar Cutter', 'code' => 'AB-003', 'condition' => 'baik', 'purchase_price' => 5500000, 'total_qty' => 2, 'available_qty' => 2],
            ['category_id' => 10, 'name' => 'Bar Bender', 'code' => 'AB-004', 'condition' => 'baik', 'purchase_price' => 6000000, 'total_qty' => 1, 'available_qty' => 1],

            // Alat Tangan (category 11)
            ['category_id' => 11, 'name' => 'Mesin Bor Beton', 'code' => 'AT-001', 'condition' => 'baik', 'purchase_price' => 1200000, 'total_qty' => 5, 'available_qty' => 5],
            ['category_id' => 11, 'name' => 'Gerinda Potong', 'code' => 'AT-002', 'condition' => 'baik', 'purchase_price' => 850000, 'total_qty' => 4, 'available_qty' => 4],
            ['category_id' => 11, 'name' => 'Gergaji Circular', 'code' => 'AT-003', 'condition' => 'baik', 'purchase_price' => 1500000, 'total_qty' => 3, 'available_qty' => 3],
            ['category_id' => 11, 'name' => 'Mesin Las', 'code' => 'AT-004', 'condition' => 'baik', 'purchase_price' => 3500000, 'total_qty' => 2, 'available_qty' => 2],
            ['category_id' => 11, 'name' => 'Cangkul', 'code' => 'AT-005', 'condition' => 'baik', 'purchase_price' => 75000, 'total_qty' => 10, 'available_qty' => 10],
            ['category_id' => 11, 'name' => 'Sekop', 'code' => 'AT-006', 'condition' => 'baik', 'purchase_price' => 65000, 'total_qty' => 10, 'available_qty' => 10],

            // Alat Ukur (category 12)
            ['category_id' => 12, 'name' => 'Theodolite', 'code' => 'AU-001', 'condition' => 'baik', 'purchase_price' => 12000000, 'total_qty' => 1, 'available_qty' => 1],
            ['category_id' => 12, 'name' => 'Waterpass Digital', 'code' => 'AU-002', 'condition' => 'baik', 'purchase_price' => 350000, 'total_qty' => 5, 'available_qty' => 5],
            ['category_id' => 12, 'name' => 'Meteran 50m', 'code' => 'AU-003', 'condition' => 'baik', 'purchase_price' => 85000, 'total_qty' => 8, 'available_qty' => 8],

            // Alat Keselamatan (category 13)
            ['category_id' => 13, 'name' => 'Helm Proyek', 'code' => 'AK-001', 'condition' => 'baik', 'purchase_price' => 45000, 'total_qty' => 20, 'available_qty' => 20],
            ['category_id' => 13, 'name' => 'Rompi Safety', 'code' => 'AK-002', 'condition' => 'baik', 'purchase_price' => 35000, 'total_qty' => 20, 'available_qty' => 20],
            ['category_id' => 13, 'name' => 'Sepatu Safety', 'code' => 'AK-003', 'condition' => 'baik', 'purchase_price' => 250000, 'total_qty' => 15, 'available_qty' => 15],
        ];

        foreach ($tools as $index => $tool) {
            Tool::create($tool);
        }
    }
}

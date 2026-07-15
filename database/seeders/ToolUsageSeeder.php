<?php

namespace Database\Seeders;

use App\Models\Tool;
use App\Models\ToolUsage;
use Illuminate\Database\Seeder;

class ToolUsageSeeder extends Seeder
{
    public function run(): void
    {
        $usages = [
            // Blok A-01 (house_id 1) - pembangunan
            ['house_id' => 1, 'tool_id' => 1,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-02-01', 'return_date' => null, 'notes' => 'Pengecoran pondasi utama'],
            ['house_id' => 1, 'tool_id' => 2,  'user_id' => 2, 'quantity' => 1, 'checkout_date' => '2026-01-20', 'return_date' => null, 'notes' => 'Pemadatan tanah dasar'],
            ['house_id' => 1, 'tool_id' => 3,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-02-15', 'return_date' => null, 'notes' => 'Pemotongan besi kolom'],
            ['house_id' => 1, 'tool_id' => 6,  'user_id' => 2, 'quantity' => 1, 'checkout_date' => '2026-02-20', 'return_date' => null, 'notes' => 'Pemotongan keramik teras'],
            ['house_id' => 1, 'tool_id' => 9,  'user_id' => 2, 'quantity' => 2, 'checkout_date' => '2026-01-20', 'return_date' => null, 'notes' => 'Galian pondasi'],
            ['house_id' => 1, 'tool_id' => 10, 'user_id' => 1, 'quantity' => 2, 'checkout_date' => '2026-01-20', 'return_date' => null, 'notes' => 'Membantu pengadukan semen'],

            // Blok A-02 (house_id 2) - pembangunan
            ['house_id' => 2, 'tool_id' => 5,  'user_id' => 2, 'quantity' => 1, 'checkout_date' => '2026-03-01', 'return_date' => null, 'notes' => 'Pemasangan angkur'],
            ['house_id' => 2, 'tool_id' => 7,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-03-15', 'return_date' => null, 'notes' => 'Pemotongan papan tripleks'],
            ['house_id' => 2, 'tool_id' => 10, 'user_id' => 1, 'quantity' => 2, 'checkout_date' => '2026-02-01', 'return_date' => null, 'notes' => 'Galian septic tank'],

            // Blok B-01 (house_id 4) - pembangunan
            ['house_id' => 4, 'tool_id' => 1,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-03-05', 'return_date' => null, 'notes' => 'Pengecoran kolom'],
            ['house_id' => 4, 'tool_id' => 2,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-03-01', 'return_date' => null, 'notes' => 'Pemadatan halaman depan'],
            ['house_id' => 4, 'tool_id' => 3,  'user_id' => 2, 'quantity' => 1, 'checkout_date' => '2026-03-10', 'return_date' => null, 'notes' => 'Pemotongan besi ring balk'],
            ['house_id' => 4, 'tool_id' => 5,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-03-20', 'return_date' => null, 'notes' => 'Instalasi pipa'],
            ['house_id' => 4, 'tool_id' => 6,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-03-25', 'return_date' => null, 'notes' => 'Pemotongan hollow plafon'],
            ['house_id' => 4, 'tool_id' => 9,  'user_id' => 2, 'quantity' => 2, 'checkout_date' => '2026-02-15', 'return_date' => null, 'notes' => 'Pekerjaan perataan tanah'],
            ['house_id' => 4, 'tool_id' => 10, 'user_id' => 2, 'quantity' => 2, 'checkout_date' => '2026-02-15', 'return_date' => null, 'notes' => 'Pengurukan pasir'],

            // Blok C-02 (house_id 7) - pembangunan
            ['house_id' => 7, 'tool_id' => 7,  'user_id' => 2, 'quantity' => 1, 'checkout_date' => '2026-03-05', 'return_date' => null, 'notes' => 'Pemotongan kaso bekisting'],

            // Blok D-01 (house_id 8) - pembangunan
            ['house_id' => 8, 'tool_id' => 2,  'user_id' => 2, 'quantity' => 1, 'checkout_date' => '2026-04-10', 'return_date' => null, 'notes' => 'Pemadatan carport'],
            ['house_id' => 8, 'tool_id' => 5,  'user_id' => 1, 'quantity' => 1, 'checkout_date' => '2026-04-15', 'return_date' => null, 'notes' => 'Pemasangan kusen'],
            ['house_id' => 8, 'tool_id' => 9,  'user_id' => 1, 'quantity' => 2, 'checkout_date' => '2026-04-10', 'return_date' => null, 'notes' => 'Galian saluran air'],
        ];

        foreach ($usages as $usage) {
            ToolUsage::create($usage);

            // All are active checkouts, decrement available quantity
            $tool = Tool::find($usage['tool_id']);
            if ($tool) {
                $tool->decrement('available_qty', $usage['quantity']);
            }
        }
    }
}

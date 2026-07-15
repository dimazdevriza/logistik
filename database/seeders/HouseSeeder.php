<?php

namespace Database\Seeders;

use App\Models\House;
use Illuminate\Database\Seeder;

class HouseSeeder extends Seeder
{
    public function run(): void
    {
        $houses = [
            ['name' => 'Blok A-01', 'type' => 'Tipe 36/72', 'status' => 'pembangunan', 'start_date' => '2026-01-15', 'target_end_date' => '2026-06-15'],
            ['name' => 'Blok A-02', 'type' => 'Tipe 36/72', 'status' => 'pembangunan', 'start_date' => '2026-01-20', 'target_end_date' => '2026-06-20'],
            ['name' => 'Blok A-03', 'type' => 'Tipe 45/90', 'status' => 'perencanaan', 'start_date' => '2026-04-01', 'target_end_date' => '2026-10-01'],
            ['name' => 'Blok B-01', 'type' => 'Tipe 45/90', 'status' => 'pembangunan', 'start_date' => '2026-02-01', 'target_end_date' => '2026-08-01'],
            ['name' => 'Blok B-02', 'type' => 'Tipe 54/120', 'status' => 'perencanaan', 'start_date' => '2026-05-01', 'target_end_date' => '2026-12-01'],
            ['name' => 'Blok C-01', 'type' => 'Tipe 70/150', 'status' => 'selesai', 'start_date' => '2025-06-01', 'target_end_date' => '2026-01-01'],
            ['name' => 'Blok C-02', 'type' => 'Tipe 70/150', 'status' => 'pembangunan', 'start_date' => '2025-09-01', 'target_end_date' => '2026-04-01'],
            ['name' => 'Blok D-01', 'type' => 'Tipe 36/72', 'status' => 'pembangunan', 'start_date' => '2026-02-10', 'target_end_date' => '2026-07-10'],
        ];

        foreach ($houses as $index => $house) {
            $house['house_code'] = House::generateCode($house['name']);
            
            House::create($house);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Cluster;
use Illuminate\Database\Seeder;

class HouseSeeder extends Seeder
{
    public function run(): void
    {
        $houses = [
            ['cluster' => 'Cluster A', 'name' => 'Blok A-01', 'type' => 'Tipe 36/72', 'status' => 'pembangunan', 'start_date' => '2026-07-01', 'target_end_date' => '2027-01-31'],
            ['cluster' => 'Cluster A', 'name' => 'Blok A-02', 'type' => 'Tipe 36/72', 'status' => 'pembangunan', 'start_date' => '2026-07-10', 'target_end_date' => '2027-02-15'],
            ['cluster' => 'Cluster A', 'name' => 'Blok A-03', 'type' => 'Tipe 45/90', 'status' => 'pembangunan', 'start_date' => '2026-08-01', 'target_end_date' => '2027-03-31'],
            ['cluster' => 'Cluster A', 'name' => 'Blok A-04', 'type' => 'Tipe 45/90', 'status' => 'perencanaan', 'start_date' => '2026-10-01', 'target_end_date' => '2027-05-31'],
            ['cluster' => 'Cluster B', 'name' => 'Blok B-01', 'type' => 'Tipe 36/72', 'status' => 'pembangunan', 'start_date' => '2026-06-15', 'target_end_date' => '2027-01-15'],
            ['cluster' => 'Cluster B', 'name' => 'Blok B-02', 'type' => 'Tipe 54/120', 'status' => 'pembangunan', 'start_date' => '2026-07-20', 'target_end_date' => '2027-04-30'],
            ['cluster' => 'Cluster B', 'name' => 'Blok B-03', 'type' => 'Tipe 45/90', 'status' => 'pembangunan', 'start_date' => '2026-08-10', 'target_end_date' => '2027-04-15'],
            ['cluster' => 'Cluster B', 'name' => 'Blok B-04', 'type' => 'Tipe 70/150', 'status' => 'selesai', 'start_date' => '2025-12-01', 'target_end_date' => '2026-08-30'],
        ];

        foreach ($houses as $house) {
            $house['cluster_id'] = Cluster::where('name', $house['cluster'])->value('id');
            unset($house['cluster']);
            $house['house_code'] = House::generateCode($house['name']);
            House::updateOrCreate(['house_code' => $house['house_code']], $house);
        }
    }
}

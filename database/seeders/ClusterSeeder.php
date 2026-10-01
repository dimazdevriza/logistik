<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClusterSeeder extends Seeder
{
    public function run(): void
    {
        Cluster::firstOrCreate(['name' => 'Cluster A'], ['description' => 'Tahap pembangunan sisi utara.']);
        Cluster::firstOrCreate(['name' => 'Cluster B'], ['description' => 'Tahap pembangunan sisi selatan.']);

        User::where('email', 'logistik@logistik.com')
            ->update(['cluster_id' => Cluster::where('name', 'Cluster A')->value('id')]);
    }
}

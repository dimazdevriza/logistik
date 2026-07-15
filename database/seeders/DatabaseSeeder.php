<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SupplierSeeder::class,
            CategorySeeder::class,
            MaterialSeeder::class,
            ToolSeeder::class,
            HouseSeeder::class,
            ToolUsageSeeder::class,
        ]);
    }
}

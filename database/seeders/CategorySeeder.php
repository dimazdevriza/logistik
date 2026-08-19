<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Material categories
            ['name' => 'Semen', 'type' => 'material'],
            ['name' => 'Beton', 'type' => 'material'],
            ['name' => 'Besi', 'type' => 'material'],
            ['name' => 'Baja', 'type' => 'material'],
            ['name' => 'Kayu', 'type' => 'material'],
            ['name' => 'Pasir', 'type' => 'material'],
            ['name' => 'Batu', 'type' => 'material'],
            ['name' => 'Keramik', 'type' => 'material'],
            ['name' => 'Granit', 'type' => 'material'],
            ['name' => 'Cat', 'type' => 'material'],
            ['name' => 'Finishing', 'type' => 'material'],
            ['name' => 'Atap', 'type' => 'material'],
            ['name' => 'Genteng', 'type' => 'material'],
            ['name' => 'Pipa', 'type' => 'material'],
            ['name' => 'Sanitasi', 'type' => 'material'],
            ['name' => 'Listrik', 'type' => 'material'],
            ['name' => 'Kabel', 'type' => 'material'],


            // Tool categories
            ['name' => 'Alat Berat', 'type' => 'tool'],
            ['name' => 'Alat Tangan', 'type' => 'tool'],
            ['name' => 'Alat Ukur', 'type' => 'tool'],
            ['name' => 'Alat Keselamatan', 'type' => 'tool'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}

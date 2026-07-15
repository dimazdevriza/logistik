<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            // Semen & Beton (category 1 & 2, supplier 1)
            ['supplier_id' => 1, 'category_id' => 1, 'name' => 'Semen Portland 50kg', 'unit' => 'sak', 'unit_price' => 65000, 'stock' => 300],
            ['supplier_id' => 1, 'category_id' => 1, 'name' => 'Semen Portland 50kg', 'unit' => 'sak', 'unit_price' => 72000, 'stock' => 200],
            ['supplier_id' => 1, 'category_id' => 2, 'name' => 'Beton Ready Mix K-250', 'unit' => 'm³', 'unit_price' => 850000, 'stock' => 0],

            // Besi & Baja (category 3 & 4, supplier 2)
            ['supplier_id' => 2, 'category_id' => 3, 'name' => 'Besi Beton 10mm', 'unit' => 'batang', 'unit_price' => 75000, 'stock' => 300],
            ['supplier_id' => 2, 'category_id' => 3, 'name' => 'Besi Beton 12mm', 'unit' => 'batang', 'unit_price' => 95000, 'stock' => 200],
            ['supplier_id' => 2, 'category_id' => 3, 'name' => 'Kawat Bendrat', 'unit' => 'kg', 'unit_price' => 18000, 'stock' => 150],
            ['supplier_id' => 2, 'category_id' => 3, 'name' => 'Paku 5cm', 'unit' => 'kg', 'unit_price' => 22000, 'stock' => 100],

            // Kayu (category 5, supplier 3)
            ['supplier_id' => 3, 'category_id' => 5, 'name' => 'Kayu Balok 6/12', 'unit' => 'batang', 'unit_price' => 85000, 'stock' => 120],
            ['supplier_id' => 3, 'category_id' => 5, 'name' => 'Kayu Papan 2/20', 'unit' => 'lembar', 'unit_price' => 45000, 'stock' => 200],
            ['supplier_id' => 3, 'category_id' => 5, 'name' => 'Triplek 9mm', 'unit' => 'lembar', 'unit_price' => 125000, 'stock' => 80],

            // Pasir & Batu (category 6 & 7, supplier 5)
            ['supplier_id' => 5, 'category_id' => 6, 'name' => 'Pasir Pasang', 'unit' => 'm³', 'unit_price' => 250000, 'stock' => 30],
            ['supplier_id' => 5, 'category_id' => 6, 'name' => 'Pasir Pasang', 'unit' => 'm³', 'unit_price' => 280000, 'stock' => 20],
            ['supplier_id' => 5, 'category_id' => 6, 'name' => 'Pasir Cor', 'unit' => 'm³', 'unit_price' => 300000, 'stock' => 40],
            ['supplier_id' => 5, 'category_id' => 7, 'name' => 'Batu Split', 'unit' => 'm³', 'unit_price' => 350000, 'stock' => 30],
            ['supplier_id' => 5, 'category_id' => 7, 'name' => 'Batu Bata Merah', 'unit' => 'buah', 'unit_price' => 800, 'stock' => 10000],

            // Keramik & Granit (category 8 & 9, supplier 4)
            ['supplier_id' => 4, 'category_id' => 8, 'name' => 'Keramik Lantai 40x40', 'unit' => 'dus', 'unit_price' => 55000, 'stock' => 150],
            ['supplier_id' => 4, 'category_id' => 8, 'name' => 'Keramik Dinding 25x40', 'unit' => 'dus', 'unit_price' => 48000, 'stock' => 100],

            // Cat & Finishing (category 10 & 11, supplier 4)
            ['supplier_id' => 4, 'category_id' => 10, 'name' => 'Cat Tembok Interior 5kg', 'unit' => 'kaleng', 'unit_price' => 95000, 'stock' => 60],
            ['supplier_id' => 4, 'category_id' => 10, 'name' => 'Cat Tembok Eksterior 5kg', 'unit' => 'kaleng', 'unit_price' => 125000, 'stock' => 40],

            // Atap & Genteng (category 12 & 13, supplier 4)
            ['supplier_id' => 4, 'category_id' => 13, 'name' => 'Genteng Beton', 'unit' => 'buah', 'unit_price' => 8500, 'stock' => 3000],
            ['supplier_id' => 4, 'category_id' => 4, 'name' => 'Baja Ringan C75', 'unit' => 'batang', 'unit_price' => 75000, 'stock' => 200],

            // Pipa & Sanitasi (category 14 & 15, supplier 4)
            ['supplier_id' => 4, 'category_id' => 14, 'name' => 'Pipa PVC 4 inch', 'unit' => 'batang', 'unit_price' => 65000, 'stock' => 80],
            ['supplier_id' => 4, 'category_id' => 14, 'name' => 'Pipa PVC 1/2 inch', 'unit' => 'batang', 'unit_price' => 25000, 'stock' => 120],

            // Listrik & Kabel (category 16 & 17, supplier 4)
            ['supplier_id' => 4, 'category_id' => 17, 'name' => 'Kabel NYM 2x2.5mm', 'unit' => 'meter', 'unit_price' => 12000, 'stock' => 500],
            ['supplier_id' => 4, 'category_id' => 16, 'name' => 'Stop Kontak', 'unit' => 'buah', 'unit_price' => 15000, 'stock' => 200],
        ];

        foreach ($materials as $index => $material) {
            Material::create($material);
        }
    }
}

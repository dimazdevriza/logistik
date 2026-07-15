<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT. Semen Padang',
                'contact_person' => 'Budi Santoso',
                'phone' => '0751-123456',
                'address' => 'Jl. Raya Indarung, Padang',
            ],
            [
                'name' => 'CV. Baja Mandiri',
                'contact_person' => 'Andi Pratama',
                'phone' => '0812-3456-7890',
                'address' => 'Jl. Bypass, Padang',
            ],
            [
                'name' => 'UD. Kayu Jati Sejahtera',
                'contact_person' => 'Hendra Wijaya',
                'phone' => '0813-5678-9012',
                'address' => 'Jl. Adinegoro, Lubuk Buaya',
            ],
            [
                'name' => 'TB. Maju Bersama',
                'contact_person' => 'Rini Susanti',
                'phone' => '0751-654321',
                'address' => 'Jl. S. Parman, Padang',
            ],
            [
                'name' => 'PT. Pasir Nusantara',
                'contact_person' => 'Dedi Kurniawan',
                'phone' => '0856-1234-5678',
                'address' => 'Jl. Pelabuhan, Teluk Bayur',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}

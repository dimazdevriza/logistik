<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@logistik.com'],
            [
                'name' => 'Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'admin',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'logistik@logistik.com'],
            [
                'name' => 'Staff Logistik',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'logistik',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'mandor@logistik.com'],
            [
                'name' => 'Mandor Lapangan',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'mandor',
            ]
        );
    }
}

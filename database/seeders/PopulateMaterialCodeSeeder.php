<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PopulateMaterialCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = \App\Models\Material::with('category')->orderBy('id')->get();
        $categoryCounters = [];

        foreach ($materials as $material) {
            $catName = $material->category?->name ?? 'Material';
            $words = explode(' ', trim($catName));
            $prefix = (count($words) > 1)
                ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
                : strtoupper(substr($catName, 0, 2));

            $prefix .= '-';

            if (!isset($categoryCounters[$prefix])) {
                $categoryCounters[$prefix] = 1;
            } else {
                $categoryCounters[$prefix]++;
            }

            $code = $prefix . str_pad($categoryCounters[$prefix], 3, '0', STR_PAD_LEFT);
            $material->code = $code;
            $material->save();

            $this->command?->info("Material #{$material->id} [{$material->name}] -> {$code}");
        }

        $this->command?->info('Semua data material berhasil diperbarui dengan kode unik!');
    }
}

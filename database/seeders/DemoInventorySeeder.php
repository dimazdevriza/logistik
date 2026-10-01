<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolWarehouseBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ToolInventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call(CategorySeeder::class);
            $warehouse = Warehouse::firstOrCreate(['name' => 'Gudang Utama']);
            $siteWarehouse = Warehouse::firstOrCreate(['name' => 'Gudang Lapangan Cluster B'], ['address' => 'Area proyek sisi selatan']);
            $userId = User::where('role', 'logistik')->value('id') ?? User::value('id');
            $legacySupplier = Supplier::where('name', 'Supplier Demo Konstruksi')->first();
            $supplierRecords = [
                [
                    'name' => 'PT. Andalas Beton Perkasa',
                    'contact_person' => 'Tim Penjualan',
                    'phone' => '0812-0000-0101',
                    'address' => 'Jl. By Pass Km. 12, Koto Tangah, Padang',
                ],
                [
                    'name' => 'CV. Baja Prima Konstruksi',
                    'contact_person' => 'Bagian Pengadaan',
                    'phone' => '0812-0000-0102',
                    'address' => 'Jl. Raya Indarung No. 18, Lubuk Kilangan, Padang',
                ],
                [
                    'name' => 'UD. Agregat Alam Sejahtera',
                    'contact_person' => 'Admin Pemasok',
                    'phone' => '0812-0000-0103',
                    'address' => 'Jl. Raya Padang–Solok Km. 23, Padang',
                ],
                [
                    'name' => 'TB. Mitra Bangunan Nusantara',
                    'contact_person' => 'Tim Layanan Pelanggan',
                    'phone' => '0812-0000-0104',
                    'address' => 'Jl. S. Parman No. 45, Ulak Karang, Padang',
                ],
            ];
            $suppliers = [];

            foreach ($supplierRecords as $supplierRecord) {
                $supplier = Supplier::where('name', $supplierRecord['name'])->first();

                if (! $supplier && $legacySupplier) {
                    $supplier = $legacySupplier;
                    $supplier->fill($supplierRecord)->save();
                    $legacySupplier = null;
                } else {
                    $supplier ??= Supplier::firstOrCreate(
                        ['name' => $supplierRecord['name']],
                        $supplierRecord,
                    );
                }

                $suppliers[$supplierRecord['name']] = $supplier;
            }

            $supplierByCategory = [
                'Semen' => $suppliers['PT. Andalas Beton Perkasa']->id,
                'Beton' => $suppliers['PT. Andalas Beton Perkasa']->id,
                'Besi' => $suppliers['CV. Baja Prima Konstruksi']->id,
                'Baja' => $suppliers['CV. Baja Prima Konstruksi']->id,
                'Atap' => $suppliers['CV. Baja Prima Konstruksi']->id,
                'Genteng' => $suppliers['CV. Baja Prima Konstruksi']->id,
                'Pasir' => $suppliers['UD. Agregat Alam Sejahtera']->id,
                'Batu' => $suppliers['UD. Agregat Alam Sejahtera']->id,
                'Kayu' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Keramik' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Granit' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Cat' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Finishing' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Pipa' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Sanitasi' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Listrik' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
                'Kabel' => $suppliers['TB. Mitra Bangunan Nusantara']->id,
            ];
            $materialCategories = Category::where('type', 'material')->pluck('id', 'name');
            $toolCategories = Category::where('type', 'tool')->pluck('id', 'name');

            $materials = [
                ['Semen', 'Semen Portland 50 kg', 'sak', 65000, 300],
                ['Semen', 'Semen PCC 40 kg', 'sak', 55000, 250],
                ['Semen', 'Semen Putih 40 kg', 'sak', 95000, 60],
                ['Semen', 'Mortar Perekat Bata Ringan 40 kg', 'sak', 78000, 180],
                ['Semen', 'Mortar Acian 40 kg', 'sak', 85000, 120],
                ['Beton', 'Beton Ready Mix K-225', 'm³', 820000, 12.5],
                ['Beton', 'Beton Ready Mix K-250', 'm³', 850000, 20],
                ['Beton', 'Beton Ready Mix K-300', 'm³', 920000, 15.5],
                ['Besi', 'Besi Beton Polos 8 mm', 'batang', 42000, 240],
                ['Besi', 'Besi Beton Polos 10 mm', 'batang', 68000, 300],
                ['Besi', 'Besi Beton Polos 12 mm', 'batang', 98000, 180],
                ['Besi', 'Besi Beton Ulir 13 mm', 'batang', 115000, 150],
                ['Besi', 'Besi Beton Ulir 16 mm', 'batang', 172000, 90],
                ['Besi', 'Wiremesh M6', 'lembar', 385000, 45],
                ['Besi', 'Wiremesh M8', 'lembar', 650000, 30],
                ['Besi', 'Kawat Bendrat', 'kg', 18000, 125.5],
                ['Besi', 'Paku Kayu 5 cm', 'kg', 22000, 75],
                ['Baja', 'Baja Ringan C75 0.75 mm', 'batang', 85000, 200],
                ['Baja', 'Reng Baja Ringan 0.45 mm', 'batang', 38000, 300],
                ['Baja', 'Hollow Galvanis 40x40 mm', 'batang', 110000, 100],
                ['Baja', 'Besi Siku 40x40 mm', 'batang', 145000, 80],
                ['Kayu', 'Kayu Balok 6/12', 'batang', 85000, 120],
                ['Kayu', 'Kayu Kaso 5/7', 'batang', 45000, 160],
                ['Kayu', 'Papan Bekisting 2/20', 'lembar', 48000, 200],
                ['Kayu', 'Triplek 9 mm', 'lembar', 125000, 80],
                ['Kayu', 'Triplek 12 mm', 'lembar', 165000, 65],
                ['Pasir', 'Pasir Pasang', 'm³', 250000, 35.5],
                ['Pasir', 'Pasir Cor', 'm³', 300000, 28.75],
                ['Pasir', 'Pasir Urug', 'm³', 180000, 40],
                ['Batu', 'Batu Split 1/2', 'm³', 350000, 22.5],
                ['Batu', 'Batu Kali', 'm³', 280000, 18],
                ['Batu', 'Bata Merah', 'buah', 900, 12000],
                ['Batu', 'Bata Ringan 10 cm', 'm³', 720000, 25],
                ['Keramik', 'Keramik Lantai 40x40 cm', 'dus', 65000, 180],
                ['Keramik', 'Keramik Lantai 60x60 cm', 'dus', 110000, 120],
                ['Keramik', 'Keramik Dinding 25x40 cm', 'dus', 72000, 95],
                ['Granit', 'Granit Polished 60x60 cm', 'dus', 185000, 70],
                ['Granit', 'Granit Matt 80x80 cm', 'dus', 265000, 40],
                ['Cat', 'Cat Interior Putih 5 kg', 'kaleng', 125000, 80],
                ['Cat', 'Cat Interior Krem 25 kg', 'pail', 580000, 24],
                ['Cat', 'Cat Eksterior Abu-abu 5 kg', 'kaleng', 185000, 45],
                ['Cat', 'Pelapis Anti Bocor 4 kg', 'kaleng', 210000, 36],
                ['Finishing', 'Papan Gypsum 9 mm', 'lembar', 68000, 150],
                ['Finishing', 'Compound Gypsum 20 kg', 'sak', 95000, 60],
                ['Finishing', 'Plafon PVC 20 cm', 'batang', 42000, 250],
                ['Atap', 'Atap Spandek 0.30 mm', 'lembar', 135000, 100],
                ['Atap', 'Atap Polycarbonate 5 mm', 'lembar', 450000, 20],
                ['Genteng', 'Genteng Beton Flat', 'buah', 9500, 4000],
                ['Genteng', 'Nok Genteng Beton', 'buah', 22000, 300],
                ['Pipa', 'Pipa PVC AW 1/2 inch', 'batang', 32000, 150],
                ['Pipa', 'Pipa PVC AW 3/4 inch', 'batang', 45000, 130],
                ['Pipa', 'Pipa PVC D 2 inch', 'batang', 65000, 100],
                ['Pipa', 'Pipa PVC D 4 inch', 'batang', 125000, 80],
                ['Sanitasi', 'Kloset Duduk', 'unit', 1250000, 20],
                ['Sanitasi', 'Wastafel Keramik', 'unit', 450000, 24],
                ['Sanitasi', 'Floor Drain Stainless', 'buah', 85000, 80],
                ['Listrik', 'MCB 6 Ampere', 'buah', 65000, 50],
                ['Listrik', 'Stop Kontak Tanam', 'buah', 28000, 120],
                ['Listrik', 'Saklar Seri', 'buah', 32000, 100],
                ['Listrik', 'Lampu LED 12 Watt', 'buah', 38000, 150],
                ['Kabel', 'Kabel NYM 2x1.5 mm', 'meter', 8500, 500],
                ['Kabel', 'Kabel NYM 2x2.5 mm', 'meter', 14000, 400],
                ['Kabel', 'Kabel NYY 3x2.5 mm', 'meter', 22000, 250],
            ];

            foreach ($materials as $index => [$category, $name, $unit, $price, $stock]) {
                $legacyCode = 'DEMO-MAT-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
                $code = 'MAT-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
                $supplierId = $supplierByCategory[$category];
                $material = Material::where('code', $legacyCode)->first();

                if ($material) {
                    $material->update([
                        'code' => $code,
                        'supplier_id' => $supplierId,
                    ]);
                } else {
                    $material = Material::firstOrCreate(['code' => $code], [
                        'warehouse_id' => $warehouse->id,
                        'supplier_id' => $supplierId,
                        'category_id' => $materialCategories[$category],
                        'name' => $name,
                        'unit' => $unit,
                        'unit_price' => $price,
                        'stock' => $stock,
                    ]);
                }

                if ($material->supplier_id !== $supplierId) {
                    $material->update(['supplier_id' => $supplierId]);
                }

                DB::table('stock_ins')
                    ->where('material_id', $material->id)
                    ->where('entry_type', 'opening_balance')
                    ->whereIn('entry_code', ['OPEN-'.$legacyCode, 'OPEN-'.$code])
                    ->update([
                        'entry_code' => 'OPEN-'.$code,
                        'supplier_id' => $supplierId,
                        'notes' => 'Saldo awal persediaan.',
                    ]);

                if ($material->wasRecentlyCreated) {
                    $batches = match ($code) {
                        'MAT-0001' => [[200, 62000, 21], [100, 65000, 5]],
                        'MAT-0010' => [[120, 65000, 16], [180, 68000, 4]],
                        'MAT-0027' => [[20, 240000, 12], [15.5, 250000, 3]],
                        default => [[$stock, $price, 5 + ($index % 17)]],
                    };

                    foreach ($batches as $batchIndex => [$quantity, $batchPrice, $daysAgo]) {
                        $receivedAt = now()->subDays($daysAgo)->setTime(8 + ($index % 3), 15 + ($index % 40));
                        StockIn::create([
                            'transaction_code' => 'MSK-'.Str::ulid(),
                            'entry_type' => 'receipt',
                            'material_id' => $material->id,
                            'warehouse_id' => $code === 'MAT-0027' && $batchIndex === 1 ? $siteWarehouse->id : $warehouse->id,
                            'supplier_id' => $supplierId,
                            'user_id' => $userId,
                            'quantity' => $quantity,
                            'remaining_quantity' => $quantity,
                            'unit_price' => $batchPrice,
                            'total_cost' => $quantity * $batchPrice,
                            'date' => $receivedAt->toDateString(),
                            'received_at' => $receivedAt,
                            'notes' => 'Penerimaan stok untuk kebutuhan pekerjaan konstruksi.',
                        ]);
                    }
                }
            }

            $tools = [
                ['Alat Berat', 'Molen Beton 350 Liter', 15000000, 3],
                ['Alat Berat', 'Stamper Kodok', 8500000, 4],
                ['Alat Berat', 'Plate Compactor', 7200000, 3],
                ['Alat Berat', 'Bar Cutter 32 mm', 18500000, 2],
                ['Alat Berat', 'Bar Bender 32 mm', 21000000, 2],
                ['Alat Berat', 'Genset 5000 Watt', 9500000, 3],
                ['Alat Berat', 'Pompa Air Celup 2 inch', 1850000, 5],
                ['Alat Berat', 'Concrete Vibrator', 3200000, 4],
                ['Alat Berat', 'Scaffolding Set 1.7 m', 850000, 40],
                ['Alat Berat', 'Kompresor 50 Liter', 3500000, 3],
                ['Alat Tangan', 'Bor Beton SDS Plus', 1800000, 8],
                ['Alat Tangan', 'Bor Baterai 18 Volt', 1450000, 6],
                ['Alat Tangan', 'Gerinda Tangan 4 inch', 650000, 10],
                ['Alat Tangan', 'Mesin Potong Besi 14 inch', 2200000, 4],
                ['Alat Tangan', 'Gergaji Circular 7 inch', 1650000, 5],
                ['Alat Tangan', 'Mesin Las Inverter 160 A', 2100000, 4],
                ['Alat Tangan', 'Mesin Serut Kayu', 950000, 4],
                ['Alat Tangan', 'Mesin Amplas Orbital', 750000, 5],
                ['Alat Tangan', 'Pemotong Keramik Manual 60 cm', 850000, 6],
                ['Alat Tangan', 'Palu Kambing 16 oz', 85000, 24],
                ['Alat Tangan', 'Palu Godam 5 kg', 225000, 8],
                ['Alat Tangan', 'Cangkul Baja', 95000, 20],
                ['Alat Tangan', 'Sekop Pasir', 85000, 20],
                ['Alat Tangan', 'Linggis 90 cm', 125000, 12],
                ['Alat Tangan', 'Gerobak Sorong', 550000, 15],
                ['Alat Tangan', 'Tang Kombinasi 8 inch', 75000, 18],
                ['Alat Tangan', 'Kunci Inggris 12 inch', 115000, 12],
                ['Alat Tangan', 'Sendok Semen', 45000, 30],
                ['Alat Tangan', 'Tangga Aluminium 3 m', 1250000, 6],
                ['Alat Ukur', 'Theodolite Digital', 18500000, 2],
                ['Alat Ukur', 'Auto Level', 4500000, 3],
                ['Alat Ukur', 'Laser Level 12 Garis', 1250000, 5],
                ['Alat Ukur', 'Meteran Gulung 50 m', 185000, 8],
                ['Alat Ukur', 'Meteran Saku 5 m', 55000, 25],
                ['Alat Ukur', 'Waterpass Aluminium 60 cm', 125000, 12],
                ['Alat Ukur', 'Multimeter Digital', 350000, 6],
                ['Alat Ukur', 'Jangka Sorong Digital', 275000, 4],
                ['Alat Keselamatan', 'Helm Proyek Putih', 65000, 30],
                ['Alat Keselamatan', 'Helm Proyek Kuning', 55000, 60],
                ['Alat Keselamatan', 'Rompi Reflektif', 45000, 60],
                ['Alat Keselamatan', 'Sepatu Safety Ukuran 42', 350000, 25],
                ['Alat Keselamatan', 'Full Body Harness', 650000, 12],
                ['Alat Keselamatan', 'Kacamata Safety', 45000, 40],
                ['Alat Keselamatan', 'Pelindung Telinga', 85000, 20],
                ['Alat Keselamatan', 'APAR Powder 6 kg', 450000, 8],
            ];

            $toolCodePrefixes = [
                'Alat Berat' => 'AB',
                'Alat Tangan' => 'AT',
                'Alat Ukur' => 'AU',
                'Alat Keselamatan' => 'AK',
            ];
            $toolCodeSequences = [];

            foreach ($tools as $index => [$category, $name, $price, $quantity]) {
                $legacyCode = 'DEMO-TOOL-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
                $toolCodeSequences[$category] = ($toolCodeSequences[$category] ?? 100) + 1;
                $code = $toolCodePrefixes[$category].'-'.$toolCodeSequences[$category];
                $tool = Tool::where('code', $legacyCode)->first();

                if ($tool) {
                    $tool->update([
                        'code' => $code,
                        'entry_code' => 'OPEN-'.$code,
                    ]);
                } else {
                    Tool::firstOrCreate(['code' => $code], [
                        'entry_type' => 'receipt',
                        'warehouse_id' => $warehouse->id,
                        'category_id' => $toolCategories[$category],
                        'name' => $name,
                        'condition' => 'baik',
                        'purchase_price' => $price,
                        'total_qty' => $quantity,
                        'available_qty' => $quantity,
                        'qty_broken' => 0,
                        'received_at' => now()->subDays(4 + ($index % 14))->setTime(9, 0),
                        'received_date' => now()->subDays(4 + ($index % 14))->toDateString(),
                        'recorded_by_id' => $userId,
                    ]);
                    $tool = Tool::where('code', $code)->firstOrFail();
                }

                if (! ToolWarehouseBalance::where('tool_id', $tool->id)->where('warehouse_id', $siteWarehouse->id)->exists()) {
                    $moveQuantity = min(1, max(0, $tool->total_qty - 1));
                    if ($moveQuantity > 0) {
                        ToolInventory::transferAvailable($tool, $warehouse->id, $siteWarehouse->id, $moveQuantity);
                    }
                }
            }

            $this->command?->info('Contoh inventaris diperbarui: '.count($materials).' material dan '.count($tools).' alat.');
        });
    }
}

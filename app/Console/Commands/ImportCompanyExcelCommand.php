<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ImportCompanyExcelCommand extends Command
{
    protected $signature = 'import:company-excel 
                            {file? : Path berkas Excel} 
                            {--warehouse=Gudang Villagio : Nama gudang tujuan} 
                            {--cluster=VILLAGIO : Nama cluster tujuan} 
                            {--dry-run : Uji coba validasi tanpa menyimpan ke database} 
                            {--force : Jalankan langsung tanpa konfirmasi}';

    protected $description = 'Impor data master & riwayat pengeluaran dari Excel perusahaan (LOGISTIK AMPANG)';

    public function handle(): int
    {
        $filePath = $this->argument('file') ?: 'C:\Users\DIM\Downloads\LOGISTIK AMPANG (ORI).xlsx';
        $warehouseName = (string) $this->option('warehouse');
        $clusterName = (string) $this->option('cluster');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        if (! file_exists($filePath)) {
            $this->error("Berkas tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $this->info("=== Impor Data Logistik Perusahaan ===");
        $this->line("Berkas: <comment>{$filePath}</comment>");
        $this->line("Cluster: <comment>{$clusterName}</comment> | Gudang: <comment>{$warehouseName}</comment>");
        if ($isDryRun) {
            $this->warn("Mode: DRY-RUN (tidak ada data yang disimpan)");
        }

        if (! $isDryRun && ! $isForce) {
            if (! $this->confirm('Lanjutkan proses impor data ke database?', true)) {
                $this->info('Dibatalkan oleh pengguna.');
                return self::SUCCESS;
            }
        }

        $tempDir = storage_path('app/import_temp');
        File::ensureDirectoryExists($tempDir);

        $this->info("Menjalankan ekstraksi data via Python...");
        $startTime = microtime(true);

        $extractorScript = base_path('app/Support/extract_company_excel.py');
        $process = new Process([
            'python',
            $extractorScript,
            '--file=' . $filePath,
            '--out-dir=' . $tempDir,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error("Gagal mengekstrak berkas Excel:");
            $this->line($process->getErrorOutput());
            return self::FAILURE;
        }

        $extractMeta = json_decode($process->getOutput(), true);
        $this->info("Ekstraksi selesai dalam " . ($extractMeta['elapsed_seconds'] ?? '0') . " detik.");
        $this->line("• Unit Rumah: " . ($extractMeta['houses'] ?? 0));
        $this->line("• Material: " . ($extractMeta['materials'] ?? 0));
        $this->line("• Transaksi: " . number_format($extractMeta['transactions'] ?? 0));

        // Read parsed JSON files
        $houses = json_decode(file_get_contents("{$tempDir}/houses.json"), true);
        $categories = json_decode(file_get_contents("{$tempDir}/categories.json"), true);
        $suppliers = json_decode(file_get_contents("{$tempDir}/suppliers.json"), true);
        $materials = json_decode(file_get_contents("{$tempDir}/materials.json"), true);
        $usagesFile = "{$tempDir}/usages.jsonl";

        if (! file_exists($usagesFile)) {
            $this->error("Berkas transaksi usages.jsonl tidak ditemukan.");
            return self::FAILURE;
        }

        $user = \App\Models\User::first();
        if (! $user) {
            $user = \App\Models\User::create([
                'name' => 'Admin Logistik',
                'email' => 'admin@logistik.test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]);
        }
        $userId = $user->id;

        $dbStartTime = microtime(true);
        DB::beginTransaction();

        try {
            // 1. Cluster & Warehouse
            $cluster = Cluster::firstOrCreate(
                ['name' => $clusterName],
                ['description' => 'Cluster ' . $clusterName]
            );

            $warehouse = Warehouse::firstOrCreate(
                ['name' => $warehouseName],
                ['address' => 'Area ' . $clusterName]
            );

            // 2. Categories
            $this->info("Menyinkronkan kategori...");
            $catMap = [];
            foreach ($categories as $catName) {
                $c = Category::firstOrCreate(
                    ['name' => $catName],
                    ['type' => 'material']
                );
                $catMap[$catName] = $c->id;
            }

            // 3. Suppliers
            $this->info("Menyinkronkan supplier...");
            $suppMap = [];
            foreach ($suppliers as $suppName) {
                $s = Supplier::firstOrCreate(['name' => $suppName]);
                $suppMap[$suppName] = $s->id;
            }

            // 4. Houses
            $this->info("Menyinkronkan unit rumah & infrastruktur...");
            $houseMap = [];
            foreach ($houses as $h) {
                $status = ($h['physical_progress'] >= 100)
                    ? 'selesai'
                    : (($h['physical_progress'] > 0) ? 'pembangunan' : 'perencanaan');

                $house = House::firstOrNew([
                    'house_code' => $h['house_code'],
                    'cluster_id' => $cluster->id,
                ]);

                $house->name = $h['name'];
                $house->type = $h['type'];
                $house->category = $h['category'] ?? 'house';
                $house->budget = $h['budget'] ?? 0;
                $house->material_budget = $h['material_budget'] ?? 0;
                $house->physical_progress = $h['physical_progress'] ?? 0;
                $house->status = $status;
                if (! empty($h['mandor_name'])) {
                    $house->mandor_name = $h['mandor_name'];
                }
                $house->save();

                $houseMap[$h['house_code']] = $house->id;
            }

            $infraHouseId = $houseMap['VIL-INFRA'] ?? array_values($houseMap)[0];

            // 5. Materials & StockIns
            $this->info("Menyinkronkan material & saldo awal...");
            $matCodeMap = [];
            $matNameMap = [];

            $now = now()->toDateTimeString();
            $stockInsToInsert = [];

            foreach ($materials as $m) {
                $code = $m['code'];
                $name = $m['name'];
                $catId = $catMap[$m['category']] ?? null;
                $suppId = $m['supplier'] ? ($suppMap[$m['supplier']] ?? null) : null;

                $material = Material::where('warehouse_id', $warehouse->id)
                    ->where(function ($q) use ($code, $name) {
                        $q->where('code', $code)->orWhere('name', $name);
                    })
                    ->first();

                if (! $material) {
                    $material = new Material();
                    $material->warehouse_id = $warehouse->id;
                }

                $material->code = $code;
                $material->name = $name;
                $material->category_id = $catId;
                $material->supplier_id = $suppId;
                $material->unit = $m['unit'] ?: 'pcs';
                $material->unit_price = $m['unit_price'];
                $material->stock = $m['stock'];
                $material->save();

                $matCodeMap[$code] = $material->id;
                $matNameMap[strtolower($name)] = $material->id;

                // Opening balance batch if incoming stock recorded
                if ($m['datang'] > 0) {
                    $entryCode = 'OPEN-MAT-' . $material->id;
                    $stockInsToInsert[] = [
                        'entry_code' => $entryCode,
                        'entry_type' => 'opening_balance',
                        'material_id' => $material->id,
                        'warehouse_id' => $warehouse->id,
                        'supplier_id' => $suppId,
                        'user_id' => $userId,
                        'quantity' => $m['datang'],
                        'remaining_quantity' => $m['stock'],
                        'unit_price' => $m['unit_price'],
                        'total_cost' => round($m['datang'] * $m['unit_price'], 2),
                        'date' => '2024-01-01',
                        'notes' => 'Saldo awal impor LOGISTIK AMPANG (ORI)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($stockInsToInsert)) {
                foreach (array_chunk($stockInsToInsert, 500) as $chunk) {
                    DB::table('stock_ins')->upsert(
                        $chunk,
                        ['entry_code'],
                        ['quantity', 'remaining_quantity', 'unit_price', 'total_cost', 'updated_at']
                    );
                }
            }

            // 6. Material Usages (Streamed in chunks of 1,000)
            $this->info("Memproses 27k+ transaksi pengeluaran material...");
            $handle = fopen($usagesFile, 'r');
            $batch = [];
            $batchSize = 1000;
            $totalInserted = 0;
            $bar = $this->output->createProgressBar($extractMeta['transactions'] ?? 27114);

            while (($line = fgets($handle)) !== false) {
                $tx = json_decode(trim($line), true);
                if (! $tx) {
                    continue;
                }

                $hId = $houseMap[$tx['house_code']] ?? $infraHouseId;
                $mId = $matCodeMap[$tx['material_code']] ?? ($matNameMap[strtolower($tx['material_name'])] ?? null);

                if (! $mId) {
                    continue;
                }

                $txCode = 'KLR-VIL-' . str_pad($tx['row'], 7, '0', STR_PAD_LEFT);
                $batch[] = [
                    'transaction_code' => $txCode,
                    'house_id' => $hId,
                    'material_id' => $mId,
                    'user_id' => $userId,
                    'quantity' => $tx['quantity'],
                    'unit_price_at_usage' => $tx['unit_price'],
                    'total_cost' => $tx['total_cost'],
                    'usage_date' => $tx['date'],
                    'notes' => $tx['notes'] ?: 'Pengeluaran logistik ' . $tx['house_code'],
                    'taken_by' => $tx['taken_by'],
                    'created_at' => $tx['date'] . ' 08:00:00',
                    'updated_at' => $tx['date'] . ' 08:00:00',
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('material_usages')->upsert(
                        $batch,
                        ['transaction_code', 'house_id'],
                        ['quantity', 'unit_price_at_usage', 'total_cost', 'usage_date', 'notes', 'taken_by', 'updated_at']
                    );
                    $totalInserted += count($batch);
                    $bar->advance(count($batch));
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                DB::table('material_usages')->upsert(
                    $batch,
                    ['transaction_code', 'house_id'],
                    ['quantity', 'unit_price_at_usage', 'total_cost', 'usage_date', 'notes', 'taken_by', 'updated_at']
                );
                $totalInserted += count($batch);
                $bar->advance(count($batch));
            }

            fclose($handle);
            $bar->finish();
            $this->newLine();

            if ($isDryRun) {
                DB::rollBack();
                $this->warn("\n[DRY RUN BERHASIL] Validasi data tuntas tanpa kendala. Tidak ada perubahan yang disimpan.");
            } else {
                DB::commit();
                $this->info("\n[BERHASIL] Seluruh data berhasil disimpan ke database!");
            }

            $totalDuration = round(microtime(true) - $startTime, 2);
            $dbDuration = round(microtime(true) - $dbStartTime, 2);

            $this->newLine();
            $this->table(
                ['Entitas', 'Jumlah Diproses', 'Status'],
                [
                    ['Cluster', '1 (' . $clusterName . ')', 'OK'],
                    ['Warehouse', '1 (' . $warehouseName . ')', 'OK'],
                    ['Unit Rumah & Fasum', count($houseMap), 'OK'],
                    ['Kategori Material', count($catMap), 'OK'],
                    ['Supplier', count($suppMap), 'OK'],
                    ['Material Aktif', count($matCodeMap), 'OK'],
                    ['Batch Stok Awal', count($stockInsToInsert), 'OK'],
                    ['Transaksi Barang Keluar', number_format($totalInserted), 'OK'],
                ]
            );

            $this->line("Waktu Eksekusi: Total {$totalDuration}s (Database {$dbDuration}s)");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("\nTerjadi kesalahan selama impor: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}

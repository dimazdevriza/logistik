<?php

namespace Tests\Feature;

use App\Imports\ToolImport;
use App\Imports\MaterialImport;
use App\Livewire\Logistik\Materials;
use App\Models\House;
use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ImportReconciliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ImportIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_modified_csv_does_not_repeat_the_same_material_transaction(): void
    {
        House::factory()->create(['name' => 'AUD-DUP-01']);
        $material = Material::factory()->create([
            'name' => 'Audit Semen',
            'stock' => 5,
            'unit' => 'sak',
        ]);

        $firstFile = "Nama Barang,Jenis,Volume,Blok Rumah,Tanggal,Harga Satuan,Catatan Internal\nAudit Semen,keluar,2,AUD-DUP-01,2026-09-17,100000,versi pertama\n";
        $editedFile = "Nama Barang,Jenis,Volume,Blok Rumah,Tanggal,Harga Satuan,Catatan Internal\nAudit Semen,keluar,2,AUD-DUP-01,2026-09-17,100000,catatan berubah saja\n";

        Livewire::test(Materials::class)
            ->call('openImportModal')
            ->set('importFile', UploadedFile::fake()->createWithContent('material-v1.csv', $firstFile))
            ->call('importExcel')
            ->assertHasNoErrors('importFile');

        Livewire::test(Materials::class)
            ->call('openImportModal')
            ->set('importFile', UploadedFile::fake()->createWithContent('material-v2.csv', $editedFile))
            ->call('importExcel')
            ->assertHasNoErrors('importFile');

        $this->assertSame(3.0, (float) $material->fresh()->stock);
        $this->assertSame(1, MaterialUsage::where('material_id', $material->id)->count());
        $this->assertDatabaseCount('import_batches', 2);
    }

    public function test_invalid_material_transaction_date_fails_without_changing_stock(): void
    {
        $material = Material::factory()->create([
            'name' => 'Audit Bata',
            'stock' => 5,
            'unit' => 'buah',
        ]);

        $csv = "Nama Barang,Jenis,Volume,Blok Rumah,Tanggal\nAudit Bata,keluar,2,AUD-DATE-01,bukan-tanggal\n";

        Livewire::test(Materials::class)
            ->call('openImportModal')
            ->set('importFile', UploadedFile::fake()->createWithContent('invalid-date.csv', $csv))
            ->call('importExcel')
            ->assertHasErrors('importFile')
            ->assertSee('Tanggal tidak valid');

        $this->assertSame(5.0, (float) $material->fresh()->stock);
        $this->assertSame(0, MaterialUsage::where('material_id', $material->id)->count());
        $this->assertDatabaseCount('stock_ins', 0);
    }

    public function test_material_import_keeps_different_prices_as_separate_rows_and_uses_named_warehouse(): void
    {
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'Gudang Migrasi Test']);

        (new \App\Imports\MaterialImport)->collection(collect([
            [
                'nama_material' => 'Audit Material Batch',
                'satuan' => 'sak',
                'harga_satuan' => 100000,
                'sisa_stok' => 4,
                'gudang' => $source->name,
            ],
            [
                'nama_material' => 'Audit Material Batch',
                'satuan' => 'sak',
                'harga_satuan' => 110000,
                'sisa_stok' => 7,
                'gudang' => $target->name,
            ],
        ]));

        $this->assertDatabaseHas('materials', [
            'name' => 'Audit Material Batch',
            'warehouse_id' => $source->id,
            'unit_price' => 100000,
            'stock' => 4,
        ]);
        $this->assertDatabaseHas('materials', [
            'name' => 'Audit Material Batch',
            'warehouse_id' => $target->id,
            'unit_price' => 110000,
            'stock' => 7,
        ]);
    }

    public function test_import_requires_named_warehouse_when_multiple_warehouses_exist(): void
    {
        Warehouse::create(['name' => 'Gudang Migrasi Test']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Kolom Gudang wajib diisi');

        (new \App\Imports\MaterialImport)->collection(collect([[
            'nama_material' => 'Audit Material Tanpa Gudang',
            'satuan' => 'sak',
            'harga_satuan' => 100000,
            'sisa_stok' => 2,
        ]]));
    }

    public function test_material_and_tool_imports_reject_unmapped_houses_without_creating_them(): void
    {
        $material = Material::factory()->create([
            'name' => 'Audit Material Unmapped House',
            'unit' => 'sak',
            'stock' => 4,
        ]);
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-UNMAPPED',
            'name' => 'Alat Audit Unmapped House',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);
        $houseCount = House::count();

        try {
            (new MaterialImport)->collection(collect([[
                'nama_barang' => $material->name,
                'jenis' => 'keluar',
                'volume' => 1,
                'blok_rumah' => 'AUD-HOUSE-NOT-MAPPED',
                'tanggal' => '2026-09-17',
                'gudang' => 'Gudang Utama',
            ]]));
            $this->fail('Material history import must not invent a house.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('belum terdaftar', $exception->getMessage());
        }

        try {
            (new ToolImport)->collection(collect([[
                'kode' => $tool->code,
                'nama_alat' => $tool->name,
                'jenis' => 'pinjam',
                'jumlah' => 1,
                'unit_rumah' => 'AUD-HOUSE-NOT-MAPPED',
                'tanggal' => '2026-09-17',
                'gudang' => 'Gudang Utama',
            ]]));
            $this->fail('Tool history import must not invent a house.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('belum terdaftar', $exception->getMessage());
        }

        $this->assertSame($houseCount, House::count());
        $this->assertSame(4.0, (float) $material->fresh()->stock);
        $this->assertSame(1, (int) $tool->fresh()->available_qty);
        $this->assertSame(0, MaterialUsage::where('material_id', $material->id)->count());
        $this->assertSame(0, ToolUsage::where('tool_id', $tool->id)->count());
    }

    public function test_material_import_requires_cluster_to_disambiguate_duplicate_house_names(): void
    {
        $firstCluster = Cluster::create(['name' => 'Audit Cluster First']);
        $secondCluster = Cluster::create(['name' => 'Audit Cluster Second']);
        $firstHouse = House::factory()->create([
            'house_code' => 'AUD-A5-FIRST',
            'name' => 'A5',
            'cluster_id' => $firstCluster->id,
        ]);
        $secondHouse = House::factory()->create([
            'house_code' => 'AUD-A5-SECOND',
            'name' => 'A5',
            'cluster_id' => $secondCluster->id,
        ]);
        $material = Material::factory()->create([
            'name' => 'Audit Material Cluster House',
            'unit' => 'sak',
            'stock' => 4,
        ]);
        $row = [
            'nama_barang' => $material->name,
            'jenis' => 'keluar',
            'volume' => 1,
            'blok_rumah' => 'A5',
            'tanggal' => '2026-09-17',
            'gudang' => 'Gudang Utama',
        ];

        try {
            (new MaterialImport)->collection(collect([$row]));
            $this->fail('Duplicate house names across clusters must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tidak unik', $exception->getMessage());
        }

        (new MaterialImport)->collection(collect([$row + ['cluster' => $secondCluster->name]]));

        $this->assertSame(3.0, (float) $material->fresh()->stock);
        $this->assertDatabaseHas('material_usages', [
            'material_id' => $material->id,
            'house_id' => $secondHouse->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('material_usages', [
            'material_id' => $material->id,
            'house_id' => $firstHouse->id,
        ]);
    }

    public function test_logistics_import_cannot_resolve_a_house_outside_its_cluster(): void
    {
        $assignedCluster = Cluster::create(['name' => 'Audit Assigned Cluster']);
        $foreignCluster = Cluster::create(['name' => 'Audit Foreign Cluster']);
        $foreignHouse = House::factory()->create([
            'house_code' => 'AUD-FOREIGN-HOUSE',
            'name' => 'AUD-FOREIGN-HOUSE',
            'cluster_id' => $foreignCluster->id,
        ]);
        $material = Material::factory()->create([
            'name' => 'Audit Foreign House Material',
            'unit' => 'sak',
            'stock' => 2,
        ]);
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-FOREIGN-HOUSE',
            'name' => 'Alat Audit Foreign House',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);
        $this->actingAs(User::factory()->create([
            'role' => 'logistik',
            'cluster_id' => $assignedCluster->id,
        ]));

        foreach ([
            fn () => (new MaterialImport)->collection(collect([[
                'nama_barang' => $material->name,
                'jenis' => 'keluar',
                'volume' => 1,
                'blok_rumah' => $foreignHouse->house_code,
                'tanggal' => '2026-09-17',
                'gudang' => 'Gudang Utama',
            ]])),
            fn () => (new ToolImport)->collection(collect([[
                'kode' => $tool->code,
                'nama_alat' => $tool->name,
                'jenis' => 'pinjam',
                'jumlah' => 1,
                'unit_rumah' => $foreignHouse->house_code,
                'tanggal' => '2026-09-17',
                'gudang' => 'Gudang Utama',
            ]])),
        ] as $import) {
            try {
                $import();
                $this->fail('Logistics imports must reject houses from another cluster.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('di luar cluster tugas', $exception->getMessage());
            }
        }

        $this->assertSame(2.0, (float) $material->fresh()->stock);
        $this->assertSame(1, (int) $tool->fresh()->available_qty);
        $this->assertSame(0, MaterialUsage::where('material_id', $material->id)->count());
        $this->assertSame(0, ToolUsage::where('tool_id', $tool->id)->count());
    }

    public function test_tool_checkout_cannot_exceed_available_stock(): void
    {
        House::factory()->create(['name' => 'AUD-TOOL-01']);
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-01',
            'name' => 'Alat Audit Satu',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);

        try {
            (new ToolImport)->collection(collect([[
                'kode' => $tool->code,
                'nama_alat' => $tool->name,
                'jenis' => 'pinjam',
                'jumlah' => 2,
                'unit_rumah' => 'AUD-TOOL-01',
                'tanggal' => '2026-09-17',
            ]]));
            $this->fail('Checkout above available stock must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Stok alat tidak mencukupi', $exception->getMessage());
        }

        $this->assertSame(1, $tool->fresh()->available_qty);
        $this->assertSame(0, ToolUsage::where('tool_id', $tool->id)->count());
    }

    public function test_tool_return_requires_an_active_loan(): void
    {
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-02',
            'name' => 'Alat Audit Dua',
            'total_qty' => 1,
            'available_qty' => 0,
        ]);
        $house = House::factory()->create(['name' => 'AUD-RETURN-01']);

        try {
            (new ToolImport)->collection(collect([[
                'kode' => $tool->code,
                'nama_alat' => $tool->name,
                'jenis' => 'kembali',
                'jumlah' => 1,
                'unit_rumah' => $house->name,
                'tanggal' => '2026-09-17',
            ]]));
            $this->fail('A return without an active loan must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Tidak ada peminjaman aktif', $exception->getMessage());
        }

        $this->assertSame(0, $tool->fresh()->available_qty);
        $this->assertSame(0, ToolReturnLog::where('tool_id', $tool->id)->count());
    }

    public function test_invalid_tool_transaction_date_fails_without_creating_a_loan(): void
    {
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-DATE',
            'name' => 'Alat Audit Tanggal',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);

        try {
            (new ToolImport)->collection(collect([[
                'kode' => $tool->code,
                'nama_alat' => $tool->name,
                'jenis' => 'pinjam',
                'jumlah' => 1,
                'unit_rumah' => 'AUD-TOOL-DATE',
                'tanggal' => 'tanggal-salah',
            ]]));
            $this->fail('An invalid tool date must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Tanggal tidak valid', $exception->getMessage());
        }

        $this->assertSame(1, $tool->fresh()->available_qty);
        $this->assertSame(0, ToolUsage::where('tool_id', $tool->id)->count());
    }

    public function test_modified_tool_rows_do_not_repeat_checkout_or_return(): void
    {
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-DUP',
            'name' => 'Alat Audit Duplikat',
            'total_qty' => 2,
            'available_qty' => 2,
        ]);
        $house = House::factory()->create(['name' => 'AUD-TOOL-DUP']);
        $checkout = [
            'kode' => $tool->code,
            'nama_alat' => $tool->name,
            'jenis' => 'pinjam',
            'jumlah' => 1,
            'unit_rumah' => $house->name,
            'tanggal' => '2026-09-17',
        ];

        (new ToolImport)->collection(collect([$checkout]));
        (new ToolImport)->collection(collect([$checkout + ['catatan_internal' => 'berubah saja']]));

        $this->assertSame(1, $tool->fresh()->available_qty);
        $this->assertSame(1, ToolUsage::where('tool_id', $tool->id)->count());
        $this->assertTrue((bool) ToolUsage::where('tool_id', $tool->id)->sole()->warehouse_source_recorded);

        $return = [
            'kode' => $tool->code,
            'nama_alat' => $tool->name,
            'jenis' => 'kembali',
            'jumlah' => 1,
            'unit_rumah' => $house->name,
            'tanggal' => '2026-09-17',
        ];

        (new ToolImport)->collection(collect([$return]));
        (new ToolImport)->collection(collect([$return + ['catatan_internal' => 'berubah saja']]));

        $this->assertSame(2, $tool->fresh()->available_qty);
        $this->assertSame(1, ToolReturnLog::where('tool_id', $tool->id)->count());
    }

    public function test_tool_import_records_return_destination_and_keeps_checkout_source(): void
    {
        $source = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $target = Warehouse::create(['name' => 'Gudang Impor Pengembalian']);
        $tool = Tool::factory()->create([
            'warehouse_id' => $source->id,
            'code' => 'ALT-AUD-WAREHOUSE-RETURN',
            'total_qty' => 1,
            'available_qty' => 1,
        ]);
        $house = House::factory()->create(['name' => 'AUD-WAREHOUSE-RETURN']);

        (new ToolImport)->collection(collect([[
            'kode' => $tool->code,
            'nama_alat' => $tool->name,
            'jenis' => 'pinjam',
            'jumlah' => 1,
            'unit_rumah' => $house->name,
            'tanggal' => '2026-09-17',
            'gudang' => $source->name,
        ]]));

        (new ToolImport)->collection(collect([[
            'kode' => $tool->code,
            'nama_alat' => $tool->name,
            'jenis' => 'kembali',
            'jumlah' => 1,
            'unit_rumah' => $house->name,
            'tanggal' => '2026-09-18',
            'gudang' => $target->name,
        ]]));

        $usage = ToolUsage::where('tool_id', $tool->id)->sole();
        $this->assertSame($source->id, $usage->warehouse_id);
        $this->assertTrue((bool) $usage->warehouse_source_recorded);
        $this->assertSame($target->id, ToolReturnLog::where('tool_id', $tool->id)->sole()->receiving_warehouse_id);
        $this->assertSame(0, (int) $tool->fresh()->warehouseBalances()->where('warehouse_id', $source->id)->value('available_qty'));
        $this->assertSame(1, (int) $tool->fresh()->warehouseBalances()->where('warehouse_id', $target->id)->value('available_qty'));
    }

    public function test_identical_tool_receipt_rows_create_distinct_batches(): void
    {
        $name = 'Audit Tool Receipt '.bin2hex(random_bytes(4));
        $row = [
            'kode' => 'ALT-AUD-RECEIPT',
            'nama_alat' => $name,
            'jenis' => 'masuk',
            'jumlah' => 2,
            'total_qty' => 2,
            'tersedia' => 2,
            'tanggal' => '2026-09-24 09:45',
        ];

        (new ToolImport)->collection(collect([$row, $row]));

        $batches = Tool::where('name', $name)->orderBy('id')->get();
        $this->assertCount(2, $batches);
        $this->assertCount(2, $batches->pluck('entry_code')->unique());
        $this->assertCount(2, $batches->pluck('code')->unique());
        $this->assertSame([2, 2], $batches->pluck('available_qty')->all());
        $this->assertSame('2026-09-24 09:45', $batches[0]->received_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-24', $batches[0]->received_date->format('Y-m-d'));
        $this->assertSame(auth()->id(), $batches[0]->recorded_by_id);
    }

    public function test_partial_tool_return_keeps_the_remaining_loan_active(): void
    {
        $tool = Tool::factory()->create([
            'code' => 'ALT-AUD-03',
            'name' => 'Alat Audit Tiga',
            'total_qty' => 3,
            'available_qty' => 0,
        ]);
        $house = House::factory()->create(['name' => 'AUD-RETURN-02']);
        $usage = ToolUsage::factory()->create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'quantity' => 3,
            'checkout_date' => '2026-09-16',
            'return_date' => null,
            'voided_at' => null,
        ]);

        (new ToolImport)->collection(collect([[
            'kode' => $tool->code,
            'nama_alat' => $tool->name,
            'jenis' => 'kembali',
            'jumlah' => 1,
            'unit_rumah' => $house->name,
            'tanggal' => '2026-09-17',
        ]]));

        $this->assertSame(1, $tool->fresh()->available_qty);
        $this->assertSame(1, ToolReturnLog::where('tool_id', $tool->id)->count());
        $this->assertSame(1, ToolUsage::whereKey($usage->id)->value('quantity'));
        $this->assertNotNull(ToolUsage::whereKey($usage->id)->value('return_date'));
        $this->assertDatabaseHas('tool_usages', [
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'quantity' => 2,
            'return_date' => null,
            'parent_usage_id' => $usage->id,
        ]);
    }

    public function test_explicit_transaction_codes_allow_two_legitimate_material_transactions(): void
    {
        House::factory()->create(['name' => 'AUD-LEGIT-01']);
        $material = Material::factory()->create([
            'name' => 'Audit Pasir',
            'stock' => 6,
            'unit' => 'm3',
        ]);

        foreach (['MAT-AUD-01', 'MAT-AUD-02'] as $code) {
            (new \App\Imports\MaterialImport)->collection(collect([[
                'nama_barang' => $material->name,
                'jenis' => 'keluar',
                'volume' => 2,
                'blok_rumah' => 'AUD-LEGIT-01',
                'tanggal' => '2026-09-17',
                'kode_transaksi' => $code,
            ]]));
        }

        $this->assertSame(2.0, (float) $material->fresh()->stock);
        $this->assertSame(2, MaterialUsage::where('material_id', $material->id)->count());
    }

    public function test_reconciliation_snapshots_capture_values_and_unattributed_tool_loans(): void
    {
        $before = ImportReconciliation::snapshot();
        $warehouse = Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $house = House::factory()->create(['name' => 'AUD-RECON-COST']);
        $material = Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Audit Rekonsiliasi Material',
            'stock' => 2.50,
            'unit_price' => 3.25,
        ]);
        MaterialUsage::factory()->create([
            'house_id' => $house->id,
            'material_id' => $material->id,
            'total_cost' => 25.50,
        ]);
        MaterialUsage::factory()->create([
            'house_id' => $house->id,
            'material_id' => $material->id,
            'total_cost' => 100,
            'voided_at' => now(),
        ]);

        $cluster = Cluster::create(['name' => 'Audit Rekonsiliasi '.bin2hex(random_bytes(4))]);
        ClusterExpense::create([
            'cluster_id' => $cluster->id,
            'type' => 'other',
            'description' => 'Audit reconciliation expense',
            'amount' => 456.78,
        ]);
        $tool = Tool::factory()->create([
            'warehouse_id' => $warehouse->id,
            'total_qty' => 5,
            'available_qty' => 3,
            'qty_broken' => 1,
        ]);
        ToolUsage::factory()->create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'warehouse_source_recorded' => true,
        ]);
        ToolUsage::factory()->create([
            'tool_id' => $tool->id,
            'house_id' => $house->id,
            'quantity' => 2,
            'warehouse_id' => null,
            'warehouse_source_recorded' => false,
        ]);

        $after = ImportReconciliation::snapshot();
        $delta = ImportReconciliation::delta($before, $after);

        $this->assertSame(813, $after['warehouses'][$warehouse->id]['material_value_minor']);
        $this->assertSame(1, $after['warehouses'][$warehouse->id]['tool_broken_qty']);
        $this->assertSame(1, $after['warehouses'][$warehouse->id]['tool_active_loan_qty']);
        $this->assertSame(2550, $after['house_costs'][$house->id]['material_cost_minor']);
        $this->assertSame(45678, $after['cluster_expenses'][$cluster->id]['amount_minor']);
        $this->assertSame(3, $after['totals']['active_tool_loan_qty']);
        $this->assertSame(2, $after['totals']['unattributed_active_tool_loan_qty']);
        $this->assertSame(813, $delta['warehouses'][$warehouse->id]['material_value_minor']);
        $this->assertSame(2550, $delta['house_costs'][$house->id]['material_cost_minor']);
        $this->assertSame(45678, $delta['cluster_expenses'][$cluster->id]['amount_minor']);
    }
}

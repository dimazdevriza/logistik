<?php

namespace Tests\Feature;

use App\Imports\MaterialImport;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\House;
use App\Models\StockIn;
use App\Models\User;
use App\Livewire\Logistik\TransaksiLogistik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MaterialImportStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function row(Material $material, float $qty): array
    {
        $houseName = 'AUD-STOCK-'.bin2hex(random_bytes(4));
        House::factory()->create(['name' => $houseName]);

        return ['nama_barang' => $material->name, 'jenis' => 'keluar', 'volume' => $qty,
            'blok_rumah' => $houseName, 'tanggal' => '2026-09-13', 'harga_satuan' => 100];
    }

    public function test_excess_stock_rejects_file_and_rolls_back_earlier_rows(): void
    {
        $material = Material::factory()->create(['stock' => 8, 'unit' => 'sak']);
        $before = $material->fresh()->getRawOriginal();
        $rows = collect([$this->row($material, 3), $this->row($material, 6)]);
        $houseCount = House::count();
        try {
            (new MaterialImport)->collection($rows);
            $this->fail('Insufficient stock must reject the import.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Baris 3', $error->getMessage());
            $this->assertStringContainsString('Tersedia: 5 sak, diminta: 6 sak', $error->getMessage());
        }
        $this->assertSame($before, $material->fresh()->getRawOriginal());
        $this->assertSame(0, MaterialUsage::where('material_id', $material->id)->count());
        $this->assertSame($houseCount, House::count());
    }

    public function test_exact_and_fractional_stock_are_deducted_in_full(): void
    {
        $material = Material::factory()->create(['stock' => 0.75]);
        $import = new MaterialImport;
        $import->collection(collect([$this->row($material, 0.25), $this->row($material, 0.5)]));
        $this->assertEquals(0, $material->fresh()->stock);
        $this->assertEquals(0.75, MaterialUsage::where('material_id', $material->id)->sum('quantity'));
        $this->assertSame(2, $import->successfulRows);
    }

    public function test_out_alias_does_not_create_stock_from_requested_quantity(): void
    {
        $material = Material::factory()->create(['stock' => 0]);
        House::factory()->create(['name' => 'AUD-ALIAS']);
        try {
            (new MaterialImport)->collection(collect([['nama_material' => $material->name,
                'jenis' => 'out', 'jumlah' => 20, 'unit_rumah' => 'AUD-ALIAS', 'tanggal' => '2026-09-13']]));
            $this->fail('A stock-out row must not initialize inventory.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Stok tidak mencukupi', $error->getMessage());
        }
        $this->assertEquals(0, $material->fresh()->stock);
        $this->assertSame(0, MaterialUsage::where('material_id', $material->id)->count());
    }

    public function test_ui_reports_row_error_and_records_failed_batch(): void
    {
        $material = Material::factory()->create(['name' => 'AUD-'.bin2hex(random_bytes(5)), 'stock' => 8, 'unit' => 'sak']);
        House::factory()->create(['name' => 'AUD-UI']);
        $csv = "nama_barang,jenis,volume,blok_rumah,tanggal\n{$material->name},keluar,20,AUD-UI,2026-09-13\n";
        Livewire::test(\App\Livewire\Logistik\Materials::class)
            ->call('openImportModal')
            ->set('importFile', UploadedFile::fake()->createWithContent('stock-check.csv', $csv))
            ->call('importExcel')
            ->assertHasErrors('importFile')
            ->assertSee('Tersedia: 8 sak, diminta: 20 sak');
        $this->assertEquals(8, $material->fresh()->stock);
        $this->assertDatabaseHas('import_batches', ['file_hash' => hash('sha256', $csv), 'status' => 'failed']);
    }

    public function test_incoming_template_uses_volume_instead_of_monetary_amount(): void
    {
        $material = Material::factory()->create(['name' => 'AUD-IN-'.bin2hex(random_bytes(5)), 'stock' => 8]);
        $csv = "Nama Barang,Jenis,Volume.,Harga Satuan,Jumlah,Tanggal\n{$material->name},masuk,2,100,200,2026-09-13\n";
        Livewire::test(\App\Livewire\Logistik\Materials::class)
            ->call('openImportModal')
            ->set('importFile', UploadedFile::fake()->createWithContent('incoming.csv', $csv))
            ->call('importExcel')->assertHasNoErrors('importFile');
        $receipt = \App\Models\StockIn::whereHas('material', fn ($query) => $query->where('name', $material->name))->sole();
        $this->assertEquals(2, $receipt->quantity);
        $this->assertEquals(200, $receipt->total_cost);
        $this->assertEquals(10, Material::where('name', $material->name)->sum('stock'));
    }

    public function test_inventory_snapshot_import_creates_idempotent_opening_batch_without_receipt_evidence(): void
    {
        $warehouse = \App\Models\Warehouse::where('name', 'Gudang Utama')->firstOrFail();
        $name = 'TEST Opening Import '.bin2hex(random_bytes(4));
        $row = [
            'kode_material' => 'TEST-OPEN-'.strtoupper(bin2hex(random_bytes(3))),
            'nama_material' => $name,
            'satuan' => 'sak',
            'harga_satuan' => 100000,
            'sisa_stok' => 5,
            'gudang' => $warehouse->name,
        ];

        (new MaterialImport)->collection(collect([$row]));

        $material = Material::where('name', $name)->sole();
        $openingBatch = StockIn::where('material_id', $material->id)->sole();
        $this->assertSame('opening_balance', $openingBatch->entry_type);
        $this->assertStringStartsWith('OPEN-MAT-', $openingBatch->entry_code);
        $this->assertNull($openingBatch->received_at);
        $this->assertSame($warehouse->id, $openingBatch->warehouse_id);
        $this->assertEquals(5, $openingBatch->quantity);
        $this->assertEquals(5, $openingBatch->remaining_quantity);
        $this->assertEquals(5, $material->stock);
        Livewire::test(TransaksiLogistik::class)
            ->set('material_id', (string) $material->id)
            ->assertSee($openingBatch->entry_code);

        $openingBatch->update(['remaining_quantity' => 0]);
        $material->update(['stock' => 0]);
        (new MaterialImport)->collection(collect([$row]));

        $this->assertSame(1, StockIn::where('material_id', $material->id)->count());
        $this->assertEquals(0, $openingBatch->fresh()->remaining_quantity);
        $this->assertEquals(0, $material->fresh()->stock);
    }

    public function test_negative_inventory_snapshot_is_rejected_for_reconciliation(): void
    {
        $name = 'TEST Negative Import '.bin2hex(random_bytes(4));
        $receiptCount = \App\Models\StockIn::count();

        try {
            (new MaterialImport)->collection(collect([[
                'nama_material' => $name,
                'satuan' => 'sak',
                'sisa_stok' => -3,
                'gudang' => 'Gudang Utama',
            ]]));
            $this->fail('Negative opening balances must be reconciled instead of being clamped to zero.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Saldo awal negatif perlu direkonsiliasi', $exception->getMessage());
        }

        $this->assertDatabaseMissing('materials', ['name' => $name]);
        $this->assertSame($receiptCount, \App\Models\StockIn::count());
    }

    public function test_fractional_volume_works_for_all_incoming_aliases(): void
    {
        foreach (['masuk', 'restock', 'in'] as $type) {
            $name = 'AUD-NEW-'.bin2hex(random_bytes(5));
            (new MaterialImport)->collection(collect([['nama_barang' => $name, 'jenis' => $type,
                'volume' => 0.75, 'harga_satuan' => 100, 'jumlah' => 75, 'satuan' => 'm3', 'tanggal' => '2026-09-13']]));
            $material = Material::where('name', $name)->sole();
            $receipt = \App\Models\StockIn::where('material_id', $material->id)->sole();
            $this->assertEquals(0.75, $material->stock);
            $this->assertEquals(0.75, $receipt->quantity);
            $this->assertEquals(75, $receipt->total_cost);
        }
    }

    public function test_invalid_incoming_volume_is_rejected_without_using_amount(): void
    {
        foreach ([null, '', 'invalid', 0, -2] as $volume) {
            $material = Material::factory()->create(['stock' => 8]);
            $message = null;
            try {
                (new MaterialImport)->collection(collect([['nama_barang' => $material->name,
                    'jenis' => 'masuk', 'volume' => $volume, 'jumlah' => 200, 'harga_satuan' => 100, 'tanggal' => '2026-09-13']]));
            } catch (\RuntimeException $error) {
                $message = $error->getMessage();
            }
            $this->assertNotNull($message);
            $this->assertStringContainsString('Baris 2', $message);
            $this->assertEquals(8, $material->fresh()->stock);
            $this->assertSame(0, \App\Models\StockIn::where('material_id', $material->id)->count());
        }
    }

    public function test_incoming_volume_can_fund_a_later_outgoing_row(): void
    {
        $material = Material::factory()->create(['stock' => 0]);
        $incoming = $this->row($material, 2);
        $incoming['jenis'] = 'masuk';
        $incoming['jumlah'] = 200;
        (new MaterialImport)->collection(collect([$incoming, $this->row($material, 1)]));
        $this->assertEquals(1, $material->fresh()->stock);
        $this->assertEquals(2, \App\Models\StockIn::where('material_id', $material->id)->sole()->quantity);
        $this->assertEquals(1, MaterialUsage::where('material_id', $material->id)->sole()->quantity);
    }

    public function test_identical_incoming_rows_create_distinct_receipt_batches(): void
    {
        $material = Material::factory()->create([
            'name' => 'AUD-RECEIPT-'.bin2hex(random_bytes(4)),
            'unit' => 'sak',
            'unit_price' => 100,
            'supplier_id' => null,
            'warehouse_id' => \App\Models\Warehouse::where('name', 'Gudang Utama')->value('id'),
            'stock' => 0,
        ]);
        $row = [
            'nama_barang' => $material->name,
            'satuan' => 'sak',
            'jenis' => 'masuk',
            'volume' => 2,
            'harga_satuan' => 100,
            'tanggal' => '2026-09-24 08:30',
        ];

        (new MaterialImport)->collection(collect([$row, $row]));

        $receipts = \App\Models\StockIn::where('material_id', $material->id)->orderBy('id')->get();
        $this->assertCount(2, $receipts);
        $this->assertCount(2, $receipts->pluck('entry_code')->unique());
        $this->assertSame([2.0, 2.0], $receipts->map(fn ($receipt) => (float) $receipt->remaining_quantity)->all());
        $this->assertSame('2026-09-24 08:30', $receipts[0]->received_at->format('Y-m-d H:i'));
        $this->assertEquals(4, $material->fresh()->stock);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ins', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('entry_code', 40)->nullable()->unique()->after('id');
            $table->string('entry_type', 20)->default('receipt')->after('entry_code');
            $table->string('submission_key', 36)->nullable()->unique()->after('entry_type');
            $table->foreignId('warehouse_id')->nullable()->after('material_id')->constrained()->nullOnDelete();
            $table->decimal('remaining_quantity', 15, 2)->nullable()->after('quantity');
            $table->timestamp('received_at')->nullable()->after('date');
        });

        DB::table('stock_ins')->whereNull('entry_code')->orderBy('id')->chunkById(500, function ($receipts): void {
            foreach ($receipts as $receipt) {
                DB::table('stock_ins')->where('id', $receipt->id)->update([
                    'entry_code' => 'RCV-LEGACY-'.$receipt->id,
                ]);
            }
        });

        DB::table('materials')->where('stock', '>', 0)->orderBy('id')->chunkById(500, function ($materials): void {
            foreach ($materials as $material) {
                DB::table('stock_ins')->insertOrIgnore([
                    'entry_code' => 'OPEN-MAT-'.$material->id,
                    'entry_type' => 'opening_balance',
                    'material_id' => $material->id,
                    'warehouse_id' => $material->warehouse_id,
                    'supplier_id' => $material->supplier_id,
                    'user_id' => null,
                    'quantity' => $material->stock,
                    'remaining_quantity' => $material->stock,
                    'unit_price' => $material->unit_price,
                    'total_cost' => 0,
                    'date' => now()->toDateString(),
                    'received_at' => null,
                    'notes' => 'Saldo warisan; waktu penerimaan tidak tercatat.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::table('stock_ins', function (Blueprint $table): void {
            $table->string('entry_code', 40)->nullable(false)->change();
        });

        Schema::table('tools', function (Blueprint $table): void {
            $table->string('entry_code', 40)->nullable()->unique()->after('code');
            $table->string('entry_type', 20)->default('receipt')->after('entry_code');
            $table->string('submission_key', 36)->nullable()->unique()->after('entry_type');
            $table->timestamp('received_at')->nullable()->after('warehouse_id');
        });

        DB::table('tools')->orderBy('id')->chunkById(500, function ($tools): void {
            foreach ($tools as $tool) {
                DB::table('tools')->where('id', $tool->id)->update([
                    'entry_code' => 'ALT-LEGACY-'.$tool->id,
                    'entry_type' => 'opening_balance',
                ]);
            }
        });

        Schema::table('tools', function (Blueprint $table): void {
            $table->string('entry_code', 40)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('stock_ins')->whereNotNull('submission_key')->exists()
            || DB::table('tools')->whereNotNull('submission_key')->exists()) {
            throw new RuntimeException('Cannot roll back receipt identity after a new receipt has been recorded.');
        }

        if (DB::table('stock_ins')->where('entry_type', 'opening_balance')->whereColumn('remaining_quantity', '<', 'quantity')->exists()) {
            throw new RuntimeException('Cannot roll back receipt identity after a legacy opening balance has been consumed.');
        }

        if (DB::table('stock_ins')->whereNull('user_id')->where('entry_type', '!=', 'opening_balance')->exists()) {
            throw new RuntimeException('Cannot roll back receipt identity: receipts with deleted users must keep nullable user_id.');
        }

        DB::table('stock_ins')->where('entry_type', 'opening_balance')->delete();

        $toolColumns = array_values(array_filter(
            ['entry_code', 'entry_type', 'submission_key', 'received_at'],
            fn (string $column): bool => Schema::hasColumn('tools', $column),
        ));
        $toolIndexes = collect(Schema::getIndexes('tools'))->pluck('name')->all();

        Schema::table('tools', function (Blueprint $table) use ($toolColumns, $toolIndexes): void {
            if (in_array('tools_entry_code_unique', $toolIndexes, true)) {
                $table->dropUnique(['entry_code']);
            }
            if (in_array('tools_submission_key_unique', $toolIndexes, true)) {
                $table->dropUnique(['submission_key']);
            }
            $table->dropColumn($toolColumns);
        });

        Schema::table('stock_ins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropUnique(['entry_code']);
            $table->dropUnique(['submission_key']);
            $table->dropColumn(['entry_code', 'entry_type', 'submission_key', 'remaining_quantity', 'received_at']);
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

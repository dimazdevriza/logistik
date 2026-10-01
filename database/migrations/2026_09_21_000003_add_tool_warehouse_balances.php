<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_warehouse_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->unsignedInteger('available_qty')->default(0);
            $table->unsignedInteger('qty_broken')->default(0);
            $table->timestamps();
            $table->unique(['tool_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'available_qty']);
        });

        Schema::table('tool_usages', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('tool_id')->constrained('warehouses')->nullOnDelete();
            $table->index(['tool_id', 'warehouse_id']);
        });

        DB::table('tools')->orderBy('id')->chunkById(100, function ($tools): void {
            foreach ($tools as $tool) {
                if (! $tool->warehouse_id) {
                    continue;
                }

                DB::table('tool_warehouse_balances')->insertOrIgnore([
                    'tool_id' => $tool->id,
                    'warehouse_id' => $tool->warehouse_id,
                    'available_qty' => $tool->available_qty,
                    'qty_broken' => $tool->qty_broken,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

    }

    public function down(): void
    {
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::dropIfExists('tool_warehouse_balances');
    }
};

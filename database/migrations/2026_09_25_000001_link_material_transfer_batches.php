<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table): void {
            $table->foreignId('source_stock_in_id')->nullable()->after('destination_material_id')->constrained('stock_ins')->restrictOnDelete();
            $table->foreignId('destination_stock_in_id')->nullable()->after('source_stock_in_id')->constrained('stock_ins')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('inventory_transfers')->whereNotNull('source_stock_in_id')->orWhereNotNull('destination_stock_in_id')->exists()) {
            throw new RuntimeException('Cannot remove material transfer batch links after a linked transfer has been recorded.');
        }

        Schema::table('inventory_transfers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('destination_stock_in_id');
            $table->dropConstrainedForeignId('source_stock_in_id');
        });
    }
};

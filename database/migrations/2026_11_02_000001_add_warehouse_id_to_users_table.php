<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('cluster_id')
                ->constrained()
                ->nullOnDelete();
        });

        $warehouseId = DB::table('warehouses')->where('name', 'Gudang Utama')->value('id')
            ?? DB::table('warehouses')->orderBy('id')->value('id');

        if ($warehouseId) {
            DB::table('users')
                ->whereIn('role', ['logistik', 'pengawas'])
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $warehouseId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};

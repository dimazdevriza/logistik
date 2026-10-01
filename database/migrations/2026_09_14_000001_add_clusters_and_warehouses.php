<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::table('houses', function (Blueprint $table) {
            $table->foreignId('cluster_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        $warehouseId = DB::table('warehouses')->where('name', 'Gudang Utama')->value('id');
        if (! $warehouseId) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'name' => 'Gudang Utama',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('materials')->whereNull('warehouse_id')->update(['warehouse_id' => $warehouseId]);
        DB::table('tools')->whereNull('warehouse_id')->update(['warehouse_id' => $warehouseId]);
    }

    public function down(): void
    {
        Schema::table('tools', fn (Blueprint $table) => $table->dropConstrainedForeignId('warehouse_id'));
        Schema::table('materials', fn (Blueprint $table) => $table->dropConstrainedForeignId('warehouse_id'));
        Schema::table('houses', fn (Blueprint $table) => $table->dropConstrainedForeignId('cluster_id'));

        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('clusters');
    }
};

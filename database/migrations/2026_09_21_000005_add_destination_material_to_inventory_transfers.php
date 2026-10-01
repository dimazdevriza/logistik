<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->foreignId('destination_material_id')
                ->nullable()
                ->after('material_id')
                ->constrained('materials')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_material_id');
        });
    }
};

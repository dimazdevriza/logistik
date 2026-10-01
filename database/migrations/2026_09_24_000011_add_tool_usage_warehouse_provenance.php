<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_usages', function (Blueprint $table): void {
            $table->boolean('warehouse_source_recorded')->default(false)->after('warehouse_id');
        });
    }

    public function down(): void
    {
        if (DB::table('tool_usages')->exists()) {
            throw new RuntimeException('Cannot remove tool source provenance while checkout history exists.');
        }

        Schema::table('tool_usages', function (Blueprint $table): void {
            $table->dropColumn('warehouse_source_recorded');
        });
    }
};

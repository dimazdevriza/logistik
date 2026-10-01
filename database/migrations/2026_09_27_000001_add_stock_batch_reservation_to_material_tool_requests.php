<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_tool_requests', function (Blueprint $table): void {
            $table->foreignId('stock_in_id')
                ->nullable()
                ->after('material_id')
                ->constrained('stock_ins')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('material_tool_requests')->whereNotNull('stock_in_id')->exists()) {
            throw new RuntimeException('Cannot remove selected stock batches after requests have been recorded.');
        }

        Schema::table('material_tool_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_in_id');
        });
    }
};

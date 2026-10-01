<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_tool_requests', function ($table): void {
            $table->enum('status', ['pending', 'dispatched', 'partially_arrived', 'arrived', 'approved', 'rejected'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        if (DB::table('material_tool_requests')->where('status', 'partially_arrived')->exists()) {
            throw new RuntimeException('Cannot remove partial-arrival status while open partial shipments exist.');
        }

        Schema::table('material_tool_requests', function ($table): void {
            $table->enum('status', ['pending', 'dispatched', 'arrived', 'approved', 'rejected'])
                ->default('pending')
                ->change();
        });
    }
};

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
            $table->enum('status', ['pending', 'dispatched', 'partially_arrived', 'arrived', 'resolved', 'approved', 'rejected'])
                ->default('pending')->change();
        });
    }

    public function down(): void
    {
        if (DB::table('material_tool_requests')->where('status', 'resolved')->exists()) {
            throw new RuntimeException('Cannot remove resolved dispatch status while reconciled dispatches exist.');
        }

        Schema::table('material_tool_requests', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'dispatched', 'partially_arrived', 'arrived', 'approved', 'rejected'])
                ->default('pending')->change();
        });
    }
};

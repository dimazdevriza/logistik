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
            $table->string('dispatch_proof_image')->nullable()->after('dispatched_at');
        });
    }

    public function down(): void
    {
        if (DB::table('material_tool_requests')->whereNotNull('dispatch_proof_image')->exists()) {
            throw new RuntimeException('Cannot remove dispatch proof images after deliveries have been recorded.');
        }

        Schema::table('material_tool_requests', function (Blueprint $table): void {
            $table->dropColumn('dispatch_proof_image');
        });
    }
};

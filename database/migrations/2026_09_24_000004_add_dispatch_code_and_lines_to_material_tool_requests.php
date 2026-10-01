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
            $table->string('dispatch_code', 40)->nullable()->unique()->after('request_code');
        });

        Schema::create('dispatch_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_tool_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->restrictOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->timestamps();
            $table->index(['material_tool_request_id', 'stock_in_id'], 'dispatch_req_batch_idx');
            $table->index(['material_tool_request_id', 'tool_id', 'warehouse_id'], 'dispatch_req_tool_wh_idx');
        });
    }

    public function down(): void
    {
        if (DB::table('dispatch_lines')->exists()
            || DB::table('material_tool_requests')->whereNotNull('dispatch_code')->exists()) {
            throw new RuntimeException('Cannot remove dispatch lines after stock has been dispatched.');
        }

        Schema::dropIfExists('dispatch_lines');
        Schema::table('material_tool_requests', function (Blueprint $table): void {
            $table->dropUnique(['dispatch_code']);
            $table->dropColumn('dispatch_code');
        });
    }
};

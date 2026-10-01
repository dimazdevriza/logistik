<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_tool_requests', function (Blueprint $table) {
            $table->decimal('unit_price_at_dispatch', 15, 2)->nullable()->after('quantity');
            $table->timestamp('rejected_returned_at')->nullable()->after('approved_at');
            $table->foreignId('rejected_returned_by_id')->nullable()->after('rejected_returned_at')->constrained('users')->nullOnDelete();
        });

        // Existing rejected shipments already released their reservation under
        // the old flow, so do not ask staff to receive them a second time.
        DB::table('material_tool_requests')
            ->where('status', 'rejected')
            ->whereNotNull('dispatched_at')
            ->update(['rejected_returned_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('material_tool_requests', function (Blueprint $table) {
            $table->dropForeign(['rejected_returned_by_id']);
            $table->dropColumn(['unit_price_at_dispatch', 'rejected_returned_at', 'rejected_returned_by_id']);
        });
    }
};

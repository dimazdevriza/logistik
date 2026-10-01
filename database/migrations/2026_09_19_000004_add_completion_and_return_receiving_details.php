<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('target_end_date');
            $table->foreignId('completed_by_id')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('tool_return_logs', function (Blueprint $table) {
            $table->foreignId('receiving_warehouse_id')->nullable()->after('reported_by')->constrained('warehouses')->restrictOnDelete();
            $table->timestamp('received_at')->nullable()->after('receiving_warehouse_id');
            $table->foreignId('received_by_id')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tool_return_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by_id');
            $table->dropColumn('received_at');
            $table->dropConstrainedForeignId('receiving_warehouse_id');
        });

        Schema::table('houses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by_id');
            $table->dropColumn('completed_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->string('transaction_code', 30)->nullable()->after('id');
            $table->unique(['transaction_code', 'material_id'], 'stock_in_transaction_material_unique');
        });

        Schema::table('tool_return_logs', function (Blueprint $table) {
            $table->string('transaction_code', 30)->nullable()->after('id');
            $table->unique(['transaction_code', 'tool_id', 'house_id'], 'tool_return_transaction_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tool_return_logs', function (Blueprint $table) {
            $table->dropUnique('tool_return_transaction_item_unique');
            $table->dropColumn('transaction_code');
        });

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropUnique('stock_in_transaction_material_unique');
            $table->dropColumn('transaction_code');
        });
    }
};

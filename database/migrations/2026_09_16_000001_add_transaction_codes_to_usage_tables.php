<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            $table->string('transaction_code', 30)->nullable()->after('id');
            $table->unique(['transaction_code', 'house_id'], 'material_usage_transaction_house_unique');
        });

        Schema::table('tool_usages', function (Blueprint $table) {
            $table->string('transaction_code', 30)->nullable()->after('id');
            $table->unique(['transaction_code', 'house_id'], 'tool_usage_transaction_house_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->dropUnique('tool_usage_transaction_house_unique');
            $table->dropColumn('transaction_code');
        });

        Schema::table('material_usages', function (Blueprint $table) {
            $table->dropUnique('material_usage_transaction_house_unique');
            $table->dropColumn('transaction_code');
        });
    }
};

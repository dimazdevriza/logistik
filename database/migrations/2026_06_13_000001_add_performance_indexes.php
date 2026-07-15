<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            $table->index('usage_date');
            $table->index(['house_id', 'usage_date']);
        });

        Schema::table('tool_usages', function (Blueprint $table) {
            $table->index('return_date');
            $table->index('checkout_date');
            $table->index(['house_id', 'return_date']);
        });

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->index('date');
            $table->index(['material_id', 'date']);
        });

        Schema::table('houses', function (Blueprint $table) {
            $table->index('status');
            $table->index('name');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->index('stock');
            $table->index('name');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->index('condition');
            $table->index('available_qty');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            $table->dropIndex(['usage_date']);
            $table->dropIndex(['house_id', 'usage_date']);
        });

        Schema::table('tool_usages', function (Blueprint $table) {
            $table->dropIndex(['return_date']);
            $table->dropIndex(['checkout_date']);
            $table->dropIndex(['house_id', 'return_date']);
        });

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['material_id', 'date']);
        });

        Schema::table('houses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['name']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex(['stock']);
            $table->dropIndex(['name']);
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['condition']);
            $table->dropIndex(['available_qty']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};

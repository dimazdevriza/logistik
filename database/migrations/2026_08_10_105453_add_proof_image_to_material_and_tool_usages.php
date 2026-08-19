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
            $table->string('proof_image')->nullable()->after('notes');
        });
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->string('proof_image')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            $table->dropColumn('proof_image');
        });
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->dropColumn('proof_image');
        });
    }
};

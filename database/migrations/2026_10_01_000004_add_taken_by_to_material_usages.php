<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_usages', 'taken_by')) {
            Schema::table('material_usages', function (Blueprint $table) {
                $table->string('taken_by', 120)->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('material_usages', 'taken_by')) {
            Schema::table('material_usages', function (Blueprint $table) {
                $table->dropColumn('taken_by');
            });
        }
    }
};

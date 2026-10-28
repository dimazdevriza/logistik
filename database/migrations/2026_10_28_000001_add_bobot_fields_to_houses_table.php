<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            if (! Schema::hasColumn('houses', 'material_budget')) {
                $table->decimal('material_budget', 15, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('houses', 'physical_progress')) {
                $table->decimal('physical_progress', 5, 2)->default(0)->after('material_budget');
            }
            if (! Schema::hasColumn('houses', 'progress_stages')) {
                $table->json('progress_stages')->nullable()->after('physical_progress');
            }
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('houses', 'progress_stages')) {
                $drop[] = 'progress_stages';
            }
            if (Schema::hasColumn('houses', 'physical_progress')) {
                $drop[] = 'physical_progress';
            }
            if (Schema::hasColumn('houses', 'material_budget')) {
                $drop[] = 'material_budget';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

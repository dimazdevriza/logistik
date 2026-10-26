<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('houses', 'category')) {
            Schema::table('houses', function (Blueprint $table) {
                // 'house' = unit rumah, 'public_work' = pekerjaan umum (jalan, drainase, dll.)
                $table->string('category', 20)->default('house')->after('cluster_id');
                $table->index(['category', 'cluster_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('houses', 'category')) {
            Schema::table('houses', function (Blueprint $table) {
                $table->dropIndex(['category', 'cluster_id']);
                $table->dropColumn('category');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cluster_expenses', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->after('cluster_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cluster_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('house_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->foreignId('parent_usage_id')
                ->nullable()
                ->after('notes')
                ->constrained('tool_usages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_usage_id');
        });
    }
};

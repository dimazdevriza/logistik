<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('parent_usage_id');
            $table->foreignId('voided_by')
                ->nullable()
                ->after('voided_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('tool_usages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropIndex(['voided_at']);
            $table->dropColumn('voided_at');
        });
    }
};

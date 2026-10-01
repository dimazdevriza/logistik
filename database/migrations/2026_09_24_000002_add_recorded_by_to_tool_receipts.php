<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table): void {
            $table->foreignId('recorded_by_id')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('tools')->whereNotNull('recorded_by_id')->exists()) {
            throw new RuntimeException('Cannot remove tool receipt attribution after a receipt has been recorded.');
        }

        Schema::table('tools', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recorded_by_id');
        });
    }
};

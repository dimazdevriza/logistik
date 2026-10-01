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
            $table->date('received_date')->nullable()->after('received_at');
        });
    }

    public function down(): void
    {
        if (DB::table('tools')->whereNotNull('received_date')->exists()) {
            throw new RuntimeException('Cannot remove tool receipt dates after receipt data has been recorded.');
        }

        Schema::table('tools', function (Blueprint $table): void {
            $table->dropColumn('received_date');
        });
    }
};

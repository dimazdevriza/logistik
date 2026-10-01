<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_usages', function (Blueprint $table): void {
            $table->string('dispatch_code', 30)->nullable()->after('transaction_code')->index();
        });

        Schema::table('tool_usages', function (Blueprint $table): void {
            $table->string('dispatch_code', 30)->nullable()->after('transaction_code')->index();
        });
    }

    public function down(): void
    {
        if (DB::table('material_usages')->whereNotNull('dispatch_code')->exists()
            || DB::table('tool_usages')->whereNotNull('dispatch_code')->exists()) {
            throw new RuntimeException('Cannot remove dispatch references after dispatch usage has been recorded.');
        }

        Schema::table('tool_usages', function (Blueprint $table): void {
            $table->dropIndex(['dispatch_code']);
            $table->dropColumn('dispatch_code');
        });

        Schema::table('material_usages', function (Blueprint $table): void {
            $table->dropIndex(['dispatch_code']);
            $table->dropColumn('dispatch_code');
        });
    }
};

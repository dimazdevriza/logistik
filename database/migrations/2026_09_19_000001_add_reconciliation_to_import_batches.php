<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->json('reconciliation_before')->nullable()->after('skipped_rows');
            $table->json('reconciliation_after')->nullable()->after('reconciliation_before');
            $table->json('reconciliation_delta')->nullable()->after('reconciliation_after');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['reconciliation_before', 'reconciliation_after', 'reconciliation_delta']);
        });
    }
};

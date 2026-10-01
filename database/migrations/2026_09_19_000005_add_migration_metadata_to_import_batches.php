<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->date('cutoff_date')->nullable()->after('file_hash');
            $table->string('migration_mode', 32)->nullable()->after('cutoff_date');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['cutoff_date', 'migration_mode']);
        });
    }
};

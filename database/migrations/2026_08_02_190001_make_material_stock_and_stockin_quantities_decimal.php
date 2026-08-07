<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: ALTER TYPE not supported, handled by base migration
            Schema::table('materials', function (Blueprint $table) {
                $table->decimal('stock', 15, 2)->default(0)->change();
            });
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->decimal('quantity', 15, 2)->change();
            });
        } else {
            // MySQL: raw ALTER for efficiency
            DB::statement('ALTER TABLE materials MODIFY stock DECIMAL(15, 2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE stock_ins MODIFY quantity DECIMAL(15, 2) NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('materials', function (Blueprint $table) {
                $table->integer('stock')->default(0)->change();
            });
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->integer('quantity')->change();
            });
        } else {
            DB::statement('ALTER TABLE materials MODIFY stock INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE stock_ins MODIFY quantity INT NOT NULL');
        }
    }
};

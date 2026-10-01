<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE materials MODIFY stock DECIMAL(15, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stock_ins MODIFY quantity DECIMAL(15, 2) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE materials MODIFY stock INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE stock_ins MODIFY quantity INT NOT NULL');
    }
};

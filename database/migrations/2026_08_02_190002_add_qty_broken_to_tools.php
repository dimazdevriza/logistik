<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->unsignedInteger('qty_broken')->default(0)->after('available_qty');
        });

        DB::table('tools')
            ->where('condition', 'rusak')
            ->where('available_qty', 0)
            ->update(['qty_broken' => DB::raw('total_qty')]);
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn('qty_broken');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_return_logs', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'fixed', 'discarded', 'received'])
                ->default('received')->change();
        });

        DB::table('tool_return_logs')
            ->where('report_type', 'broken')
            ->where('status', 'pending')
            ->update(['status' => 'received']);
    }

    public function down(): void
    {
        DB::table('tool_return_logs')
            ->where('report_type', 'broken')
            ->where('status', 'received')
            ->update(['status' => 'pending']);

        Schema::table('tool_return_logs', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'fixed', 'discarded'])
                ->default('pending')->change();
        });
    }
};

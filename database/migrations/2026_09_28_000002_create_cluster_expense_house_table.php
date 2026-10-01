<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_expense_house', function (Blueprint $table) {
            $table->foreignId('cluster_expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->constrained()->restrictOnDelete();
            $table->primary(['cluster_expense_id', 'house_id']);
        });

        DB::table('cluster_expenses')->whereNotNull('house_id')
            ->select(['id', 'house_id'])->orderBy('id')->chunk(500, function ($expenses) {
                DB::table('cluster_expense_house')->insertOrIgnore($expenses->map(fn ($expense) => [
                    'cluster_expense_id' => $expense->id,
                    'house_id' => $expense->house_id,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_expense_house');
    }
};

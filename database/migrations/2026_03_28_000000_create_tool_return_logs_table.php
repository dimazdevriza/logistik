<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_return_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_usage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->enum('report_type', ['normal', 'repair', 'broken', 'lost'])->default('normal');
            $table->enum('status', ['pending', 'fixed', 'discarded'])->default('pending');
            $table->decimal('replacement_cost', 15, 2)->nullable(); // for broken/lost financial tracking
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_return_logs');
    }
};

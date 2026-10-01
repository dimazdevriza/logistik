<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_expense_id')->nullable()->constrained('cluster_expenses')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32); // rental, rental_extension, drainage, electrical, streetlight, other
            $table->string('description');
            $table->string('vendor')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('off_hire_date')->nullable();
            $table->string('status', 20)->default('active'); // active, returned, closed
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('bill_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['cluster_id', 'type']);
            $table->index(['cluster_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_expenses');
    }
};

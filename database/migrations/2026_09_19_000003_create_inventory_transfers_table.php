<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code')->unique();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->restrictOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained('tools')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transferred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['source_warehouse_id', 'destination_warehouse_id'], 'transfer_warehouse_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};

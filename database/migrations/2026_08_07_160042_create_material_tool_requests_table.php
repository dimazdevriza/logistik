<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('material_tool_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('house_id')->constrained('houses')->cascadeOnDelete();
            $table->enum('type', ['material', 'tool']);
            $table->foreignId('material_id')->nullable()->constrained('materials')->cascadeOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained('tools')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('notes')->nullable();
            $table->enum('status', ['pending', 'dispatched', 'arrived', 'approved', 'rejected'])->default('pending');
            $table->string('arrival_proof_image')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_tool_requests');
    }
};

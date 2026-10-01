<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_tool_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('correction_of_id')->nullable()->constrained('dispatch_receipts')->restrictOnDelete();
            $table->string('event_type', 20)->default('confirmation');
            $table->uuid('submission_key')->unique();
            $table->timestamp('received_at');
            $table->string('proof_image')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->index(['material_tool_request_id', 'received_at'], 'dispatch_receipt_request_date_idx');
        });

        Schema::create('dispatch_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dispatch_receipt_id')->constrained('dispatch_receipts')->restrictOnDelete();
            $table->foreignId('dispatch_line_id')->nullable()->constrained('dispatch_lines')->restrictOnDelete();
            $table->decimal('received_quantity', 15, 2);
            $table->decimal('damaged_quantity', 15, 2);
            $table->timestamps();
            $table->unique(['dispatch_receipt_id', 'dispatch_line_id'], 'dispatch_receipt_line_source_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('dispatch_receipts')->exists()) {
            throw new RuntimeException('Cannot remove receipt confirmations or correction history after receiving has been recorded.');
        }

        Schema::dropIfExists('dispatch_receipt_lines');
        Schema::dropIfExists('dispatch_receipts');
    }
};

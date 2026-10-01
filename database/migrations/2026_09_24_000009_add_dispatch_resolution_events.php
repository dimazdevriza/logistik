<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_usages', function (Blueprint $table): void {
            $table->foreignId('dispatch_line_id')->nullable()->after('dispatch_code')
                ->constrained('dispatch_lines')->restrictOnDelete();
        });

        Schema::table('tool_return_logs', function (Blueprint $table): void {
            $table->foreignId('resolved_by_id')->nullable()->after('received_by_id')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by_id');
            $table->text('resolution_notes')->nullable()->after('resolved_at');
            $table->decimal('repair_cost', 15, 2)->nullable()->after('resolution_notes');
        });

        Schema::create('dispatch_resolution_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_tool_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('dispatch_line_id')->nullable()->constrained('dispatch_lines')->restrictOnDelete();
            $table->foreignId('dispatch_receipt_line_id')->nullable()->constrained('dispatch_receipt_lines')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->uuid('submission_key')->unique();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->text('notes');
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['material_tool_request_id', 'event_type', 'recorded_at'], 'dispatch_resolution_req_type_idx');
            $table->index(['dispatch_line_id', 'event_type'], 'dispatch_resolution_line_type_idx');
        });
    }

    public function down(): void
    {
        if (DB::table('dispatch_resolution_events')->exists()
            || DB::table('tool_usages')->whereNotNull('dispatch_line_id')->exists()
            || DB::table('tool_return_logs')->whereNotNull('resolved_at')->exists()
            || DB::table('tool_return_logs')->whereNotNull('repair_cost')->exists()) {
            throw new RuntimeException('Cannot remove dispatch resolution or tool return provenance history.');
        }

        Schema::dropIfExists('dispatch_resolution_events');
        Schema::table('tool_return_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('resolved_by_id');
            $table->dropColumn(['resolved_at', 'resolution_notes', 'repair_cost']);
        });
        Schema::table('tool_usages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dispatch_line_id');
        });
    }
};

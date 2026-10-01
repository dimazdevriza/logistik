<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('houses', 'warranty_expires_at')) {
            Schema::table('houses', function (Blueprint $table) {
                $table->timestamp('warranty_expires_at')->nullable()->after('completed_at');
            });
        }

        DB::table('houses')
            ->where('status', 'selesai')
            ->whereNotNull('completed_at')
            ->whereNull('warranty_expires_at')
            ->chunkById(500, function ($houses): void {
                foreach ($houses as $house) {
                    DB::table('houses')->where('id', $house->id)->update([
                        'warranty_expires_at' => Carbon::parse($house->completed_at)->addYear(),
                    ]);
                }
            });

        if (! Schema::hasColumn('material_usages', 'is_warranty')) {
            Schema::table('material_usages', function (Blueprint $table) {
                $table->boolean('is_warranty')->default(false)->after('proof_image');
            });
        }

        if (! Schema::hasColumn('tool_usages', 'is_warranty')) {
            Schema::table('tool_usages', function (Blueprint $table) {
                $table->boolean('is_warranty')->default(false)->after('proof_image');
            });
        }
    }

    public function down(): void
    {
        if ((Schema::hasColumn('material_usages', 'is_warranty') && DB::table('material_usages')->where('is_warranty', true)->exists())
            || (Schema::hasColumn('tool_usages', 'is_warranty') && DB::table('tool_usages')->where('is_warranty', true)->exists())) {
            throw new RuntimeException('Warranty allocation history exists; export or preserve it before rolling back this migration.');
        }

        if (Schema::hasColumn('tool_usages', 'is_warranty')) {
            Schema::table('tool_usages', function (Blueprint $table) {
                $table->dropColumn('is_warranty');
            });
        }

        if (Schema::hasColumn('material_usages', 'is_warranty')) {
            Schema::table('material_usages', function (Blueprint $table) {
                $table->dropColumn('is_warranty');
            });
        }

        if (Schema::hasColumn('houses', 'warranty_expires_at')) {
            Schema::table('houses', function (Blueprint $table) {
                $table->dropColumn('warranty_expires_at');
            });
        }
    }
};

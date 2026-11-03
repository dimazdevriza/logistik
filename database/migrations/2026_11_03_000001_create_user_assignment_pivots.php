<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cluster_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'cluster_id']);
        });

        Schema::create('user_warehouse', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'warehouse_id']);
        });

        $field = DB::table('users')->whereIn('role', ['logistik', 'pengawas']);

        (clone $field)->whereNotNull('cluster_id')->get(['id', 'cluster_id'])
            ->each(fn ($u) => DB::table('cluster_user')->insertOrIgnore(['user_id' => $u->id, 'cluster_id' => $u->cluster_id]));

        (clone $field)->whereNotNull('warehouse_id')->get(['id', 'warehouse_id'])
            ->each(fn ($u) => DB::table('user_warehouse')->insertOrIgnore(['user_id' => $u->id, 'warehouse_id' => $u->warehouse_id]));
    }

    public function down(): void
    {
        Schema::dropIfExists('user_warehouse');
        Schema::dropIfExists('cluster_user');
    }
};

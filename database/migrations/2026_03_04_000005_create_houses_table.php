<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Blok A-01
            $table->string('type'); // e.g. Tipe 36, Tipe 45
            $table->enum('status', ['perencanaan', 'pembangunan', 'selesai'])->default('perencanaan');
            $table->decimal('budget', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};

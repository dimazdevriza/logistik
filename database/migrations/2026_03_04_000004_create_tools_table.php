<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique(); // unique asset tag
            $table->enum('condition', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->integer('total_qty')->default(1);
            $table->integer('available_qty')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};

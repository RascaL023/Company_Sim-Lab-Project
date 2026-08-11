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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('unit', 30); // satuan: pcs, box, liter, dll
            $table->decimal('stock_quantity', 10, 2)->nullable(); // hanya untuk bahan; alat = NULL (jumlah dari ItemUnit)
            $table->decimal('minimum_stock', 10, 2)->nullable(); // hanya untuk bahan
            $table->string('location', 100)->nullable(); // legacy: dipindah ke location_id oleh migrasi 2026_08_11_000001
            $table->string('manufacturer', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

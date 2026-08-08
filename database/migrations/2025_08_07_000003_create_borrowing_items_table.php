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
        Schema::create('borrowing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_request_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate(); // katalog item
            $table->foreignId('item_unit_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate(); // unit fisik (untuk alat)
            $table->decimal('quantity', 10, 2)->default(1); // untuk bahan (consumable)
            $table->enum('condition_before', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->enum('condition_after', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->nullable();
            $table->boolean('is_damaged')->default(false);
            $table->text('damage_notes')->nullable();
            $table->dateTime('borrow_date')->nullable(); // tanggal benar-benar diambil
            $table->dateTime('expected_return_date')->nullable();
            $table->dateTime('actual_return_date')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate(); // admin yang cek saat kembali
            $table->timestamp('checked_at')->nullable();
            $table->text('check_notes')->nullable(); // catatan pemeriksaan
            $table->timestamps();

            $table->index(['borrowing_request_id', 'item_id']);
            $table->index('item_unit_id');
            $table->index('actual_return_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowing_items');
    }
};

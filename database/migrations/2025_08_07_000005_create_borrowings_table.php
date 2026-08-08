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
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('borrower_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('borrow_date'); // tanggal benar-benar diambil (checkout)
            $table->dateTime('expected_return_date')->nullable();
            $table->dateTime('actual_return_date')->nullable(); // tanggal dikembalikan (checkin)
            $table->enum('condition_before', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->enum('condition_after', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->nullable();
            $table->boolean('is_damaged')->default(false);
            $table->text('damage_notes')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate(); // admin yang checkout
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate(); // admin yang checkin
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate(); // admin yang memeriksa kondisi saat kembali
            $table->timestamp('checked_at')->nullable(); // waktu pemeriksaan
            $table->text('check_notes')->nullable(); // catatan pemeriksaan
            $table->enum('status', [
                'dipinjam',      // sedang dipinjam
                'dikembalikan',  // sudah dikembalikan dan dicek
                'terlambat',     // melewati expected_return_date
                'hilang',        // dilaporkan hilang
            ])->default('dipinjam');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['borrower_id', 'status']);
            $table->index('expected_return_date');
            $table->index('actual_return_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};

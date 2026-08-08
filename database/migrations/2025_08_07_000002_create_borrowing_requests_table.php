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
        Schema::create('borrowing_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->unique(); // e.g., BR-20250001
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('status', [
                'diajukan',       // baru diajukan, menunggu approval
                'disetujui',      // disetujui admin, siap diambil
                'ditolak',        // ditolak admin
                'diproses',       // barang sedang diambil/dipinjam
                'selesai',        // semua barang dikembalikan dan dicek
                'batal',          // dibatalkan oleh pemohon sebelum disetujui
            ])->default('diajukan');
            $table->text('purpose')->nullable(); // tujuan peminjaman
            $table->text('rejection_reason')->nullable(); // alasan ditolak
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable(); // catatan tambahan
            $table->timestamps();

            $table->index(['requested_by', 'status']);
            $table->index('status');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowing_requests');
    }
};

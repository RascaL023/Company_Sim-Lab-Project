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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_unit_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->enum('type', [
                'in_purchase',      // pembelian baru
                'in_return',        // pengembalian pinjaman
                'in_adjustment',    // koreksi stok (+)
                'out_borrow',       // peminjaman keluar
                'out_usage',        // pemakaian bahan
                'out_disposal',     // buang/hapus
                'out_adjustment',   // koreksi stok (-)
                'transfer_in',      // transfer masuk (jika multi-lokasi)
                'transfer_out',     // transfer keluar
            ]);
            $table->decimal('quantity', 10, 2);
            $table->decimal('quantity_before', 10, 2);
            $table->decimal('quantity_after', 10, 2);
            $table->string('reference_type', 150)->nullable(); // polymorphic reference
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['item_id', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

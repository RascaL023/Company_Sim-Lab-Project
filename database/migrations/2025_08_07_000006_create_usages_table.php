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
        Schema::create('usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_unit_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate(); // for tracking specific units if needed
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate(); // who used it
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate(); // admin who verified
            $table->decimal('quantity_used', 10, 2);
            $table->decimal('quantity_before', 10, 2)->nullable(); // stock before usage
            $table->decimal('quantity_after', 10, 2)->nullable(); // stock after usage
            $table->dateTime('usage_date');
            $table->enum('status', [
                'dicatat',       // baru dicatat oleh user
                'diverifikasi',  // sudah diverifikasi admin
                'ditolak',       // ditolak verifikasi (jumlah tidak sesuai, dll)
            ])->default('dicatat');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('purpose', 255)->nullable(); // tujuan pemakaian
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'usage_date']);
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usages');
    }
};

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
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_unit_id')->nullable()->constrained('item_units')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('reason', ['rusak_total', 'kedaluwarsa', 'hilang', 'lainnya']);
            $table->text('notes')->nullable();
            $table->foreignId('proposed_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamp('proposed_at')->useCurrent();
            $table->enum('status', ['diusulkan', 'disetujui', 'ditolak'])->default('diusulkan');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['item_id', 'status']);
            $table->index(['item_unit_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};

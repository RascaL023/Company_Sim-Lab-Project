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
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string('serial_number', 100)->unique();
            $table->string('asset_tag', 50)->nullable()->unique(); // barcode/QR tag
            $table->enum('condition', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->string('location', 100)->nullable(); // current location (room, shelf, etc.)
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable(); // dihapus di Phase 9 (lihat 2026_08_12_000001)
            $table->date('next_calibration_date')->nullable(); // kalibrasi opsional, per unit
            $table->date('last_calibration_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_id', 'condition']);
            $table->index('next_calibration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};

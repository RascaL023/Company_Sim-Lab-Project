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
        Schema::create('item_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_unit_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('calibration_date');
            $table->date('next_calibration_date')->nullable();
            $table->string('calibrated_by', 150)->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->enum('result', ['lulus', 'tidak_lulus'])->default('lulus');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['item_unit_id', 'calibration_date']);
            $table->index('next_calibration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_calibrations');
    }
};

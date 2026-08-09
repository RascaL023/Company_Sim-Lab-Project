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
        Schema::create('item_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_unit_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('maintenance_date');
            $table->text('description');
            $table->string('performed_by', 150)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->enum('status', ['selesai', 'proses', 'tertunda'])->default('selesai');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['item_unit_id', 'maintenance_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_maintenances');
    }
};

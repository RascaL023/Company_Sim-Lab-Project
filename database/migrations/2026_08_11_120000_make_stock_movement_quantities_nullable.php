<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ALAT items have no numeric stock, so their stock movements legitimately
     * carry no quantity_before/quantity_after. Make the columns nullable so
     * alat movements can be recorded without faking a 0 inventory.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity_before', 10, 2)->nullable()->change();
            $table->decimal('quantity_after', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity_before', 10, 2)->change();
            $table->decimal('quantity_after', 10, 2)->change();
        });
    }
};

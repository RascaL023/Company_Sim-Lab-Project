<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Domain reason: item_units only exist for alat (bahan is tracked as
     * stock, without physical units) and there is no batch system. Equipment
     * does not "expire" in this workflow, so expiry_date on a physical unit
     * has no real function. Consumables that expire should carry expiry on a
     * future batch table for bahan, not on item_units.
     */
    public function up(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('purchase_date');
        });
    }
};

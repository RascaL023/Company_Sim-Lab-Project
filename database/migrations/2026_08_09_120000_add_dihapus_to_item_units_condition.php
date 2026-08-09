<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add `dihapus` to item_units.condition for approved asset disposals.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE item_units MODIFY COLUMN `condition` ENUM('baik', 'rusak_ringan', 'rusak_berat', 'hilang', 'dihapus') NOT NULL DEFAULT 'baik'");

            return;
        }

        // SQLite: drop dependent index, rebuild column without CHECK, restore index.
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'condition']);
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->string('condition_new', 32)->default('baik');
        });

        foreach (DB::table('item_units')->get(['id', 'condition']) as $unit) {
            DB::table('item_units')->where('id', $unit->id)->update([
                'condition_new' => $unit->condition ?: 'baik',
            ]);
        }

        Schema::table('item_units', function (Blueprint $table) {
            $table->dropColumn('condition');
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->renameColumn('condition_new', 'condition');
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->index(['item_id', 'condition']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('item_units')->where('condition', 'dihapus')->update(['condition' => 'hilang']);
            DB::statement("ALTER TABLE item_units MODIFY COLUMN `condition` ENUM('baik', 'rusak_ringan', 'rusak_berat', 'hilang') NOT NULL DEFAULT 'baik'");

            return;
        }

        Schema::table('item_units', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'condition']);
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->string('condition_old', 32)->default('baik');
        });

        foreach (DB::table('item_units')->get(['id', 'condition']) as $unit) {
            $mapped = $unit->condition === 'dihapus' ? 'hilang' : ($unit->condition ?: 'baik');
            DB::table('item_units')->where('id', $unit->id)->update(['condition_old' => $mapped]);
        }

        Schema::table('item_units', function (Blueprint $table) {
            $table->dropColumn('condition');
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->renameColumn('condition_old', 'condition');
        });

        Schema::table('item_units', function (Blueprint $table) {
            $table->index(['item_id', 'condition']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Pindahkan `items.location` (string bebas) menjadi FK `items.location_id`
     * ke tabel `locations`, dan kosongkan stok untuk alat.
     *
     * Aturan domain:
     * - bahan  : location_id terisi, stock_quantity/minimum_stock >= 0.
     * - alat   : location_id = NULL (lokasi fisik di item_units.location_id),
     *            stock_quantity/minimum_stock = NULL (jumlah unit dari item_units).
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('unit')
                ->constrained('locations')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        // Backfill lokasi dari nilai string legacy `items.location`.
        $legacy = DB::table('items')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->pluck('location');

        foreach ($legacy as $name) {
            $locationId = DB::table('locations')->where('name', $name)->value('id')
                ?? DB::table('locations')->insertGetId([
                    'code' => $this->uniqueCodeFromName($name),
                    'name' => $name,
                    'description' => 'Migrated from legacy items.location',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('items')
                ->where('location', $name)
                ->update(['location_id' => $locationId]);
        }

        // Normalisasi domain: lokasi & stok hanya berlaku untuk bahan.
        $alatItemIds = DB::table('items')
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->where('categories.type', 'alat')
            ->pluck('items.id');

        DB::table('items')
            ->whereIn('id', $alatItemIds)
            ->update([
                'location_id' => null,
                'stock_quantity' => null,
                'minimum_stock' => null,
            ]);

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        // Constraint non-negatif stok (MySQL). SQLite tidak mendukung
        // ALTER TABLE ADD CONSTRAINT CHECK; ditegakkan oleh business logic
        // dan seeder (lihat DatabaseDataValidationTest).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE items ADD CONSTRAINT items_stock_quantity_non_negative CHECK (stock_quantity IS NULL OR stock_quantity >= 0)');
            DB::statement('ALTER TABLE items ADD CONSTRAINT items_minimum_stock_non_negative CHECK (minimum_stock IS NULL OR minimum_stock >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE items DROP CONSTRAINT items_stock_quantity_non_negative');
            DB::statement('ALTER TABLE items DROP CONSTRAINT items_minimum_stock_non_negative');
        }

        Schema::table('items', function (Blueprint $table) {
            $table->string('location', 100)->nullable();
        });

        $items = DB::table('items')
            ->whereNotNull('location_id')
            ->get(['id', 'location_id']);

        foreach ($items as $item) {
            $name = DB::table('locations')->where('id', $item->location_id)->value('name');

            DB::table('items')
                ->where('id', $item->id)
                ->update(['location' => $name]);
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }

    private function uniqueCodeFromName(string $name): string
    {
        $base = Str::upper(Str::slug($name, '-'));
        if ($base === '') {
            $base = 'LOC';
        }

        $code = $base;
        $suffix = 1;

        while (DB::table('locations')->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
};

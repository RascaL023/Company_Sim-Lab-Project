<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        $legacyLocations = DB::table('item_units')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->pluck('location');

        $map = [];

        foreach ($legacyLocations as $name) {
            $code = $this->uniqueCodeFromName($name);

            $id = DB::table('locations')->insertGetId([
                'code' => $code,
                'name' => $name,
                'description' => 'Migrated from legacy item_units.location',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $map[$name] = $id;
        }

        foreach ($map as $name => $locationId) {
            DB::table('item_units')
                ->where('location', $name)
                ->update(['location_id' => $locationId]);
        }

        Schema::table('item_units', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->string('location', 100)->nullable();
        });

        $units = DB::table('item_units')
            ->whereNotNull('location_id')
            ->get(['id', 'location_id']);

        foreach ($units as $unit) {
            $name = DB::table('locations')->where('id', $unit->location_id)->value('name');

            DB::table('item_units')
                ->where('id', $unit->id)
                ->update(['location' => $name]);
        }

        Schema::table('item_units', function (Blueprint $table) {
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

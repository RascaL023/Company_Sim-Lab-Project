<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Location adalah FK untuk items.location_id (bahan) dan item_units.location_id
     * (alat). Sebelumnya menggunakan nullOnDelete() yang secara diam-diam
     * meng-null-kan referensi saat Location dihapus. Ubah menjadi restrictOnDelete()
     * agar penghapusan Location yang masih digunakan ditolak oleh DB (safety net;
     * controller sudah mengembalikan 422 sebelum mencapai sini).
     */
    public function up(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->foreignId('location_id')
                ->nullable()
                ->after('unit')
                ->constrained('locations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->foreignId('location_id')
                ->nullable()
                ->after('unit')
                ->constrained('locations')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }
};

<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Beberapa lokasi jelas, dipakai oleh bahan (items.location_id)
     * dan unit fisik alat (item_units.location_id).
     */
    public function run(): void
    {
        $locations = [
            ['code' => 'LOC-MIKROSKOPI', 'name' => 'Lab Mikroskopi', 'description' => 'Lokasi unit mikroskop'],
            ['code' => 'LOC-SENTRIFUGASI', 'name' => 'Lab Sentrifugasi', 'description' => 'Lokasi unit sentrifus'],
            ['code' => 'LOC-GUDANG-KIMIA', 'name' => 'Gudang Kimia', 'description' => 'Penyimpanan reagen kimia'],
            ['code' => 'LOC-GUDANG-KONSUMABEL', 'name' => 'Gudang Konsumabel', 'description' => 'Penyimpanan barang habis pakai'],
        ];

        foreach ($locations as $loc) {
            Location::create($loc);
        }

        $this->command?->info('Created '.Location::count().' locations');
    }
}

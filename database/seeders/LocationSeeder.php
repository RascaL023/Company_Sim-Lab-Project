<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $names = Item::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        foreach ($names as $name) {
            Location::firstOrCreateFromName($name, 'Seeded from catalog item.location');
        }

        $this->command?->info('Created '.Location::count().' locations');
    }
}
